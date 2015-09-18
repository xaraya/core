<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/200.html
 */

function sql_220_12()
{
    // Define parameters
    $table = xarDB::getPrefix() . '_dynamic_properties';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Redefining the configuration property of the objects object
    ");
    $data['reply'] = xarML("
        Success!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        UPDATE $table SET `configuration` = 'a:6:{s:20:\"display_minimum_rows\";s:1:\"1\";s:20:\"display_maximum_rows\";s:2:\"10\";s:25:\"display_column_definition\";a:1:{s:5:\"value\";a:4:{i:0;a:2:{i:0;s:3:\"Key\";i:1;s:5:\"Value\";}i:1;a:2:{i:0;s:1:\"2\";i:1;s:1:\"2\";}i:2;a:2:{i:0;s:0:\"\";i:1;s:0:\"\";}i:3;a:2:{i:0;s:0:\"\";i:1;s:0:\"\";}}}s:14:\"display_layout\";s:7:\"default\";s:28:\"validation_associative_array\";s:1:\"1\";s:24:\"initialization_addremove\";s:1:\"2\";}' WHERE `object_id` = 1;
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