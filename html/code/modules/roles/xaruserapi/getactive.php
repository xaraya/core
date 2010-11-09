<?php
/**
 * Check if a user is active or not on the site
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * check if a user is active or not on the site
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param bool $include_anonymous whether or not to include anonymous user
 * @return mixed array of users, or false on failure
 */
function roles_userapi_getactive(Array $args=array())
{
    extract($args);

    if (!empty($id) && !is_numeric($id)) {
        throw new VariableValidationException(array('id',$id,'numeric'));
    }

    if (empty($filter)){
        $filter = time() - (xarConfigVars::get(null, 'Site.Session.Duration') * 60);
    }

    $roles = array();

    // Security Check
    if(!xarSecurityCheck('ReadRoles')) return;

    // Get database setup
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    $sessioninfoTable = $xartable['session_info'];

    $query = "SELECT role_id
              FROM $sessioninfoTable
              WHERE last_use > ? AND role_id = ?";
    $stmt = $dbconn->prepareStatement($query);
    $bindvars = array((int)$filter,(int)$id);
    $result = $stmt->executeQuery($bindvars);

    // Put users into result array
    while($result->next()) {
        $id = $result->fields;
        // FIXME: add some instances here
        if (xarSecurityCheck('ReadRoles',0)) {
            $sessions[] = array('id'       => $id);
        }
    }
    $result->close();

    // Return the users
    if (empty($sessions)){
        $sessions = '';
    }

    return $sessions;
}
?>