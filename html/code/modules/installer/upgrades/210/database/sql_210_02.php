<?php

function sql_210_02()
{
    // Define parameters
    $table = xarDB::getPrefix() . '_modules_vars';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Adding a modvar to hold the number of releases to be shown on the release page
    ");
    $data['reply'] = xarML("
        Done!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        INSERT INTO $table (module_id, name, value)
            SELECT mods.id, 'releasenumber', 10 FROM xar_modules mods
            WHERE mods.name = 'base';
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