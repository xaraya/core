<?php

/**
 * Update a users status
 * @param $args['uname'] is the users system name
 * @param $args['state'] is the new state for the user
 * returns bool
 */
function roles_userapi_updatestatus($args)
{
    extract($args);

    if ((!isset($uname)) ||
        (!isset($state))) {
        $msg = xarML('Invalid Parameter Count',
                      join (', ',$invalid), 'user', 'updatestatus', 'roles');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }

    if (!xarSecurityCheck('ViewRoles')) return;

    // Get DB Set-up
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $rolesTable = $xartable['roles'];

    // Update the status
    $query = "UPDATE $rolesTable
             SET xar_valcode = '',
                 xar_state = '" . xarVarPrepForStore($state) . "'
             WHERE xar_uname = '" . xarVarPrepForStore($uname) . "'";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    return true;
}

?>
