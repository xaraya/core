<?php

/**
 * Update a user's core info

 * @param $args['uid'] user ID
 * @param $args['name'] user real name
 * @param $args['uname'] user nick name
 * @param $args['email'] user email address
 * @param $args['pass'] user password
 * TODO: move url to dynamic user data
 *       replace with status
 * @param $args['url'] user url
 */
function roles_adminapi_stateupdate($args)
{
    extract($args);

    // Argument check - make sure that all required arguments are present,
    // if not then set an appropriate error message and return
    if ((!isset($uid)) ||
        (!isset($state))) {
        $msg = xarML('Invalid Parameter Count',
                    join(', ',$invalid), 'admin', 'update', 'Users');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

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

//    if (!xarSecAuthAction(0, 'roles::Item', "$item[uname]::$uid", ACCESS_EDIT)) {
//        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
//        return;
//    }

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $rolesTable = $xartable['roles'];

    $query = "UPDATE $rolesTable
            SET xar_state = '" . xarVarPrepForStore($state) . "'
            WHERE xar_uid = " . xarVarPrepForStore($uid);

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    return true;
}

?>