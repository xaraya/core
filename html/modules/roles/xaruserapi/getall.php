<?php

/**
 * get all users
 * @returns array
 * @return array of users, or false on failure
 */
function roles_userapi_getall($args)
{
    extract($args);

    // Optional arguments.
    if (!isset($startnum)) {
        $startnum = 1;
    }
    if (!isset($numitems)) {
        $numitems = -1;
    }

    $roles = array();

    // Security check
    if(!xarSecurityCheck('ReadRole')) return;

    // Get database setup
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $rolestable = $xartable['roles'];

    if (!empty($state) && is_numeric($state)) {
        $query = "SELECT xar_uid,
                       xar_uname,
                       xar_name,
                       xar_email,
                       xar_state,
               xar_date_reg
        FROM $rolestable
                WHERE xar_state = " . xarVarPrepForStore($state) ."
                AND xar_type = 0
                ORDER BY xar_uname";
    } else {
        $query = "SELECT xar_uid,
                       xar_uname,
                       xar_name,
                       xar_email,
                       xar_state,
               xar_date_reg
        FROM $rolestable
                WHERE xar_state != 0
                AND xar_type = 0
                ORDER BY xar_uname";
    }

    $result = $dbconn->SelectLimit($query, $numitems, $startnum-1);
    if (!$result) return;

    // Put users into result array
    for (; !$result->EOF; $result->MoveNext()) {
        list($uid, $uname, $name, $email, $state, $date_reg) = $result->fields;
        if (xarSecurityCheck('ReadRole',0,'All', "$uname:All:$uid")) {
            $roles[] = array('uid'      => $uid,
                             'uname'    => $uname,
                             'name'     => $name,
                             'email'    => $email,
                             'state'    => $state,
                 'date_reg'   => $date_reg);
        }
    }

    $result->Close();

    // Return the users
    return $roles;
}

?>