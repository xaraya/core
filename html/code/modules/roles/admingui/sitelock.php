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
        if (!xarSecurity::check('ManageRoles')) {
            return;
        }

        if (!xarVar::fetch('cmd', 'isset', $cmd, null, xarVar::DONT_SET)) {
            return;
        }

        # --------------------------------------------------------
        # Get the configuration from the modvar
        #
        $lockvars = unserialize((string) xarModVars::get('roles', 'lockdata'));
        $toggle = $lockvars['locked'];
        $roles = $lockvars['roles'];
        $lockedoutmsg = (!isset($lockvars['message']) || $lockvars['message'] == '') ? xarML('The site is currently locked. Thank you for your patience.') : $lockvars['message'];
        $notifymsg = $lockvars['notifymsg'];
        //        echo "<pre>";var_dump($lockvars);

        if (isset($cmd)) {

            # --------------------------------------------------------
            # We have a command; get the data from the template
            #
            if (!xarVar::fetch('serialroles', 'str', $serialroles, null, xarVar::NOT_REQUIRED)) {
                return;
            }
            if (!isset($serialroles)) {
                return xarTpl::module('roles', 'user', 'errors');
            }
            $roles = unserialize($serialroles);
            $rolesCount = count($roles);
            if (!xarVar::fetch('lockedoutmsg', 'str', $lockedoutmsg, null, xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY)) {
                return;
            }
            if (!xarVar::fetch('notifymsg', 'str', $notifymsg, null, xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY)) {
                return;
            }
            if (!xarVar::fetch('toggle', 'int', $toggle, 0, xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY)) {
                return;
            }
            if (!xarVar::fetch('notify', 'array', $notify, [], xarVar::DONT_SET)) {
                return;
            }

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
                if (!xarVar::fetch('id', 'int', $id, null, xarVar::DONT_SET)) {
                    return;
                }
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
                    xarModVars::set('roles', 'lockdata', serialize($lockdata));
                }

                # --------------------------------------------------------
                # We are adding a user to the list of exceptions
                #
            } elseif ($cmd == 'add') {
                if (!xarVar::fetch('newname', 'str', $newname, null, xarVar::DONT_SET)) {
                    return;
                }
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
                    xarModVars::set('roles', 'lockdata', serialize($lockdata));
                }
            } elseif ($cmd == 'save') {
                $lockdata = ['roles'     => $roles,
                    'message'   => $lockedoutmsg,
                    'locked'    => $toggle,
                    'notifymsg' => $notifymsg];
                xarModVars::set('roles', 'lockdata', serialize($lockdata));
                // Refresh by jumping to the same page
                xarController::redirect(xarController::URL('roles', 'admin', 'sitelock'), null, $this->getContext());

                # --------------------------------------------------------
                # We are locking or unlocking the site
                #
            } elseif ($cmd == 'toggle') {
                // Toggle the previous value, turning the site on or off
                $toggle = (int) $toggle ? 0 : 1;

                // Get the roles
                $lockdata = unserialize((string) xarModVars::get('roles', 'lockdata'));
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

                $admin = xarRoles::get(xarModVars::get('roles', 'admin'));
                $mailinfo = ['subject' => 'Site Lock',
                    'from' => $admin->getEmail(),
                ];

                // We locked the site
                if ($toggle == 1) {

                    // Clear the active sessions

                    try {
                        xarSession::clear(array_keys($spared));
                    } catch (Exception $e) {
                        $msg = xarML('Could not clear sessions table');
                        throw new Exception($msg);
                    }
                    $mailinfo['message'] = 'The site ' . xarModVars::get('themes', 'SiteName') . ' has been locked.';

                    // We unlocked the site
                } else {
                    $mailinfo['message'] = 'The site ' . xarModVars::get('themes', 'SiteName') . ' has been unlocked.';
                }

                $mailinfo['message'] .= "\n\n" . $notifymsg;

                // Send the mails
                $badmails = 0;
                foreach ($spared as $recipient) {
                    if ($recipient['notify'] != 1) {
                        continue;
                    }
                    $mailinfo['info'] = $recipient['role']->getEmail();
                    if (!xarMod::apiFunc('mail', 'admin', 'sendmail', $mailinfo)) {
                        $badmails++;
                    }
                }

                // Save the locked value
                $lockdata = unserialize((string) xarModVars::get('roles', 'lockdata'));
                $lockdata['locked'] = $toggle;
                xarModVars::set('roles', 'lockdata', serialize($lockdata));

                if ($badmails) {
                    return xarTpl::module('roles', 'user', 'errors', ['layout' => 'mail_failed', 'badmails' => $badmails]);
                }
                // Refresh by jumping to the same page
                xarController::redirect(xarController::URL('roles', 'admin', 'sitelock'), null, $this->getContext());
            }
        }

        # --------------------------------------------------------
        # Send the data to the template for display
        #
        $data['roles']        = $roles;
        $data['serialroles']  = xarVar::prepForDisplay(serialize($roles));
        $data['lockedoutmsg'] = $lockedoutmsg;
        $data['notifymsg']    = $notifymsg;
        $data['toggle']       = $toggle;
        if ($toggle == 1) {
            $data['togglelabel']   = xarML('Unlock the Site');
            $data['statusmessage'] = xarML('The site is locked');
        } else {
            $data['togglelabel']   = xarML('Lock the Site');
            $data['statusmessage'] = xarML('The site is unlocked');
        }
        $data['addlabel']    = xarML('Add a role');
        $data['deletelabel'] = xarML('Remove');
        $data['savelabel']   = xarML('Save the configuration');

        return $data;
    }
}
