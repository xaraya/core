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
    if ((!isset($title)) ||
        (!isset($name)) ||
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
	if (!xarSecurityCheck('AddBlock', 1, 'Block', "All:$title:All")) {return;}

    // Make sure type exists.
    $blocktype = xarModAPIfunc('blocks', 'user', 'getblocktype', array('tid' => $type));

    // If the content is not set, attempt to get initial content from
    // the block initialization function.
    if (!isset($content) || !is_array($content)) {
        $content = '';

        // The initialisation function to execute.
        $initFunc = $blocktype['module'] . '_' . $blocktype['type'] . 'block_init';

        // Load the block - both the 'init' and the 'modify' functions, where available.
        xarModAPIFunc(
            'blocks', 'admin', 'load',
            array(
                'modName' => $blocktype['module'],
                'blockType' => $blocktype['type'],
                'blockFunc' => 'init'
            )
        );

        // If the init function is not present, try loading the block modify script.
        if (!function_exists($initFunc)) {
            xarModAPIFunc(
                'blocks', 'admin', 'load',
                array(
                    'modName' => $blocktype['module'],
                    'blockType' => $blocktype['type'],
                    'blockFunc' => 'modify'
                )
            );
        }

        if (function_exists($initFunc)) {
            $initresult = $initFunc();

            // Only if the init function returns a string, should it be
            // considered to be a block initialization.
            if (isset($initresult) && is_string($initresult) && !empty($initresult)) {
                $content = $initresult;
            }
        }
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
              xar_last_update)
            VALUES ('
              . $nextId . ', '
              . $type . ', '
              . $dbconn->qstr($name) . ', '
              . $dbconn->qstr($title) . ', '
              . $dbconn->qstr($content) . ', '
              . $dbconn->qstr($template) . ', '
              . $state . ', 0, 0)';

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
    xarModAPIFunc('blocks', 'admin', 'resequence');

    $args['module'] = 'blocks';
    xarModCallHooks('item', 'create', $bid, $args);

    return $bid;
}

?>