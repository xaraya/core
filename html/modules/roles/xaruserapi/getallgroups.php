<?php

/**
 * viewallgroups - generate all groups listing.
 * @param none
 * @return groups listing of available groups
 */
function roles_userapi_getallgroups()
{
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $groupstable = $xartable['roles'];

// Security Check
    if(!xarSecurityCheck('ViewRoles')) return;

    $roles = new xarRoles();

    return $roles->getgroups();

}

?>