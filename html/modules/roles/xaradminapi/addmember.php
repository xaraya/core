<?php

/**
 * insertuser - add a user to a group
 * @param $args['uid'] user id
 * @param $args['gid'] group id
 * @return true on succes, false on failure
 */
function roles_adminapi_addmember($args)
{
    return xarModAPIFunc('roles','user','addmember',$args);
}

?>