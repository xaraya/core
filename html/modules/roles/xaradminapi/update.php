<?php
/**
 * File: $Id$
 *
 * Update a user's core info
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
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
function roles_adminapi_update($args)
{
    extract($args);

    // Argument check - make sure that all required arguments are present,
    // if not then set an appropriate error message and return
    if ((!isset($uid)) ||
        (!isset($name)) ||
        (!isset($uname)) ||
        (!isset($email)) ||
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

    if (empty($valcode)) {
        $valcode = '';
    }

//    if (!xarSecAuthAction(0, 'roles::Item', "$item[uname]::$uid", ACCESS_EDIT)) {
//        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
//        return;
//    }

    list($dbconn) = xarDBGetConn();
    $xartable =& xarDBGetTables();

    $rolesTable = $xartable['roles'];

    if (!empty($pass)){
        $cryptpass=md5($pass);
        $query = "UPDATE $rolesTable
                SET xar_name    =  '" . xarVarPrepForStore($name) . "',
                    xar_uname   = '" . xarVarPrepForStore($uname) . "',
                    xar_email   = '" . xarVarPrepForStore($email) . "',
                    xar_pass    = '" . xarVarPrepForStore($cryptpass) . "',
                    xar_valcode = '" . xarVarPrepForStore($valcode) . "',
                    xar_state   = '" . xarVarPrepForStore($state) . "'
                WHERE xar_uid   = " . xarVarPrepForStore($uid);
    } else {
        $query = "UPDATE $rolesTable
                SET xar_name    =  '" . xarVarPrepForStore($name) . "',
                    xar_uname   = '" . xarVarPrepForStore($uname) . "',
                    xar_email   = '" . xarVarPrepForStore($email) . "',
                    xar_valcode = '" . xarVarPrepForStore($valcode) . "',
                    xar_state   = '" . xarVarPrepForStore($state) . "'
                WHERE xar_uid   = " . xarVarPrepForStore($uid);
    }

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $item['module'] = 'roles';
    $item['itemid'] = $uid;
    $item['name'] = $name;
    $item['uname'] = $uname;
    $item['email'] = $email;
    xarModCallHooks('item', 'update', $uid, $item);

    return true;
}

?>
