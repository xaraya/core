<?php

/**
 * utility function to count the number of items held by this module
 *
 * @author the Example module development team
 * @returns integer
 * @return number of items held by this module
 * @raise DATABASE_ERROR
 */
function roles_userapi_countgroups()
{
    return count(xarModAPIFunc('roles','user','getallgroups'));
}

?>