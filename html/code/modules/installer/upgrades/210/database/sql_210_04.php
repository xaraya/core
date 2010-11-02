<?php
/**
 * @package installer
 * @subpackage installer module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @link http://xaraya.com/index.php/release/200.html
 */

function sql_210_04()
{
    // Define parameters
    $dynamic_configurations = xarDB::getPrefix() . '_dynamic_configurations';
    $dynamic_properties = xarDB::getPrefix() . '_dynamic_properties';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Changing the name of the 'installation_rows' configuration to 'installation_addremove'
    ");
    $data['reply'] = xarML("
        Done!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        UPDATE $dynamic_configurations SET `name` = REPLACE(name, \"installation_rows\", \"installation_addremove\");
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $dynamic_configurations SET `description` = REPLACE(description, \"Allow adding of rows\", \"Allow adding/removing of items\");
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $dynamic_configurations SET `label` = REPLACE(label, \"Add/Delete Rows\", \"Add/Remove Items\");
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        UPDATE $dynamic_properties SET `configuration` = REPLACE(configuration, 's:19:\"initialization_rows\";', 's:24:\"initialization_addremove\";');
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