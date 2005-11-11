<?php
/**
 * Count all users
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * count all users
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @returns integer
 * @return number of users matching the selection criteria (cfr. getall)
 */
function roles_userapi_countall($args)
{
    extract($args);

    // Security check
    if(!xarSecurityCheck('ReadRole')) return;

    // Get database setup
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $rolestable = $xartable['roles'];

    $bindvars = array();
    if (!empty($state) && is_numeric($state) && $state != ROLES_STATE_CURRENT) {
        $query = "SELECT COUNT(xar_uid) FROM $rolestable WHERE xar_state = ?";
        $bindvars[] = (int) $state;
    } else {
        $query = "SELECT COUNT(xar_uid) FROM $rolestable WHERE xar_state != ?";
        $bindvars[] = ROLES_STATE_DELETED;
    }

    //suppress display of pending users to non-admins
    if (!xarSecurityCheck("AdminRole",0)) $query .= " AND xar_state != " . ROLES_STATE_PENDING;


    if (isset($selection)) $query .= $selection;

    // if we aren't including anonymous in the query,
    // then find the anonymous user's uid and add
    // a where clause to the query
   if (isset($include_anonymous) && !$include_anonymous) {
        $thisrole = xarModAPIFunc('roles','user','get',array('uname'=>'anonymous'));
        $query .= " AND xar_uid != ?";
        $bindvars[] =  (int) $thisrole['uid'];
    }
    if (isset($include_myself) && !$include_myself) {

        $thisrole = xarModAPIFunc('roles','user','get',array('uname'=>'myself'));
        $query .= " AND xar_uid != ?";
        $bindvars[] = (int) $thisrole['uid'];
    }

    $query .= " AND xar_type = " . USERTYPE;

// cfr. xarcachemanager - this approach might change later
    $expire = xarModGetVar('roles','cache.userapi.countall');
    if (!empty($expire)){
        $result = $dbconn->CacheExecute($expire,$query,$bindvars);
    } else {
        $result = $dbconn->Execute($query,$bindvars);
    }
    if (!$result) return;

    // Obtain the number of users
    list($numroles) = $result->fields;

    $result->Close();

    // Return the number of users
    return $numroles;
}

?>
