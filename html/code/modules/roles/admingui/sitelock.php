<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\AdminGui;
use Exception;
use xarController;
use xarMod;
use xarModVars;
use xarRoles;
use xarSecurity;
use xarSession;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles admin sitelock function
 * @extends MethodClass<AdminGui>
 */
class SitelockMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Site lock
     * @package modules\roles
     * @subpackage roles
     * @category Xaraya Web Applications Framework
     * @version 2.4.0
     * @copyright see the html/credits.html file in this release
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @link http://xaraya.info/index.php/release/27.html
     * @see AdminGui::sitelock()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('ManageRoles')) {
            return;
        }

        $this->var()->check('cmd', $cmd);

        # --------------------------------------------------------
        # Get the configuration from the modvar
        #
        $lockvars = unserialize((string) $this->mod()->getVar('lockdata'));
        $toggle = $lockvars['locked'];
        $roles = $lockvars['roles'];
        $lockedoutmsg = (!isset($lockvars['message']) || $lockvars['message'] == '') ? $this->ml('The site is currently locked. Thank you for your patience.') : $lockvars['message'];
        $notifymsg = $lockvars['notifymsg'];
        //        echo "<pre>";var_dump($lockvars);

        if (isset($cmd)) {

            # --------------------------------------------------------
            # We have a command; get the data from the template
            #
            $this->var()->find('serialroles', $serialroles, 'str', null);
            if (!isset($serialroles)) {
                return $this->tpl()->module('roles', 'user', 'errors');
            }
            $roles = unserialize($serialroles);
            $rolesCount = count($roles);
            $this->var()->find('lockedoutmsg', $lockedoutmsg, 'str', null);
            $this->var()->find('notifymsg', $notifymsg, 'str', null);
            $this->var()->find('toggle', $toggle, 'int', 0);
            $this->var()->check('notify', $notify, 'array', []);

            foreach ($roles as $key => $role) {
                if (isset($notify[$role['id']])) {
                    $roles[$key]['notify'] = true;
                } else {
                    $roles[$key]['notify'] = false;
                }
            }
            # --------------------------------------------------------
            # We are deleting a user from the list of exceptions
            #
            if ($cmd == 'delete') {
                $this->var()->check('id', $id, 'int', null);
                if (isset($id)) {
                    for ($i = 0; $i < $rolesCount; $i++) {
                        if ($roles[$i]['id'] == $id) {
                            array_splice($roles, $i, 1);
                            break;
                        }
                    }
                    // Write the configuration to disk
                    $lockdata = ['roles'     => $roles,
                        'message'   => $lockedoutmsg,
                        'locked'    => $toggle,
                        'notifymsg' => $notifymsg];
                    $this->mod()->setVar('lockdata', serialize($lockdata));
                }

                # --------------------------------------------------------
                # We are adding a user to the list of exceptions
                #
            } elseif ($cmd == 'add') {
                $this->var()->check('newname', $newname, 'str', null);
                if (isset($newname)) {
                    $r = xarRoles::ufindRole($newname);
                    if (!$r) {
                        $r = xarRoles::findRole($newname);
                    }
                    if ($r) {
                        $newid  = $r->getID();
                        $newname = $r->isUser() ? $r->getUser() : $r->getName();
                    } else {
                        $newid = 0;
                    }

                    $newelement = ['id' => $newid, 'name' => $newname, 'notify' => true];
                    if ($newid != 0 && !in_array($newelement, $roles)) {
                        $roles[] = $newelement;
                    }

                    // Write the configuration to disk
                    $lockdata = ['roles'     => $roles,
                        'message'   => $lockedoutmsg,
                        'locked'    => $toggle,
                        'notifymsg' => $notifymsg];
                    $this->mod()->setVar('lockdata', serialize($lockdata));
                }
            } elseif ($cmd == 'save') {
                $lockdata = ['roles'     => $roles,
                    'message'   => $lockedoutmsg,
                    'locked'    => $toggle,
                    'notifymsg' => $notifymsg];
                $this->mod()->setVar('lockdata', serialize($lockdata));
                // Refresh by jumping to the same page
                $this->ctl()->redirect($this->ctl()->getModuleURL('roles', 'admin', 'sitelock'));
                return true;

                # --------------------------------------------------------
                # We are locking or unlocking the site
                #
            } elseif ($cmd == 'toggle') {
                // Toggle the previous value, turning the site on or off
                $toggle = (int) $toggle ? 0 : 1;

                // Get the roles
                $lockdata = unserialize((string) $this->mod()->getVar('lockdata'));
                var_dump($lockdata);
                $rolesarray = $lockdata['roles'];
                foreach ($rolesarray as $thisrole) {
                    $roletoletin = xarRoles::get($thisrole['id']);
                    $notify = $thisrole['notify'];
                    var_dump($notify);
                    // If this is a user, add it to the list
                    if ($roletoletin->isUser()) {
                        // Add the notify value so we are only dealing with a single array
                        $spared[$thisrole['id']] = ['role' => $roletoletin, 'notify' => $notify];

                        // If this is a group, add its users to the list
                    } else {
                        $children = $roletoletin->getUsers();
                        foreach ($children as $thisrole) {
                            $this_id = $thisrole->properties['id']->value;
                            $roletoletin = xarRoles::get($this_id);
                            $spared[$this_id] = ['role' => $roletoletin, 'notify' => $notify];
                        }
                    }
                }

                $admin = xarRoles::get($this->mod()->getVar('admin'));
                $mailinfo = ['subject' => 'Site Lock',
                    'from' => $admin->getEmail(),
                ];

                // We locked the site
                if ($toggle == 1) {

                    // Clear the active sessions

                    try {
                        xarSession::clear(array_keys($spared));
                    } catch (Exception $e) {
                        $msg = $this->ml('Could not clear sessions table');
                        throw new Exception($msg);
                    }
                    $mailinfo['message'] = 'The site ' . $this->mod('themes')->getVar('SiteName') . ' has been locked.';

                    // We unlocked the site
                } else {
                    $mailinfo['message'] = 'The site ' . $this->mod('themes')->getVar('SiteName') . ' has been unlocked.';
                }

                $mailinfo['message'] .= "\n\n" . $notifymsg;

                // Send the mails
                $badmails = 0;
                foreach ($spared as $recipient) {
                    if ($recipient['notify'] != 1) {
                        continue;
                    }
                    $mailinfo['info'] = $recipient['role']->getEmail();
                    if (!$this->mod()->apiFunc('mail', 'admin', 'sendmail', $mailinfo)) {
                        $badmails++;
                    }
                }

                // Save the locked value
                $lockdata = unserialize((string) $this->mod()->getVar('lockdata'));
                $lockdata['locked'] = $toggle;
                $this->mod()->setVar('lockdata', serialize($lockdata));

                if ($badmails) {
                    return $this->tpl()->module('roles', 'user', 'errors', ['layout' => 'mail_failed', 'badmails' => $badmails]);
                }
                // Refresh by jumping to the same page
                $this->ctl()->redirect($this->ctl()->getModuleURL('roles', 'admin', 'sitelock'));
                return true;
            }
        }

        # --------------------------------------------------------
        # Send the data to the template for display
        #
        $data['roles']        = $roles;
        $data['serialroles']  = $this->var()->prep(serialize($roles));
        $data['lockedoutmsg'] = $lockedoutmsg;
        $data['notifymsg']    = $notifymsg;
        $data['toggle']       = $toggle;
        if ($toggle == 1) {
            $data['togglelabel']   = $this->ml('Unlock the Site');
            $data['statusmessage'] = $this->ml('The site is locked');
        } else {
            $data['togglelabel']   = $this->ml('Lock the Site');
            $data['statusmessage'] = $this->ml('The site is unlocked');
        }
        $data['addlabel']    = $this->ml('Add a role');
        $data['deletelabel'] = $this->ml('Remove');
        $data['savelabel']   = $this->ml('Save the configuration');

        return $data;
    }
}
