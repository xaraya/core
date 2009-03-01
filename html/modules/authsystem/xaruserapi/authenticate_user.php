<?php
/**
 * Authenticate a user
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * authenticate a user
 * @public
 * @author Marco Canini
 * @param args['uname'] user name of user
 * @param args['pass'] password of user
 * @todo use roles api, not direct db
 * @return int id on successful authentication, XARUSER_AUTH_FAILED otherwise
 */
function authsystem_userapi_authenticate_user($args)
{
    extract($args);

    assert('!empty($uname) && isset($pass)');

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    // Get user information
    $rolestable = $xartable['roles'];
    $query = "SELECT id, pass FROM $rolestable WHERE uname = ?";
    $stmt = $dbconn->prepareStatement($query);

    $result = $stmt->executeQuery(array($uname));

    if (!$result->first()) {
        $result->close();
        return XARUSER_AUTH_FAILED;
    }

    list($id, $realpass) = $result->fields;
    $result->close();

    // Confirm that passwords match
    if (!xarUserComparePasswords($pass, $realpass, $uname, substr($realpass, 0, 2))) {
        return XARUSER_AUTH_FAILED;
    }

    return $id;
}

?>
