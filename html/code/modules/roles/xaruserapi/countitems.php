<?php
/**
 * Utility function to count the number of users
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * utility function to count the number of users
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @returns integer
 * @return number of items held by this module
 */
function roles_userapi_countitems()
{
    // Get database setup
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    $rolestable = $xartable['roles'];

    // Get user
    $query = "SELECT COUNT(1)
            FROM $rolestable";
    $result =& $dbconn->Execute($query);

    // Obtain the number of users
    list($numroles) = $result->fields;

    $result->Close();

    // Return the number of users
    return $numroles;
}

?>
