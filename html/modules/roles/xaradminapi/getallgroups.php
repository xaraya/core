<?php

/**
 * getallgroups - generate all groups listing.
 * @param none
 * @return groups listing of available groups
 */
function roles_adminapi_getallgroups()
{
// Security Check
	if(!xarSecurityCheck('ViewRoles')) return;

    $groups = xarModAPIFunc('roles','user','getallgroups');
	return $groups;
}


?>