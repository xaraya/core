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

function roles_admin_lockout($args)
{
    // Security Check
    if(!xarSecurityCheck('AdminRole')) return;

    // Get parameters from whatever input we need
    if (!xarVarFetch('newname', 'str', $newname, NULL, XARVAR_DONT_SET)) return;
    if (isset($newname)) {
        if (!xarVarFetch('serialroles', 'str', $serialroles, NULL, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('lockedoutmsg', 'str', $data['lockedoutmsg'], NULL, XARVAR_NOT_REQUIRED)) return;
        $roles = unserialize($serialroles);
        $r = xaruFindRole($newname);
        if (!$r) $r = xarFindRole($newname);
        if($r) $newuid = $r->getID();
        else $newuid = 0;

        $newelement = array('uid' => $newuid, 'name' => $newname );
        if ($newuid != 0 && !in_array($newelement,$roles))
            $roles[] = $newelement;
        $data['roles'] = $roles;
        $data['serialroles'] = xarVarPrepForDisplay(serialize($roles));
        $toggle = 0;
    }
    else {
        if (!xarVarFetch('toggle', 'int:1', $toggle, NULL, XARVAR_DONT_SET)) return;

        if (!isset($toggle)) {
            $lockvars = unserialize(xarModGetVar('roles','lockdata'));
            $data['lockedoutmsg'] = (!isset($lockvars['message']) && $lockvars['message'] != '') ? xarML('The site is currently locked. Thank you for your patience.') : $lockvars['message'];
            $toggle = $lockvars['locked'];
            $data['roles'] = $lockvars['roles'];
            $data['serialroles'] = xarVarPrepForDisplay(serialize($lockvars['roles']));
        }
        else {
            if (!xarVarFetch('roles', 'str', $roles, NULL, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('serialroles', 'str', $serialroles, NULL, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('lockedoutmsg', 'str', $data['lockedoutmsg'], NULL, XARVAR_NOT_REQUIRED)) return;
            $lockdata = array('roles' => $roles,
                              'message' => $data['lockedoutmsg'],
                              'locked' => $toggle);
    //        xarModSetVar('roles', 'lockdata', serialize($lockdata));
            $data['roles'] = unserialize($serialroles);
            $data['serialroles'] = xarVarPrepForDisplay($serialroles);
        }

    }
        $toggle = $toggle ? 0 : 1;
        $data['toggle'] = $toggle;
        if ($toggle == 1) {
            $data['submitlabel']    = xarML('Unlock the Site');
            $data['statusmessage']    = xarML('The site is locked');
        }
        else {
            $data['submitlabel']    = xarML('Lock the Site');
            $data['statusmessage']    = xarML('The site is unlocked');
        }
        $data['addlabel']    = xarML('Add a role');
        $data['deletelabel']    = xarML('Remove');

    return $data;
}
?>