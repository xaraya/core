<?php

function sql_210_themes()
{
    // Define parameters
    $table = xarDB::getPrefix() . '_themes';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Checking the structure of $table
    ");
    $data['reply'] = xarML("
        Done!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        SELECT 
        `id`,
        `name`,
        `regid`,
        `directory`,
        `version`,
        `state` 
        FROM $table";
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