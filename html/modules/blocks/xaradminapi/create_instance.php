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

    // Argument check
    if (!isset($title) ||
        !xarVarValidate('pre:lower:ftoken:passthru:str:1', $name) ||
        (!isset($type) || !is_numeric($type)) ||
        (!isset($state) || !is_numeric($state))) {
        // TODO: this type of error to be handled automatically
        // (i.e. no need to pass the position through the error message, as the
        // error handler should already know).
        $msg = xarML('Invalid Parameter Count', 'admin', 'create', 'Blocks');
        xarExceptionSet(
            XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
            new SystemException($msg)
        );
        return;
    }

    // Security.
    // TODO: fix this. A security check on whether a certain title can be created
    // does not make any sense to me. Probably remove it.
	//if (!xarSecurityCheck('AddBlock', 1, 'Block', "All:$title:All")) {return;}

    // Make sure type exists.
    $blocktype = xarModAPIfunc('blocks', 'user', 'getblocktype', array('tid' => $type));

    // If the content is not set, attempt to get initial content from
    // the block initialization function.
    if (!isset($content)) {
        $content = '';

        $initresult = xarModAPIfunc(
            'blocks', 'user', 'read_type_init', $blocktype
        );

        if (!empty($initresult)) {
            $content = $initresult;
        }
    }

    if (!empty($content) && !is_string($content)) {
        // Serialize the content, so arrays of initial content
        // can be passed directly into this API.
        $content = serialize($content);
    }

    // Load up database details.
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $block_instances_table = $xartable['block_instances'];

    // Insert instance details.
    $nextId = $dbconn->GenId($block_instances_table);
    $query = 'INSERT INTO ' . $block_instances_table . ' (
              xar_id,
              xar_type_id,
              xar_name,
              xar_title,
              xar_content,
              xar_template,
              xar_state,
              xar_refresh,
              xar_last_update
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0)';

    $result =& $dbconn->Execute(
        $query, array(
            $nextId, $type, $name, $title, $content, $template, $state
        )
    );
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
    xarModAPIFunc('blocks', 'admin', 'resequence');

    $args['module'] = 'blocks';
    xarModCallHooks('item', 'create', $bid, $args);

    return $bid;
}

?>