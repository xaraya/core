<?php
/** 
 * File: $Id$
 *
 * Create a new block instance
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks administration
 * @author Jim McDonald, Paul Rosania
*/
/**
 * create a new block instance
 * @param $args['title'] the title of the block
 * @param $args['type'] the block's type
 * @param $args['group'] the block's group
 * @param $args['template'] the block's template
 * @returns int
 * @return block instance id on success, false on failure
 */
function blocks_adminapi_create_instance($args)
{
    // Get arguments from argument array
    extract($args);

    // Currently don't support initial positioning
    $position = 0;

    // Argument check
    if ((!isset($title)) ||
        (!isset($type)) ||
        (!isset($group)) ||
        (!isset($position)) ||
        (!isset($state))) {
        $msg = xarML('Invalid Parameter Count',
                    join(', ',$invalid), 'admin', 'create', 'Blocks');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Security
	if(!xarSecurityCheck('AddBlock',1,'Block',"All:$title:All")) return;

    if (!isset($content)) {
        $content = '';
    }

    // Load up database
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    $block_instances_table       = $xartable['block_instances'];
    $block_group_instances_table = $xartable['block_group_instances'];
    $block_groups_table          = $xartable['block_groups'];
    $block_types_table           = $xartable['block_types'];

    // TODO: make sure group, type exist

    // Insert instance into table
    $nextId = $dbconn->GenId($block_instances_table);
    $query = "INSERT INTO $block_instances_table (
              xar_id,
              xar_type_id,
              xar_title,
              xar_content,
              xar_template,
              xar_state,
              xar_refresh,
              xar_last_update)
            VALUES (
              " . xarVarPrepForStore($nextId) . ",
              " . xarVarPrepForStore($type) . ",
              '" . xarVarPrepForStore($title) . "',
              '" . xarVarPrepForStore($content) . "',
              '" . xarVarPrepForStore($template) . "',
              " . xarVarPrepForStore($state) . ", 0,0)";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Get block ID as index of block instances table
    $block_id = $dbconn->PO_Insert_ID($block_instances_table, 'xar_id');

    // Insert group-instance link into table
    $nextId = $dbconn->GenId($block_group_instances_table);
    $query = "INSERT INTO $block_group_instances_table (
              xar_id,
              xar_group_id,
              xar_instance_id,
              xar_position)
            VALUES (
              " . xarVarPrepForStore($nextId) . ",
              '" . xarVarPrepForStore($group) . "',
              '$block_id',
              '" . xarVarPrepForStore($position) . "');";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Resequence the blocks
    xarModAPIFunc('blocks','admin','resequence');

    $args['module'] = 'blocks';
    xarModCallHooks('item', 'create', $block_id, $args);

    return $block_id;
}

?>
