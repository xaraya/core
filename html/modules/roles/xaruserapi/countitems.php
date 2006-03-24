<?php
/**
 * Utility function to count the number of users
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
 * utility function to count the number of users
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @returns integer
 * @return number of items held by this module
 */
function roles_userapi_countitems()
{
    // Get database setup
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $rolestable = $xartable['roles'];

    // Get user
    $query = "SELECT COUNT(1)
            FROM $rolestable";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Obtain the number of users
    list($numroles) = $result->fields;

    $result->Close();

    // Return the number of users
    return $numroles;
}

?>