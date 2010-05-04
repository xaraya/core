<?php

function sql_210_20()
{
    // Define parameters
    $module_vars = xarDB::getPrefix() . '_module_vars';
    $roles = xarDB::getPrefix() . '_roles';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Add the version build configuration variable
    ");
    $data['reply'] = xarML("
        Done!
    ");

    // Run the query
    try {
        xarConfigVars::set(null, 'System.Core.VersionRev', xarCore::VERSION_REV);
    } catch (Exception $e) {
        // Damn
        $data['success'] = false;
        $data['reply'] = xarML("
        Failed!
        ");
    }
    return $data;
}
?>