<?php
/**
 * File: $Id$
 *
 * View users in a group
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
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