<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uid'] uid of the role that is being called
 * @returns bool
 * @return true on success, false on failure
 */
function roles_adminapi_recall($args)
{
    // Get arguments
    extract($args);

    if (!isset($uid) || $uid == 0) {
        $msg = xarML('The user to be recalled does not exist or is not deleted');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION', new SystemException($msg));
        return;
    }
    if (!isset($state) || $state == 0) {
        $msg = xarML('The state to be recalled to is missing or 0');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION', new SystemException($msg));
        return;
    }

    // Get database setup
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $rolestable = $xartable['roles'];

    $deleted = '[' . xarML('deleted') . ']';

    $roles = new xarRoles();
    $role = $roles->getRole($uid);
    $uname = explode($deleted,$role->getUser());
    $email = explode($deleted,$role->getEmail());
//            echo $uname[0];exit;
    $query = "UPDATE $rolestable
              SET xar_uname = ?, xar_email = ?, xar_state = ?
              WHERE xar_uid = ?";
    $bindvars = array($uname[0],$email[0],$state,$uid);
    $result =& $dbconn->Execute($query,$bindvars);
    if (!$result) return;

    // Let any hooks know that we have recalled this user.
    $item['module'] = 'roles';
    $item['itemid'] = $uid;
    $item['method'] = 'recall';
    xarModCallHooks('item', 'create', $uid, $item);

    //finished successfully
    return true;
}

?>
