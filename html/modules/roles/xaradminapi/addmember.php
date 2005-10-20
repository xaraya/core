<?php
/**
 * File: $Id$
 *
 * Add a user to a group
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
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