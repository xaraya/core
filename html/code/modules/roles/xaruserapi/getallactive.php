<?php
/**
 * Get all active users
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * get all active users
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param array    $args array of optional parameters<br/>
 *        boolean  $args['include_anonymous'] whether or not to include anonymous user
 * @return mixed array of users, or false on failure
 */
function roles_userapi_getallactive(Array $args=array())
{
    // Security Check
    if(!xarSecurityCheck('ViewRoles')) return;

    // Set some defaults
    $include_anonymous = true;
    $startnum = 1; $numitems = -1;
    $order = "name";
    $filter = time() - (xarConfigVars::get(null, 'Site.Session.Duration') * 60);

    // See if the arguments said otherwise
    extract($args);

    $include_anonymous = (bool) $include_anonymous;

    // Get database setup
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    $sessioninfoTable = $xartable['session_info'];
    $rolestable = $xartable['roles'];

    $bindvars = array();
    $query = "SELECT a.id,
                     a.uname,
                     a.name,
                     a.email,
                     a.date_reg,
                     b.ip_addr
              FROM $rolestable a, $sessioninfoTable b
              WHERE a.id = b.role_id AND b.last_use > ?";
    $bindvars[] = $filter;
    if (isset($selection)) $query .= $selection;

    // if we aren't including anonymous in the query,
    // then find the anonymous user's id and add
    // a where clause to the query
    if (!$include_anonymous) {
        $anon = xarMod::apiFunc('roles','user','get',array('uname'=>'anonymous'));
        $query .= " AND a.id != ?";
        $bindvars[] = (int) $anon['id'];
    }

    $query .= " AND itemtype = ? ORDER BY " . $order;
    $bindvars[] = xarRoles::ROLES_USERTYPE;
    $stmt = $dbconn->prepareStatement($query);

    // cfr. xarcachemanager - this approach might change later
    $expire = xarModVars::get('roles','cache.userapi.getallactive');

    if($startnum > 0) {
        $stmt->setLimit($numitems);
        $stmt->setOffset($startnum - 1);
    }
    $result = $stmt->executeQuery($bindvars);

    // Put users into result array
    $sessions = array();

    while($result->next()) {
        list($id, $uname, $name, $email, $date_reg, $ipaddr) = $result->fields;
        if (xarSecurityCheck('ViewRoles', 0, 'All', "$uname:All:$id")) {
            $sessions[] = array('id'       => (int) $id,
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