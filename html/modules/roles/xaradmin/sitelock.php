<?php

/**
 * lock the site except for certain groups
function getuid($rolename) {
    echo $r->getName();exit;
    $r = xaruFindRole($rolename);
    if (!$r) $r = xarFindRole($rolename);
    if($r) return $r->getID();
    else return 0
}
 */

function roles_admin_sitelock($args)
{
    // Security Check
    if(!xarSecurityCheck('AdminRole')) return;

    if (!xarVarFetch('cmd', 'str', $cmd, NULL, XARVAR_DONT_SET)) return;
    if(!isset($cmd)) {
    // Get parameters from the db
        $lockvars = unserialize(xarModGetVar('roles','lockdata'));
        $toggle = $lockvars['locked'];
        $roles = $lockvars['roles'];
        $lockedoutmsg = (!isset($lockvars['message']) || $lockvars['message'] == '') ? xarML('The site is currently locked. Thank you for your patience.') : $lockvars['message'];
    }
    else {
    // Get parameters from input
        if (!xarVarFetch('serialroles', 'str', $serialroles, NULL, XARVAR_NOT_REQUIRED)) return;
        $roles = unserialize($serialroles);
        if (!xarVarFetch('lockedoutmsg', 'str', $lockedoutmsg, NULL, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('toggle', 'str', $toggle, NULL, XARVAR_NOT_REQUIRED)) return;

        if ($cmd == 'delete') {
            if (!xarVarFetch('uid', 'int', $uid, NULL, XARVAR_DONT_SET)) return;
            if (isset($uid)) {
                for($i=0;$i<count($roles);$i++) {
                    if ($roles[$i]['uid'] == $uid) {
                        array_splice($roles,$i,1);
                        break;
                    }
                }
            }
        }

        if ($cmd == 'add') {
            if (!xarVarFetch('newname', 'str', $newname, NULL, XARVAR_DONT_SET)) return;
            if (isset($newname)) {
                $r = xaruFindRole($newname);
                if (!$r) $r = xarFindRole($newname);
                if($r) $newuid = $r->getID();
                else $newuid = 0;

                $newelement = array('uid' => $newuid, 'name' => $newname , 'notify' => TRUE);
                if ($newuid != 0 && !in_array($newelement,$roles))
                    $roles[] = $newelement;
            }
        }

        if ($cmd == 'save') {
            foreach($roles as $role) $role['notify'] = TRUE;
            $lockdata = array('roles' => $roles,
                              'message' => $lockedoutmsg,
                              'locked' => $toggle);
            xarModSetVar('roles', 'lockdata', serialize($lockdata));
            xarResponseRedirect(xarModURL('roles', 'admin', 'sitelock'));
        }

        if ($cmd == 'toggle') {
            $toggle = $toggle ? 0 : 1;
        }
    }

        $data['roles'] = $roles;
        $data['serialroles'] = xarVarPrepForDisplay(serialize($roles));
        $data['lockedoutmsg'] = $lockedoutmsg;
        $data['toggle'] = $toggle;
        if ($toggle == 1) {
            $data['togglelabel']    = xarML('Unlock the Site');
            $data['statusmessage']    = xarML('The site is locked');
        }
        else {
            $data['togglelabel']    = xarML('Lock the Site');
            $data['statusmessage']    = xarML('The site is unlocked');
        }
        $data['addlabel']    = xarML('Add a role');
        $data['deletelabel']    = xarML('Remove');
        $data['savelabel']    = xarML('Save the configuration');

    return $data;
}

?>