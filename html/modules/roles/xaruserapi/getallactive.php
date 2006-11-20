<?php
/**
 * Get all active users
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
 * get all active users
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param bool $include_anonymous whether or not to include anonymous user
 * @returns array
 * @return array of users, or false on failure
 */
function roles_userapi_getallactive($args)
{
    // Security Check
    if(!xarSecurityCheck('ViewRoles')) return;

    // Set some defaults
    $include_anonymous = true;
    $startnum = 1; $numitems = -1;
    $order = "name";
    $filter = time() - (xarConfigGetVar('Site.Session.Duration') * 60);

    // See if the arguments said otherwise
    extract($args);

    $include_anonymous = (bool) $include_anonymous;

    // Get database setup
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $sessioninfoTable = $xartable['session_info'];
    $rolestable = $xartable['roles'];

    $bindvars = array();
    $query = "SELECT a.xar_uid,
                     a.xar_uname,
                     a.xar_name,
                     a.xar_email,
                     a.xar_date_reg,
                     b.xar_ipaddr
              FROM $rolestable a, $sessioninfoTable b
              WHERE a.xar_uid = b.xar_uid AND b.xar_lastused > ? AND a.xar_uid > ?";
    $bindvars[] = $filter;
    $bindvars[] = 1;
    if (isset($selection)) $query .= $selection;

    // if we aren't including anonymous in the query,
    // then find the anonymous user's uid and add
    // a where clause to the query
    if (!$include_anonymous) {
        $anon = xarModAPIFunc('roles','user','get',array('uname'=>'anonymous'));
        $query .= " AND a.xar_uid != ?";
        $bindvars[] = (int) $anon['uid'];
    }
    if (!$include_myself) {
        $thisrole = xarModAPIFunc('roles','user','get',array('uname'=>'myself'));
        $query .= " AND a.xar_uid != ?";
        $bindvars[] = (int) $thisrole['uid'];
    }

    $query .= " AND xar_type = ? ORDER BY xar_" . $order;
    $bindvars[] = 0;
    $stmt = $dbconn->prepareStatement($query);

    // cfr. xarcachemanager - this approach might change later
    $expire = xarModGetVar('roles','cache.userapi.getallactive');

    if($startnum > 0) {
        $stmt->setLimit($numitems);
        $stmt->setOffset($startnum - 1);
    }
    $result = $stmt->executeQuery($bindvars);

    // Put users into result array
    $sessions = array();

    while($result->next()) {
        list($uid, $uname, $name, $email, $date_reg, $ipaddr) = $result->fields;
        if (xarSecurityCheck('ViewRoles', 0, 'All', "$uname:All:$uid")) {
            $sessions[] = array('uid'       => (int) $uid,
                                'name'      => $name,
                                'uname'     => $uname,
                                'email'     => $email,
                                'date_reg'  => $date_reg,
                                'ipaddr'    => $ipaddr);
        }
    }
    return $sessions;
}
?>
