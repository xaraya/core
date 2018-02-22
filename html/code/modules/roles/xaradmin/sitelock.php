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
    if(!xarSecurityCheck('ManageRoles')) return;

    if (!xarVarFetch('cmd', 'isset', $cmd, NULL, XARVAR_DONT_SET)) return;

    if(!isset($cmd)) {
    // Get parameters from the db
        $lockvars = unserialize(xarModVars::get('roles','lockdata'));
        $toggle = $lockvars['locked'];
        $roles = $lockvars['roles'];
        $lockedoutmsg = (!isset($lockvars['message']) || $lockvars['message'] == '') ? xarML('The site is currently locked. Thank you for your patience.') : $lockvars['message'];
        $notifymsg = $lockvars['notifymsg'];
    } else {
    // Get parameters from input
        if (!xarVarFetch('serialroles', 'str', $serialroles, NULL, XARVAR_NOT_REQUIRED)) return;
        if (!isset($serialroles)) {
            return xarTpl::module('roles', 'user', 'errors');
        }
        $roles = unserialize($serialroles);
        $rolesCount = count($roles);
        if (!xarVarFetch('lockedoutmsg', 'str',   $lockedoutmsg, NULL, XARVAR_NOT_REQUIRED,XARVAR_PREP_FOR_DISPLAY)) return;
        if (!xarVarFetch('notifymsg',    'str',   $notifymsg,    NULL, XARVAR_NOT_REQUIRED,XARVAR_PREP_FOR_DISPLAY)) return;
        if (!xarVarFetch('toggle',       'str',   $toggle,       NULL, XARVAR_NOT_REQUIRED,XARVAR_PREP_FOR_DISPLAY)) return;
        if (!xarVarFetch('notify',       'isset', $notify,       NULL, XARVAR_DONT_SET)) return;
        if(!isset($notify)) $notify = array();
        for($i=0; $i<$rolesCount; $i++) $roles[$i]['notify'] = in_array($roles[$i]['id'],$notify);

        if ($cmd == 'delete') {
            if (!xarVarFetch('id', 'int', $id, NULL, XARVAR_DONT_SET)) return;
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
        } elseif ($cmd == 'add') {
            if (!xarVarFetch('newname', 'str', $newname, NULL, XARVAR_DONT_SET)) return;
            if (isset($newname)) {
                $r = xaruFindRole($newname);
                if (!$r) $r = xarFindRole($newname);
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
            xarController::redirect(xarModURL('roles', 'admin', 'sitelock'));
        } elseif ($cmd == 'toggle') {

            // turn the site on or off
            $toggle = $toggle ? 0 : 1;

            // Find the users to be notified
            // First get the roles
            $rolesarray = array();
            for($i=0; $i < $rolesCount; $i++) {
                if($roles[$i]['notify'] == 1) {
                    $rolesarray[] = xarRoles::get($roles[$i]['id']);
                }
            }
            //Check each if it is a user or a group
            $notify = array();
            foreach($rolesarray as $roletotell) {
                if ($roletotell->isUser()) $notify[] = $roletotell;
                else $notify = array_merge($notify,$roletotell->getUsers());
            }
            $admin = xarRoles::get(xarModVars::get('roles','admin'));
            $mailinfo = array('subject' => 'Site Lock',
                              'from' => $admin->getEmail()
                            );

// We locked the site
            if ($toggle == 1) {

            // Clear the active sessions
                $spared = array();
                for($i=0; $i < $rolesCount; $i++) $spared[] = $roles[$i]['id'];
                if(!xarMod::apiFunc('roles','admin','clearsessions', $spared)) {
                    $msg = xarML('Could not clear sessions table');
                    throw new Exception($msg);
                }
                $mailinfo['message'] = 'The site ' . xarModVars::get('themes','SiteName') . ' has been locked.';
            } else {
// We unlocked the site
               $mailinfo['message'] = 'The site ' . xarModVars::get('themes','SiteName') . ' has been unlocked.';
            }

            $mailinfo['message'] .= "\n\n" . $notifymsg;

            // Send the mails
            $badmails = 0;
            foreach($notify as $recipient) {
                $mailinfo['info'] = $recipient->getEmail();
                if (!xarMod::apiFunc('mail','admin','sendmail', $mailinfo)) $badmails ++;
            }

            // Write the configuration to disk
            $lockdata = array('roles'     => $roles,
                              'message'   => $lockedoutmsg,
                              'locked'    => $toggle,
                              'notifymsg' => $notifymsg);
            xarModVars::set('roles', 'lockdata', serialize($lockdata));

            if($badmails) {
                return xarTpl::module('roles','user','errors',array('layout' => 'mail_failed', 'badmails' => $badmails));
            }
        }
    }


    $data['roles']        = $roles;
    $data['serialroles']  = xarVarPrepForDisplay(serialize($roles));
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

?>
