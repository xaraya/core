<?php
/**
 * Delete users based on status
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * delete users based on status
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['state'] state that we are deleting.
 * @return boolean true on success, false on failure
 */
function roles_adminapi_purge(Array $args=array())
{
    // Get arguments
    extract($args);


    if ($state == xarRoles::ROLES_STATE_ACTIVE)
        return xarTpl::module('roles','user','errors',array('layout' => 'purge_active_user'));

    $items = xarMod::apiFunc('roles',
             'user',
             'getall',
             array('state' => $state));

        foreach ($items as $item) {

        // The user API function is called.
        $user = xarMod::apiFunc('roles',
                'user',
                'get',
                array('id' => $item['id']));

        // Security check
        if (!xarSecurityCheck('ManageRoles',0,'Item',"$item[name]::$item[id]")) return;

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