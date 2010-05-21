<?php

function sql_210_dynamicdata_objects()
{
    // Define parameters
    $table = xarDB::getPrefix() . '_dynamic_objects';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Checking for required dynamicdata objects
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
        `name`
        FROM $table WHERE name ='objects'
        ";
        $result = $dbconn->Execute($data['sql']);
        if ($result->EOF) {
            $data['success'] = false;
            $data['reply'] = xarML("
            Missing dynamic_objects
            ");
        }
        $data['sql'] = "
        SELECT 
        `id`,
        `name`
        FROM $table WHERE name ='properties'
        ";
        $result = $dbconn->Execute($data['sql']);
        if ($result->EOF) {
            $data['success'] = false;
            $data['reply'] = xarML("
            Missing dynamic_properties
            ");
        }
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