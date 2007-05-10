<?php
/**
 * Count all active users
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
 * Count all active users
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param bool $include_anonymous whether or not to include anonymous user
 * @param string $filter
 * @returns integer
 * @return number of users
 */
function roles_userapi_countallactive($args)
{
    extract($args);

    if (!isset($include_anonymous)) {
        $include_anonymous = true;
    } else {
        $include_anonymous = (bool) $include_anonymous;
    }

    // Optional arguments.
    if (empty($filter)){
        $filter = time() - (xarConfigGetVar('Site.Session.Duration') * 60);
    }

    // Security Check
    if(!xarSecurityCheck('ReadRole')) return;

    // Get database setup
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $sessioninfoTable = $xartable['session_info'];
    $rolestable = $xartable['roles'];

    $bindvars = array();
    $query = "SELECT COUNT(*)
              FROM $rolestable a, $sessioninfoTable b
              WHERE a.id = b.role_id AND b.last_use > ? AND a.id > ?";
    $bindvars[] = $filter;
    $bindvars[] = 1;

    // FIXME: this adds a part to the query which does NOT have bindvars but direct values
    if (isset($selection)) $query .= $selection;
    // TODO: this would be the place to add the bindvars applicable for $selection

    // if we aren't including anonymous in the query,
    // then find the anonymous user's uid and add
    // a where clause to the query
    if (!$include_anonymous) {
        $anon = xarModAPIFunc('roles','user','get',array('uname'=>'anonymous'));
        $query .= " AND a.id != ?";
        $bindvars[] = (int) $anon['uid'];
    }
    if (!$include_myself) {
        $thisrole = xarModAPIFunc('roles','user','get',array('uname'=>'myself'));
        $query .= " AND a.id != ?";
        $bindvars[] = (int) $thisrole['uid'];
    }

    $query .= " AND type = ?";
    $bindvars[] = ROLES_USERTYPE;

// cfr. xarcachemanager - this approach might change later
    $expire = xarModVars::get('roles','cache.userapi.countallactive');
    if (!empty($expire)){
        $result = $dbconn->CacheExecute($expire,$query,$bindvars);
    } else {
        $result = $dbconn->Execute($query,$bindvars);
    }

    // Obtain the number of users
    list($numroles) = $result->fields;

    $result->Close();

    // Return the number of users
    return $numroles;
}

?>
