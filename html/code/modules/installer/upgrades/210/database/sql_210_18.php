<?php

function sql_210_18()
{
    // Define parameters
    $dynamic_configurations = xarDB::getPrefix() . '_dynamic_configurations';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Adding the 'confirm'configuration option for the email dataproperty
    ");
    $data['reply'] = xarML("
        Done!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        INSERT INTO `xar_dynamic_configurations` (`name`, `description`, `property_id`, `label`, `ignore_empty`, `configuration`) VALUES
        ('validation_email_confirm', 'Show a second email field to be filled in', 14, 'Confirm Email', 1, 'a:1:{s:14:\"display_layout\";s:7:\"default\";}');
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
