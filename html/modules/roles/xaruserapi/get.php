<?php
/**
 * File: $Id$
 *
 * Get a specific user by any of his attributes
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * get a specific user by any of his attributes
 * uname, uid and email are guaranteed to be unique,
 * otherwise the first hit will be returned
 * @param $args['uid'] id of user to get
 * @param $args['uname'] user name of user to get
 * @param $args['name'] name of user to get
 * @param $args['email'] email of user to get
 * @returns array
 * @return user array, or false on failure
 */
function roles_userapi_get($args)
{
    // Get arguments from argument array
    extract($args);
    // Argument checks
    if (empty($uid) && empty($name) && empty($uname) && empty($email)) {
        $msg = xarML('Wrong arguments to roles_userapi_get.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    } elseif (!empty($uid) && !is_numeric($uid)) {
        $msg = xarML('Wrong arguments to roles_userapi_get.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    }

    if (empty($type)){
        $type = 0;
    }
    // Get database setup
    list($dbconn) = xarDBGetConn();
    $xartable =& xarDBGetTables();

    $rolestable = $xartable['roles'];

    // Get user
    // FIXME: Add status field when it appears in roles table
    $query = "SELECT xar_uid,
                   xar_uname,
                   xar_name,
                   xar_email,
                   xar_pass,
                   xar_date_reg,
                   xar_valcode,
                   xar_state
            FROM $rolestable";

    if (!empty($uid) && is_numeric($uid)) {
        $query .= " WHERE xar_uid = " . xarVarPrepForStore($uid);
    } elseif (!empty($name)) {
        $query .= " WHERE xar_name = '" . xarVarPrepForStore($name) . "'";
    } elseif (!empty($uname)) {
        $query .= " WHERE xar_uname = '" . xarVarPrepForStore($uname) . "'";
    } elseif (!empty($email)) {
        $query .= " WHERE xar_email = '" . xarVarPrepForStore($email) . "'";
    }
        $query .= " AND xar_type = " . xarVarPrepForStore($type);

   $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Check for no rows found, and if so return
    if ($result->EOF) {
        return false;
    }

    // Obtain the item information from the result set
    list($uid, $uname, $name, $email, $pass, $date, $valcode, $state) = $result->fields;

    $result->Close();

    // Security check
//    if (xarSecurityCheck('ReadRole',1,'All',"$uname:All:$uid")) return;
//    if (xarSecurityCheck('ViewRoles')) return;

// Create the user array
    $user = array('uid'         => $uid,
                  'uname'       => $uname,
                  'name'        => $name,
                  'email'       => $email,
                  'pass'        => $pass,
                  'date_reg'    => $date,
                  'valcode'     => $valcode,
                  'state'       => $state);

    // Return the user array
    return $user;
}

?>