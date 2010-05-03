<?php

function sql_210_15()
{
    // Define parameters
    $privileges = xarDB::getPrefix() . '_privileges';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Redefining mask names in the Roles module
    ");
    $data['reply'] = xarML("
        Done!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        UPDATE $privileges SET name = 'ReadRoles' WHERE name = 'ReadRole';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $privileges SET name = 'EditRoles' WHERE name = 'EditRole';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $privileges SET name = 'AddRoles' WHERE name = 'AddRole';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $privileges SET name = 'AdminRoles' WHERE name = 'AdminRole';
        ";
        $dbconn->Execute($data['sql']);
        $dbconn->commit();
    } catch (Exception $e) {
        // Damn
        $dbconn->rollback();
        $data['success'] = false;
        $data['reply'] = xarML("
        Failed!
        ");
    }
    return $data;
}
?>
