<?php
/**
 * Check SQL file
 *
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/200.html
 */

function sql_210_dynamicdata_objects()
{
    // Define parameters
    $table = xarDB::getPrefix() . '_dynamic_objects';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Checking for required dynamicdata objects
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
        `name`
        FROM $table WHERE name ='objects'
        ";
        $result = $dbconn->Execute($data['sql']);
        if ($result->EOF) {
            $data['success'] = false;
            $data['reply'] = xarML("
            Missing dynamic_objects
            ");
        }
        $data['sql'] = "
        SELECT 
        `id`,
        `name`
        FROM $table WHERE name ='properties'
        ";
        $result = $dbconn->Execute($data['sql']);
        if ($result->EOF) {
            $data['success'] = false;
            $data['reply'] = xarML("
            Missing dynamic_properties
            ");
        }
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