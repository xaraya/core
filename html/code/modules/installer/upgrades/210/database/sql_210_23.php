<?php

function sql_210_23()
{
    // Define parameters
    $table['block_types'] = xarDB::getPrefix() . '_block_types';
    $table['block_instances'] = xarDB::getPrefix() . '_block_instances';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Removing the now unused block types 'html', 'php', 'text'
    ");
    $data['reply'] = xarML("
        Done!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        DELETE FROM $table[block_types] WHERE name = 'html';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $table[block_instances] SET type_id = 
            (SELECT id FROM $table[block_types] WHERE name = 'content') WHERE type_id = 
            (SELECT id FROM $table[block_types] WHERE name = 'php')        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $table[block_instances] SET type_id = 
            (SELECT id FROM $table[block_types] WHERE name = 'content') WHERE type_id = 
            (SELECT id FROM $table[block_types] WHERE name = 'text')        ";
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