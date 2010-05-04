<?php

function sql_210_06()
{
    // Define parameters
    $privileges = xarDB::getPrefix() . '_privileges';
    $privmembers = xarDB::getPrefix() . '_privmembers';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Removing the 'DenyBlocks' privilege
    ");
    $data['reply'] = xarML("
        Done!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        DELETE p, pm FROM $privileges p INNER JOIN $privmembers pm WHERE p.id = pm.privilege_id AND p.name = 'DenyBlocks' AND p.itemtype= 3;
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