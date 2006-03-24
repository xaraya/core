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
    if (!isset($uid)) {
        $msg = xarML('Wrong arguments to roles_adminapi_delete.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    }

    // The user API function is called.
    $item = xarModAPIFunc('roles',
            'user',
            'get',
            array('uid' => $uid));

    if ($item == false) {
        $msg = xarML('No such user','roles');
        xarErrorSet(XAR_SYSTEM_EXCEPTION,
                    'ID_NOT_EXIST',
                     new SystemException($msg));
        return false;
    }

// CHECKME: is this correct now ? (tid obviously wasn't)
    // Security check
        if (!xarSecurityCheck('DeleteRole',0,'Item',"$item[name]::$uid")) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    // Get datbase setup
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $rolestable = $xartable['roles'];

    // Delete the item
    $query = "DELETE FROM $rolestable WHERE xar_uid = ?";
    $result =& $dbconn->Execute($query,array($uid));
    if (!$result) return;

    // Let any hooks know that we have deleted this user.
    $item['module'] = 'roles';
    $item['itemid'] = $uid;
    $item['method'] = 'delete';
    xarModCallHooks('item', 'delete', $uid, $item);

    //finished successfully
    return true;
}

?>