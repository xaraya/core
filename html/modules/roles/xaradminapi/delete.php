<?php

/**
 * delete a users item
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
        xarExceptionSet(XAR_SYSTEM_EXCEPTION,
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
        xarExceptionSet(XAR_SYSTEM_EXCEPTION,
                    'ID_NOT_EXIST',
                     new SystemException($msg));
        return false;
    }

// CHECKME: is this correct now ? (tid obviously wasn't)
    // Security check
        if (!xarSecurityCheck('DeleteRole',0,'Item',"$item[name]::$uid")) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    // Get datbase setup
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    $rolestable = $xartable['roles'];

    // Delete the item
    $query = "DELETE FROM $rolestable
            WHERE xar_uid = " . xarVarPrepForStore($uid);
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Let any hooks
    $item['module'] = 'roles';
    $item['itemid'] = $uid;
    xarModCallHooks('item', 'delete', $uid, $item);

    //finished successfully
    return true;
}

?>