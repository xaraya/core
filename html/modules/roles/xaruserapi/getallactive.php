<?php
/**
 * File: $Id$
 *
 * Get all active users
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * get all active users
 * @param bool $include_anonymous whether or not to include anonymous user
 * @returns array
 * @return array of users, or false on failure
 */
function roles_userapi_getallactive($args)
{
    extract($args);

    if (!isset($include_anonymous)) {
        $include_anonymous = true;
    } else {
        $include_anonymous = (bool) $include_anonymous;
    }

    // Optional arguments.
    if (!isset($startnum)) {
        $startnum = 1;
    }
    if (!isset($numitems)) {
        $numitems = -1;
    }
    if (!isset($order)) {
        $order = "name";
    }

    if (empty($filter)){
        $filter = time() - (xarConfigGetVar('Site.Session.Duration') * 60);
    }

    $roles = array();

// Security Check
    if(!xarSecurityCheck('ReadRole')) return;

    // Get database setup
    list($dbconn) = xarDBGetConn();
    $xartable =& xarDBGetTables();

    $sessioninfoTable = $xartable['session_info'];
    $rolestable = $xartable['roles'];

    $query = "SELECT a.xar_uid,
                     a.xar_name,
                     a.xar_email,
                     b.xar_ipaddr
              FROM $rolestable a, $sessioninfoTable b
              WHERE a.xar_uid = b.xar_uid AND b.xar_lastused > $filter AND a.xar_uid > 1";

    if (isset($selection)) $query .= $selection;

    // if we aren't including anonymous in the query,
    // then find the anonymous user's uid and add
    // a where clause to the query
    if (!$include_anonymous) {
        $anon = xarModAPIFunc('roles','user','get',array('uname'=>'anonymous'));
        $query .= " AND a.xar_uid != $anon[uid]";
    }
    if (!$include_myself) {
        $thisrole = xarModAPIFunc('roles','user','get',array('uname'=>'myself'));
        $query .= " AND a.xar_uid != $thisrole[uid]";
    }

    $query .= " AND xar_type = 0 ORDER BY xar_" . $order;

    if($startnum==0) $result = $dbconn->Execute($query);
    else $result = $dbconn->SelectLimit($query, $numitems, $startnum-1);
    if (!$result) return;

    // Put users into result array
    for (; !$result->EOF; $result->MoveNext()) {
        list($uid, $name, $email, $ipaddr) = $result->fields;
// FIXME: add some instances here
        if (xarSecurityCheck('ReadRole',0)) {
            $sessions[] = array('uid'       => $uid,
                                'name'      => $name,
                                'email'     => $email,
                                'ipaddr'    => $ipaddr);
        }
    }

    // Return the users

    if (empty($sessions)){
        $sessions = '';
    }

    return $sessions;
}



