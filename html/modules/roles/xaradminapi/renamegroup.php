<?php
/**
 * File: $Id$
 *
 * Rename a group
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * renamegroup - rename a group
 * @param $args['pid'] group id
 * @param $args['gname'] group name
 * @return true on success, false on failure.
 */
function roles_adminapi_renamegroup($args)
{
    extract($args);

    if((!isset($pid)) || (!isset($gname))) {
        $msg = xarML('groups_adminapi_renamegroup');
        xarErrorSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    }

// Security Check
    if(!xarSecurityCheck('EditRole')) return;

    $roles = new xarRoles();
    $role = $roles->getRole($uid);
    $role->setName($gname);

    return $role->update();
}

?>