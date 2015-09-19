<?php
/**
 * Authenticate a user
 *
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/42.html
 */

/**
 * Authenticate a user
 * 
 * @author Marco Canini
 * @param  string[] $args Array of optional parameters<br/>
 *         string   $args['uname'] user name of user<br/>
 *         string   $args['pass'] password of user
 * @return int Returns user id on successful authentication, XARUSER_AUTH_FAILED otherwise
 */
function authsystem_userapi_authenticate_user(Array $args=array())
{
    /** 
     * Pending
     * @todo use roles api, not direct db
     */
    
    extract($args);

    assert('!empty($uname) && isset($pass)');

    $dbconn = xarDB::getConn();
    $xartable =& xarDB::getTables();

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
