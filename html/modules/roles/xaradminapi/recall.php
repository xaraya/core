<?php
/**
 *
 * Recall a user or a group
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
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
//            echo $uname[0];exit;
    $query = "UPDATE $rolestable
            SET xar_uname = '" . xarVarPrepForStore($uname[0]) .
                "', xar_state = " . xarVarPrepForStore($state) ;
    $query .= " WHERE xar_uid = ".xarVarPrepForStore($uid);

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    //finished successfully
    return true;
}

?>
