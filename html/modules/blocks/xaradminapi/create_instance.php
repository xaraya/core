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
 * @param $args['name'] unique name for the block
 * @param $args['title'] the title of the block
 * @param $args['type'] the block's type
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
        (!isset($name)) ||
        (!isset($type)) ||
        (!isset($position)) ||
        (!isset($state))) {
        $msg = xarML('Invalid Parameter Count', 'admin', 'create', 'Blocks');
        xarExceptionSet(
            XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
            new SystemException($msg)
        );
        return;
    }

    // Security
	if(!xarSecurityCheck('AddBlock', 1, 'Block', "All:$title:All")) {return;}

    if (!isset($content)) {
        $content = '';
    }

    // Load up database details.
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    $block_instances_table       = $xartable['block_instances'];
    //$block_group_instances_table = $xartable['block_group_instances'];
    //$block_groups_table          = $xartable['block_groups'];
    //$block_types_table           = $xartable['block_types'];

    // TODO: make sure type exists.

    // Insert instance details.
    $nextId = $dbconn->GenId($block_instances_table);
    $query = "INSERT INTO $block_instances_table (
              xar_id,
              xar_type_id,
              xar_name,
              xar_title,
              xar_content,
              xar_template,
              xar_state,
              xar_refresh,
              xar_last_update)
            VALUES (
              " . $nextId . ",
              " . xarVarPrepForStore($type) . ",
              '" . xarVarPrepForStore($name) . "',
              '" . xarVarPrepForStore($title) . "',
              '" . xarVarPrepForStore($content) . "',
              '" . xarVarPrepForStore($template) . "',
              " . xarVarPrepForStore($state) . ", 0,0)";

    $result =& $dbconn->Execute($query);
    if (!$result) {return;}

    // Get ID of row inserted.
    $bid = $dbconn->PO_Insert_ID($block_instances_table, 'xar_id');

    // Update the group instances.
    if (isset($groups) && is_array($groups)) {
        // Pass the group updated to the API if required.
        // TODO: error handling.
        $result = xarModAPIfunc(
            'blocks', 'admin', 'update_instance_groups',
            array('bid' => $bid, 'groups' => $groups)
        );
    }

    // Resequence the blocks.
    // TODO: support resequence by a single block type or for all
    // block groups in which a block instance is a member.
    xarModAPIFunc('blocks','admin','resequence');

    $args['module'] = 'blocks';
    xarModCallHooks('item', 'create', $bid, $args);

    return $bid;
}

?>
