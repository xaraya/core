<?php

/**
 * viewgroup - view users in group
 * @param $args['pid'] group id
 * @return $users array containing uname, pid
 */
function roles_adminapi_viewgroup($args)
{
    return xarModAPIFunc('roles','user','getusers',$args);

}

?>