<?php
/**
 * Check if a user is active or not on the site
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
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
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $sessioninfoTable = $xartable['session_info'];

    $query = "SELECT xar_uid
              FROM $sessioninfoTable
              WHERE xar_lastused > ? AND xar_uid = ?";
    $bindvars = array((int)$filter,(int)$uid);
    $result =& $dbconn->Execute($query,$bindvars);
    if (!$result) return;

    // Put users into result array
    for (; !$result->EOF; $result->MoveNext()) {
        $uid = $result->fields;
    // FIXME: add some instances here
        if (xarSecurityCheck('ReadRole',0)) {
            $sessions[] = array('uid'       => $uid);
        }
    }

    $result->Close();

    // Return the users
    if (empty($sessions)){
        $sessions = '';
    }

    return $sessions;
}
?>
