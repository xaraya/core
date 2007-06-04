<?php
/**
 * Delete users based on status
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * delete users based on status
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['state'] state that we are deleting.
 * @returns bool
 * @return true on success, false on failure
 */
function roles_adminapi_purge($args)
{
    // Get arguments
    extract($args);


    if ($state == ROLES_STATE_ACTIVE) {
        throw new ForbiddenOperation(null,'Purging active users is not allowed');
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
                array('id' => $item['id']));

        // Security check
        if (!xarSecurityCheck('DeleteRole',0,'Item',"$item[name]::$item[id]")) return;

        // Call the Roles class
        $role = xarRoles::get($item['id']);
        if (!$role->purge()) {
            return;
        }

    // Let any hooks know that we have purged this user.
        $item['module'] = 'roles';
        $item['itemid'] = $item['id'];
        $item['method'] = 'purge';
        xarModCallHooks('item', 'delete', $id, $item);
    }

    //finished successfully
    return true;
}

?>
