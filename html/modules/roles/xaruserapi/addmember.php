<?php
/**
 * File: $Id$
 *
 * Add a role to a group
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * addmember - add a role to a group
 * @param $args['gid'] group id
 * @param $args['uid'] role id
 * @return true on succes, false on failure
 */
function roles_userapi_addmember($args)
{
    extract($args);

    if((!isset($gid)) || (!isset($uid))) {
        $msg = xarML('groups_userapi_addmember');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    }

// Security Check
    if(!xarSecurityCheck('AddRole')) return;

    $roles = new xarRoles();
    $group = $roles->getRole($gid);
    if($group->isUser()) {
        $msg = xarML('Did not find a group');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    }

    $user = $roles->getRole($uid);

    return $group->addMember($user);
}

?>