<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * viewgroup - view users in group
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['pid'] group id
 * @return $users array containing uname, pid
 */
function roles_adminapi_viewgroup($args)
{
    return xarModAPIFunc('roles','user','getusers',$args);

}

?>