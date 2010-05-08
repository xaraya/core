<?php

function sql_210_19()
{
    // Define parameters
    $dynamic_objects = xarDB::getPrefix() . '_dynamic_objects';
    $roles = xarDB::getPrefix() . '_roles';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Remove the roles_roles object and reset the user and group itemtypes
    ");
    $data['reply'] = xarML("
        Done!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        DELETE FROM $dynamic_objects WHERE name = 'roles_roles';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $dynamic_objects SET itemtype = 1 WHERE module_id = 27 AND itemtype = 2;
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $dynamic_objects SET itemtype = 2 WHERE module_id = 27 AND itemtype = 3;
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $roles SET itemtype = 1 WHERE itemtype = 2;
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $roles SET itemtype = 2 WHERE itemtype = 3;
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