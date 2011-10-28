<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

function sql_220_17()
{
    // Define parameters
    $table = xarDB::getPrefix() . '_dynamic_properties';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Update the configurations table
    ");
    $data['reply'] = xarML("
        Success!
    ");
    
    // Run the task
    $dbconn  = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
INSERT INTO `xar_dynamic_configurations` VALUES(143, 'display_show_salutation', 'Display the salutation part of a name', 14, 'Display Salutation', 1, 'a:1:{s:14:\"display_layout\";s:7:\"default\";}');
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
INSERT INTO `xar_dynamic_configurations` VALUES(144, 'display_show_firstname', 'Display the first name part of a name', 14, 'Display First Name', 1, 'a:1:{s:14:\"display_layout\";s:7:\"default\";}');
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
INSERT INTO `xar_dynamic_configurations` VALUES(145, 'display_show_middlename', 'Display the middle name part of a name', 14, 'Display Middle Name', 1, 'a:1:{s:14:\"display_layout\";s:7:\"default\";}');
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
INSERT INTO `xar_dynamic_configurations` VALUES(146, 'initialization_encrypt', 'Encrypt this value', 14, 'Encryption', 1, 'a:1:{s:14:\"display_layout\";s:7:\"default\";}');
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
INSERT INTO `xar_dynamic_configurations` VALUES(147, 'initialization_hash_type', 'The hash algorithm used for encrypting passwords', 2, 'Hash Type', 1, 'a:1:{s:14:\"display_layout\";s:7:\"default\";}');
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
INSERT INTO `xar_dynamic_configurations` VALUES(148, 'display_column_definition', 'Changing definition of row', 999, 'Column Definition', 1, 'a:8:{s:15:\"display_columns\";s:2:\"30\";s:21:\"display_columns_count\";s:1:\"1\";s:12:\"display_rows\";s:1:\"4\";s:25:\"display_column_definition\";a:2:{s:13:\"configuration\";a:3:{s:25:\"display_column_definition\";a:2:{i:0;a:4:{i:0;s:5:\"Title\";i:1;s:4:\"Type\";i:2;s:13:\"Default Value\";i:3;s:13:\"Configuration\";}i:1;a:4:{i:0;s:7:\"textbox\";i:1;s:9:\"fieldtype\";i:2;s:7:\"textbox\";i:3;s:8:\"textarea\";}}s:12:\"display_rows\";i:4;s:14:\"display_layout\";s:13:\"configuration\";}s:8:\"recursed\";i:1;}s:14:\"display_layout\";s:7:\"default\";s:32:\"initialization_associative_array\";s:1:\"1\";s:24:\"initialization_prop_type\";s:9:\"fieldtype\";s:26:\"initialization_prop_config\";s:0:\"\";}');
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
INSERT INTO `xar_dynamic_configurations` VALUES(149, 'display_minimum_rows', 'The minimum number of rows of this property displayed', 15, 'Minimum Rows', 1, 'a:3:{s:12:\"display_size\";s:2:\"10\";s:17:\"display_maxlength\";s:2:\"30\";s:14:\"display_layout\";s:7:\"default\";}');
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
INSERT INTO `xar_dynamic_configurations` VALUES(150, 'display_maximum_rows', 'The maximum number of rows of this property displayed', 15, 'Maximum Rows', 1, 'a:1:{s:14:\"display_layout\";s:7:\"default\";}');
        ";
        $dbconn->Execute($data['sql']);
        $dbconn->commit();
    } catch (Exception $e) { throw($e);
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