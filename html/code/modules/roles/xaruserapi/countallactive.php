<?php
/**
 * Count all active users
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * Count all active users
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param array    $args array of optional parameters<br/>
 *        boolean  $args['include_anonymous'] whether or not to include anonymous user<br/>
 *        string   $args['filter']
 * @return integer the number of users
 */
function roles_userapi_countallactive(Array $args=array())
{
    extract($args);

    if (!isset($include_anonymous)) {
        $include_anonymous = true;
    } else {
        $include_anonymous = (bool) $include_anonymous;
    }

    // Optional arguments.
    if (empty($filter)){
        $filter = time() - (xarConfigVars::get(null, 'Site.Session.Duration') * 60);
    }

    // Security Check
    if(!xarSecurityCheck('ReadRoles')) return;

    // Get database setup
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

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
    // then find the anonymous user's id and add
    // a where clause to the query
    if (!$include_anonymous) {
        $anon = xarMod::apiFunc('roles','user','get',array('uname'=>'anonymous'));
        $query .= " AND a.id != ?";
        $bindvars[] = (int) $anon['id'];
    }
    $query .= " AND itemtype = ?";
    $bindvars[] = xarRoles::ROLES_USERTYPE;

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