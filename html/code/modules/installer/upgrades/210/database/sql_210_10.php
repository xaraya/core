<?php
/**
 * Upgrade SQL file
 *
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

function sql_210_10()
{
    // Define parameters
    $modules = xarDB::getPrefix() . '_modules';
    $privileges = xarDB::getPrefix() . '_privileges';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Adding new names for the masks of the Blocks module
    ");
    $data['reply'] = xarML("
        Success!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        INSERT INTO $privileges (name,  module_id, component, instance, level, description, itemtype)  
            SELECT 'ActivateBlocks',  m.id, 'All', 'All', 400, 'Activate mask for blocks module',3 FROM $modules m WHERE name = 'blocks';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        INSERT INTO $privileges (name,  module_id, component, instance, level, description, itemtype)  
            SELECT 'EditBlocks',  m.id, 'All', 'All', 500, 'Edit mask for blocks module',3 FROM $modules m WHERE name = 'blocks';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        INSERT INTO $privileges (name,  module_id, component, instance, level, description, itemtype)  
            SELECT 'AddBlocks',  m.id, 'All', 'All', 600, 'Add mask for blocks module',3 FROM $modules m WHERE name = 'blocks';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        INSERT INTO $privileges (name,  module_id, component, instance, level, description, itemtype)  
            SELECT 'AdminBlocks',  m.id, 'All', 'All', 800, 'Admin mask for blocks module',3 FROM $modules m WHERE name = 'blocks';
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
