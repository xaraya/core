<?php

/**
 * utility function to count the number of users
 * @returns integer
 * @return number of items held by this module
 */
function roles_userapi_countitems()
{
    // Get database setup
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

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