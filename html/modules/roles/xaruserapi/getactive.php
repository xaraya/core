<?php
/**
 * Check if a user is active or not on the site
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
 * check if a user is active or not on the site
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param bool $include_anonymous whether or not to include anonymous user
 * @returns array
 * @return array of users, or false on failure
 */
function roles_userapi_getactive($args)
{
    extract($args);

    if (!empty($uid) && !is_numeric($uid)) {
        throw new VariableValidationException(array('uid',$uid,'numeric'));
    }

    if (empty($filter)){
        $filter = time() - (xarConfigGetVar('Site.Session.Duration') * 60);
    }

    $roles = array();

    // Security Check
    if(!xarSecurityCheck('ReadRole')) return;

    // Get database setup
    $dbconn = xarDB::getConn();
    $xartable =& xarDBGetTables();

    $sessioninfoTable = $xartable['session_info'];

    $query = "SELECT role_id
              FROM $sessioninfoTable
              WHERE last_use > ? AND role_id = ?";
    $stmt = $dbconn->prepareStatement($query);
    $bindvars = array((int)$filter,(int)$uid);
    $result = $stmt->executeQuery($bindvars);

    // Put users into result array
    while($result->next()) {
        $uid = $result->fields;
        // FIXME: add some instances here
        if (xarSecurityCheck('ReadRole',0)) {
            $sessions[] = array('uid'       => $uid);
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
