<?php
/**
 * File: $Id$
 *
 * Delete users based on status
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * delete users based on status
 * @param $args['state'] state that we are deleting.
 * @returns bool
 * @return true on success, false on failure
 */
function roles_adminapi_purge($args)
{
    // Get arguments
    extract($args);


    if ($state == ROLES_STATE_ACTIVE) {
        $msg = xarML('Cannot Purge Active Users');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION', new SystemException($msg));
        return;
    }

    $items = xarModAPIFunc('roles',
             'user',
             'getall',
             array('state' => $state));

        foreach ($items as $item) {

        // The user API function is called.
        $user = xarModAPIFunc('roles',
                'user',
                'get',
                array('uid' => $item['uid']));

    // Security check
        if (!xarSecurityCheck('DeleteRole',0,'Item',"$item[name]::$item[uid]")) {
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
            return;
        }

        // Call the Roles class
        $roles = new xarRoles();
        $role = $roles->getRole($item['uid']);
        if (!$role->purge()) {
            return;
        }

    // Let any hooks know that we have purged this user.
        $item['module'] = 'roles';
        $item['itemid'] = $item['uid'];
        $item['method'] = 'purge';
        xarModCallHooks('item', 'delete', $uid, $item);
    }

    //finished successfully
    return true;
}

?>