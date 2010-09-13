<?php

function sql_220_02()
{
    // Define parameters
    $dynamic_configurations = xarDB::getPrefix() . '_dynamic_properties_def';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Changing the dynamic_properties_def.modid default value from null to 0
    ");
    $data['reply'] = xarML("
        Done!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
            ALTER TABLE `xar_dynamic_properties_def` CHANGE `modid` `modid` integer unsigned NOT NULL default '0'
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
