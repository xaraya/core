<?php
/**
 * Test a user or group's privileges against a mask
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * create a user
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uname'] username of the user
 * @param $args['name'] real name of the user
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

    if (!isset($uname)) throw new EmptyParameterException('uname');
    if (!isset($email)) throw new EmptyParameterException('email');
    if (!isset($name)) throw new EmptyParameterException('name');
    if (!isset($state)) throw new EmptyParameterException('state');
    if (!isset($pass)) throw new EmptyParameterException('pass');

    // Get datbase setup
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $rolestable = $xartable['roles'];

    // Check if that username exists
    $query = "SELECT xar_uid FROM $rolestable WHERE xar_uname= ? AND xar_type = ?";
    $result =& $dbconn->Execute($query,array($uname,0));

    if ($result->getRecordCount() > 0) {
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
        $modInfo = xarMod_GetBaseInfo('authsystem');
        $modId = $modInfo['systemid'];
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
              xar_state, xar_auth_modid
              )
            VALUES (?,?,?,?,?,?,?,?,?,?)";
    $bindvars = array($nextId, $uname, $name, 0,
                      $cryptpass,$email,$date_reg,$valcode,
                      $state,$modId);
    $result = $dbconn->Execute($query,$bindvars);
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
