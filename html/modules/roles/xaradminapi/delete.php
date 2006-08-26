<?php
/**
 * Delete a users item
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
 * delete a users item
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uid'] ID of the item
 * @returns bool
 * @return true on success, false on failure
 */
function roles_adminapi_delete($args)
{
    // Get arguments
    extract($args);

    // Argument check
    if (!isset($uid)) throw new EmptyParameterException('uid');

    // The user API function is called.
    $item = xarModAPIFunc('roles', 'user', 'get', array('uid' => $uid));

    if ($item == false) throw new IDNotFoundException($uid,'User with id "#(1)" could not be found');

    // Security check
    if (!xarSecurityCheck('DeleteRole',0,'Item',"$item[name]::$uid")) return;

    // Get datbase setup
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $rolestable = $xartable['roles'];

    // Delete the item
    $query = "DELETE FROM $rolestable WHERE xar_uid = ?";
    $dbconn->Execute($query,array($uid));

    // Let any hooks know that we have deleted this user.
    $item['module'] = 'roles';
    $item['itemid'] = $uid;
    $item['method'] = 'delete';
    xarModCallHooks('item', 'delete', $uid, $item);

    //finished successfully
    return true;
}

?>
