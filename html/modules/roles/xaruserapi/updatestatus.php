<?php
/**
 * Update a users status
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
 * Update a users status
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uname'] is the users system name
 * @param $args['state'] is the new state for the user
 * returns bool
 */
function roles_userapi_updatestatus($args)
{
    extract($args);

    if (!isset($uname)) throw new EmptyParameterException('uname');
    if (!isset($state)) throw new EmptyParameterException('state');

    if (!xarSecurityCheck('ViewRoles')) return;

    // Get DB Set-up
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $rolesTable = $xartable['roles'];

    // Update the status
    $query = "UPDATE $rolesTable
              SET xar_valcode = ?, xar_state = ?
              WHERE xar_uname = ?";
    $bindvars = array('',$state,$uname);

    $dbconn->Execute($query,$bindvars);

    return true;
}

?>
