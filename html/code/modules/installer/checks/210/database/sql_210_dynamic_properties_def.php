<?php
/**
 * Check SQL file
 *
 * @package modules
 * @subpackage installer module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @link http://xaraya.info/index.php/release/200.html
 */

function sql_210_dynamic_properties_def()
{
    // Define parameters
    $table = xarDB::getPrefix() . '_dynamic_properties_def';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Checking the structure of $table
    ");
    $data['reply'] = xarML("
        Success!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        SELECT 
        `id`,
        `name`,
        `label`,
        `filepath`,
        `class`,
        `configuration`,
        `source`,
        `reqfiles`,
        `modid`,
        `args`,
        `aliases`,
        `format`
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