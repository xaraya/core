<?php

function sql_210_hooks()
{
    // Define parameters
    $table = xarDB::getPrefix() . '_hooks';

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
        `object`,
        `action`,
        `s_module_id`,
        `s_type`,
        `t_area`,
        `t_module_id`,
        `t_type`,
        `t_func`,
        `t_file`,
        `priority`
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