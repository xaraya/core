<?php

function sql_210_13()
{
    // Define parameters
    $privileges = xarDB::getPrefix() . '_privileges';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Redefining mask names in the Privileges module
    ");
    $data['reply'] = xarML("
        Done!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        UPDATE $privileges SET name = 'EditPrivileges' WHERE name = 'EditPrivilege';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $privileges SET name = 'AddPrivileges' WHERE name = 'AddPrivilege';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $privileges SET name = 'AdminPrivileges' WHERE name = 'AdminPrivilege';
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