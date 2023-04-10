<?php
/**
 * Site lock
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */

/* Site lock
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @return array data for the template display
 */

function roles_admin_sitelock(Array $args=array())
{
    // Security
    if(!xarSecurity::check('ManageRoles')) return;

    if (!xarVar::fetch('cmd', 'isset', $cmd, NULL, xarVar::DONT_SET)) return;

# --------------------------------------------------------
# No command; just get the configuration from the modvar
#
    if(!isset($cmd)) {
        $lockvars = unserialize(xarModVars::get('roles','lockdata'));
        $toggle = $lockvars['locked'];
        $roles = $lockvars['roles'];
        $lockedoutmsg = (!isset($lockvars['message']) || $lockvars['message'] == '') ? xarML('The site is currently locked. Thank you for your patience.') : $lockvars['message'];
        $notifymsg = $lockvars['notifymsg'];

    } else {
# --------------------------------------------------------
# We have a command; get the data from the template
#
        if (!xarVar::fetch('serialroles', 'str', $serialroles, NULL, xarVar::NOT_REQUIRED)) return;
        if (!isset($serialroles)) {
            return xarTpl::module('roles', 'user', 'errors');
        }
        $roles = unserialize($serialroles);
        $rolesCount = count($roles);
        if (!xarVar::fetch('lockedoutmsg', 'str',   $lockedoutmsg, NULL, xarVar::NOT_REQUIRED,xarVar::PREP_FOR_DISPLAY)) return;
        if (!xarVar::fetch('notifymsg',    'str',   $notifymsg,    NULL, xarVar::NOT_REQUIRED,xarVar::PREP_FOR_DISPLAY)) return;
        if (!xarVar::fetch('toggle',       'str',   $toggle,       NULL, xarVar::NOT_REQUIRED,xarVar::PREP_FOR_DISPLAY)) return;
        if (!xarVar::fetch('notify',       'isset', $notify,       NULL, xarVar::DONT_SET)) return;
        if(!isset($notify)) $notify = array();
        for($i=0; $i<$rolesCount; $i++) $roles[$i]['notify'] = in_array($roles[$i]['id'],$notify);

# --------------------------------------------------------
# We are deleting a user from the list of exceptions
#
        if ($cmd == 'delete') {
            if (!xarVar::fetch('id', 'int', $id, NULL, xarVar::DONT_SET)) return;
            if (isset($id)) {
                for($i=0; $i < $rolesCount; $i++) {
                    if ($roles[$i]['id'] == $id) {
                        array_splice($roles,$i,1);
                        break;
                    }
                }
				// Write the configuration to disk
				$lockdata = array('roles'     => $roles,
								  'message'   => $lockedoutmsg,
								  'locked'    => $toggle,
								  'notifymsg' => $notifymsg);
				xarModVars::set('roles', 'lockdata', serialize($lockdata));
            }

# --------------------------------------------------------
# We are adding a user to the list of exceptions
#
        } elseif ($cmd == 'add') {
            if (!xarVar::fetch('newname', 'str', $newname, NULL, xarVar::DONT_SET)) return;
            if (isset($newname)) {
                $r = xarRoles::ufindRole($newname);
                if (!$r) $r = xarRoles::findRole($newname);
                if($r) {
                    $newid  = $r->getID();
                    $newname = $r->isUser() ? $r->getUser() : $r->getName();
                }
                else $newid = 0;

                $newelement = array('id' => $newid, 'name' => $newname , 'notify' => TRUE);
                if ($newid != 0 && !in_array($newelement,$roles))
                    $roles[] = $newelement;

            // Write the configuration to disk
            $lockdata = array('roles'     => $roles,
                              'message'   => $lockedoutmsg,
                              'locked'    => $toggle,
                              'notifymsg' => $notifymsg);
            xarModVars::set('roles', 'lockdata', serialize($lockdata));
            }
        } elseif ($cmd == 'save') {
            $lockdata = array('roles'     => $roles,
                              'message'   => $lockedoutmsg,
                              'locked'    => $toggle,
                              'notifymsg' => $notifymsg);
            xarModVars::set('roles', 'lockdata', serialize($lockdata));
			// Refresh by jumping to the same page
            xarController::redirect(xarController::URL('roles', 'admin', 'sitelock'));

# --------------------------------------------------------
# We are locking or unlocking the site
#
        } elseif ($cmd == 'toggle') {
            // Toggle the previous value, turning the site on or off
            $toggle = (int)$toggle ? 0 : 1;

            // Get the roles
            $lockdata = unserialize(xarModVars::get('roles', 'lockdata'));
            $rolesarray = $lockdata['roles'];
			foreach($rolesarray as $thisrole) {
                $roletoletin = xarRoles::get($thisrole['id']);
				$notify = $thisrole['notify'];
				// If this is a user, add it to the list
				if ($roletoletin->isUser()) {
					// Add the notify value so we are only dealing with a single array
					$spared[$thisrole['id']] = array('role' => $roletoletin, 'notify' => $notify);
					
				// If this is a group, add its users to the list
				} else {
					$children = $roletoletin->getUsers();
					foreach ($children as $thisrole) {
						$this_id = $thisrole->properties['id']->value;
						$roletoletin = xarRoles::get($this_id);
						$spared[$this_id] = array('role' => $roletoletin, 'notify' => $notify);
					}
				}
			}            

            $admin = xarRoles::get(xarModVars::get('roles','admin'));
            $mailinfo = array('subject' => 'Site Lock',
                              'from' => $admin->getEmail()
                            );

			// We locked the site
            if ($toggle == 1) {

            // Clear the active sessions
                
                try {
                	xarSession::clear(array_keys($spared));
                } catch (Exception $e) {
                    $msg = xarML('Could not clear sessions table');
                    throw new Exception($msg);
                }
                $mailinfo['message'] = 'The site ' . xarModVars::get('themes','SiteName') . ' has been locked.';

			// We unlocked the site
            } else {
               $mailinfo['message'] = 'The site ' . xarModVars::get('themes','SiteName') . ' has been unlocked.';
            }

            $mailinfo['message'] .= "\n\n" . $notifymsg;

            // Send the mails
            $badmails = 0;
            foreach($spared as $recipient) {
            	if ($recipient['notify'] != 1) continue;
                $mailinfo['info'] = $recipient['role']->getEmail();
                if (!xarMod::apiFunc('mail','admin','sendmail', $mailinfo)) $badmails ++;
            }

            // Save the locked value
            $lockdata = unserialize(xarModVars::get('roles', 'lockdata'));
            $lockdata['locked'] = $toggle;
            xarModVars::set('roles', 'lockdata', serialize($lockdata));

            if($badmails) {
                return xarTpl::module('roles','user','errors',array('layout' => 'mail_failed', 'badmails' => $badmails));
            }
			// Refresh by jumping to the same page
            xarController::redirect(xarController::URL('roles', 'admin', 'sitelock'));
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
    }
    else {
        $data['togglelabel']   = xarML('Lock the Site');
        $data['statusmessage'] = xarML('The site is unlocked');
    }
    $data['addlabel']    = xarML('Add a role');
    $data['deletelabel'] = xarML('Remove');
    $data['savelabel']   = xarML('Save the configuration');

    return $data;
}
