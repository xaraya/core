<?php
/**
 * Upgrade SQL file
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage installer module
 * @link http://xaraya.com/index.php/release/200.html
 */

function sql_210_03()
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
        Updating the block_group_instances table and deleting the old blockgroups table
    ");
    $data['reply'] = xarML("
        Done!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        UPDATE $table[block_group_instances] gi SET group_id = 
            (SELECT i.id FROM $table[block_groups] g, $table[block_instances] i WHERE i.name = g.name AND g.id = gi.group_id);
        ";
        $dbconn->Execute($data['sql']);
        $data['sql'] = "
        DROP TABLE $table[block_groups];
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