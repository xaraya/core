<?php
/**
 * File: $Id$
 *
 * Count all users
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * count all users
 * @returns integer
 * @return number of users matching the selection criteria (cfr. getall)
 */
function roles_userapi_countall($args)
{
    extract($args);

    // Security check
    if(!xarSecurityCheck('ReadRole')) return;

    // Get database setup
    list($dbconn) = xarDBGetConn();
    $xartable =& xarDBGetTables();

    $rolestable = $xartable['roles'];

    if (!empty($state) && is_numeric($state)) {
        $query = "SELECT COUNT(xar_uid)
        FROM $rolestable
                WHERE xar_state = " . xarVarPrepForStore($state);
    } else {
        $query = "SELECT COUNT(xar_uid)
        FROM $rolestable
                WHERE xar_state != 0";
    }

    //suppress display of pending users to non-admins
    if (!xarSecurityCheck("AdminRole",0)) $query .= " AND xar_state != 4";


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

    $query .= " AND xar_type = 0";

    $result = $dbconn->Execute($query);
    if (!$result) return;

    // Obtain the number of users
    list($numroles) = $result->fields;

    $result->Close();

    // Return the number of users
    return $numroles;
}

