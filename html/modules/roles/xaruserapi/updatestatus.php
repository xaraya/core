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
 * Update a users status
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uname'] is the users system name
 * @param $args['state'] is the new state for the user
 * returns bool
 */
function roles_userapi_updatestatus($args)
{
    extract($args);

    if ((!isset($uname)) ||
        (!isset($state))) {
        $msg = xarML('Invalid Parameter Count');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }

    if (!xarSecurityCheck('ViewRoles')) return;

    // Get DB Set-up
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $rolesTable = $xartable['roles'];

    // Update the status
    $query = "UPDATE $rolesTable
              SET xar_valcode = '', xar_state = ?
              WHERE xar_uname = ?";
    $bindvars = array($state,$uname);

    $result =& $dbconn->Execute($query,$bindvars);
    if (!$result) return;

    return true;
}

?>
