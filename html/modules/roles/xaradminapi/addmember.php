<?php
/**
 * Add a user to a group
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * insertuser - add a user to a group
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uid'] user id
 * @param $args['gid'] group id
 * @return true on succes, false on failure
 */
function roles_adminapi_addmember($args)
{
    return xarModAPIFunc('roles','user','addmember',$args);
}

?>
