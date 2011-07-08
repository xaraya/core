<?php
/**
 * Upgrade SQL file
 *
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

function sql_210_02()
{
    // Define parameters
    $table['modules'] = xarDB::getPrefix() . '_modules';
    $table['block_types'] = xarDB::getPrefix() . '_block_types';
    $table['block_instances'] = xarDB::getPrefix() . '_block_instances';
    $table['block_groups'] = xarDB::getPrefix() . '_block_groups';
    $table['block_group_instances'] = xarDB::getPrefix() . '_block_group_instances';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Creating a block type 'blockgroup' and adding the blockgroups to the blocks instance table
    ");
    $data['reply'] = xarML("
        Success!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        INSERT INTO $table[block_types] (name, module_id, info) 
            SELECT 'blockgroup', m.id, 'a:27:{s:4:\"name\";s:15:\"BlockgroupBlock\";s:6:\"module\";s:6:\"blocks\";s:9:\"text_type\";s:10:\"Blockgroup\";s:14:\"text_type_long\";s:10:\"Blockgroup\";s:14:\"allow_multiple\";b:1;s:12:\"show_preview\";b:1;s:7:\"nocache\";i:0;s:10:\"pageshared\";i:1;s:10:\"usershared\";i:1;s:3:\"bid\";i:0;s:7:\"groupid\";i:0;s:5:\"group\";s:0:\"\";s:19:\"group_inst_template\";s:0:\"\";s:8:\"template\";s:0:\"\";s:14:\"group_template\";s:0:\"\";s:8:\"position\";i:0;s:7:\"refresh\";i:0;s:5:\"state\";i:2;s:3:\"tid\";i:0;s:4:\"type\";s:5:\"Block\";s:5:\"title\";s:0:\"\";s:11:\"cacheexpire\";N;s:6:\"expire\";i:0;s:14:\"display_access\";a:3:{s:5:\"group\";i:0;s:5:\"level\";i:100;s:7:\"failure\";i:0;}s:13:\"modify_access\";a:3:{s:5:\"group\";i:0;s:5:\"level\";i:100;s:7:\"failure\";i:0;}s:13:\"delete_access\";a:3:{s:5:\"group\";i:0;s:5:\"level\";i:100;s:7:\"failure\";i:0;}s:7:\"content\";a:0:{}}' FROM $table[modules] m WHERE m.name = 'blocks';
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        INSERT INTO $table[block_instances] (type_id, name, title, content, template, state)
            SELECT t.id, g.name, '', 'a:0:{}', g.template, 2 FROM $table[block_groups] g, $table[block_types] t WHERE t.name = 'blockgroup';
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