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
    if (!isset($order)){
        $order = 'name';
    }
    if (!isset($startat)) {
        $startat = 1;
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
                WHERE xar_state = " . xarVarPrepForStore($state);
    } else {
        $query = "SELECT xar_uid,
                       xar_uname,
                       xar_name,
                       xar_email,
                       xar_state,
               xar_date_reg
        FROM $rolestable
                WHERE xar_state != 0 ";
    }

    if (isset($selection)) $query .= $selection;

    // if we aren't including anonymous in the query,
    // then find the anonymous user's uid and add
    // a where clause to the query
   if (isset($include_anonymous) && !$include_anonymous) {
        $thisrole = xarModAPIFunc('roles','user','get',array('uname'=>'anonymous'));
        $query .= " AND xar_uid != $thisrole[uid]";
    }
    if (isset($include_myself) && !$include_myself) {

        $thisrole = xarModAPIFunc('roles','user','get',array('uname'=>'myself'));
        $query .= " AND xar_uid != $thisrole[uid]";
    }

    $query .= " AND xar_type = 0 ORDER BY xar_" . $order;

    if($startat==0) $result = $dbconn->Execute($query);
    else $result = $dbconn->SelectLimit($query, $numitems, $startat-1);
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

    // Return the users
    return $roles;
}

?>