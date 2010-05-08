<?php

function sql_210_14()
{
    // Define parameters
    $privileges = xarDB::getPrefix() . '_privileges';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Removing old masks from the Privileges module
    ");
    $data['reply'] = xarML("
        Done!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        DELETE FROM $privileges WHERE $privileges.`name` = 'DeletePrivilege';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        DELETE FROM $privileges WHERE $privileges.`name` = 'ViewPrivileges';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        DELETE FROM $privileges WHERE $privileges.`name` = 'AssignPrivilege';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        DELETE FROM $privileges WHERE $privileges.`name` = 'DeassignPrivilege';
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