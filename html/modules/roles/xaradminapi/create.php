<?php
/**
 * File: $Id$
 *
 * Create a user
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * create a user
 * @param $args['uname'] username of the user
 * @param $args['realname'] real name of the user
 * @param $args['email'] email address of the user
 * @param $args['pass'] password of the user
 * @param $args['date'] registration date
 * @param $args['valcode'] validation code
 * @param $args['state'] state of the account
 * @param $args['authmodule'] authentication module
 * @param $args['uid'] user id to be used (import only)
 * @param $args['cryptpass'] encrypted password to be used (import only)
 * @returns int
 * @return user ID on success, false on failure
 */

function roles_adminapi_create($args)
{
    // Get arguments
    extract($args);

    $invalid = array();
    if (!isset($uname)) {
        $invalid[] = 'uname';
    } 
    if (!isset($email)) {
        $invalid[] = 'email';
    } 
    if (!isset($realname)) {
        $invalid[] = 'realname';
    } 
    if (!isset($state)) {
        $invalid[] = 'state';
    } 
    if (!isset($pass)) {
        $invalid[] = 'pass';
    } 
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)', 
            join(', ', $invalid), 
            'admin', 'create', 'roles');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    } 

    // Get datbase setup
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $rolestable = $xartable['roles'];

    // Check if that username exists
    $query = "SELECT xar_uid FROM $rolestable
            WHERE xar_uname= ? AND xar_type = 0";
    $result =& $dbconn->Execute($query,array($uname));
    if (!$result) return;

    if ($result->RecordCount() > 0) {
        return 0;  // username is already there
    }

    // Get next ID in table
    if (empty($uid) || !is_numeric($uid)) {
        $nextId = $dbconn->GenId($rolestable);
    } else {
        $nextId = $uid;
    }

// TODO: check this
    if (empty($authmodule)) {
        $authmodule = 'authsystem';
    }

    // Add item, with encrypted passwd

    if (empty($cryptpass)) {
        $cryptpass=md5($pass);
    }

    // Put registratation date in timestamp format
    //$date_reg = $dbconn->DBTimeStamp($date);
    $date_reg = $date;

    // TODO: for now, convert the timestamp to a simple string since we are
    // storing the date in a varchar field
    //$date_reg = trim($date_reg,"'");

    $query = "INSERT INTO $rolestable (
              xar_uid, xar_uname, xar_name, xar_type,
              xar_pass, xar_email, xar_date_reg, xar_valcode,
              xar_state, xar_auth_module
              )
            VALUES (?,?,?,?,?,?,?,?,?,?)";
    $bindvars = array($nextId, $uname, $realname, 0,
                      $cryptpass,$email,$date_reg,$valcode,
                      $state,$authmodule);
    $result =& $dbconn->Execute($query,$bindvars);
    if (!$result) return;

    // Get the ID of the user that we created.
    if (empty($uid) || !is_numeric($uid)) {
        $uid = $dbconn->PO_Insert_ID($rolestable, 'xar_uid');
    }

    // Let any hooks know that we have created a new user.
    $item['module'] = 'roles';
    $item['itemid'] = $uid;
    xarModCallHooks('item', 'create', $uid, $item);

    // Return the id of the newly created user to the calling process
    return $uid;
}

?>