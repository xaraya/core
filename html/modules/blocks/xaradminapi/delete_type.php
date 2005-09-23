<?php
/** 
 * File: $Id$
 *
 * Unregister block type
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
 * Delete block type
 *
 * @access public
 * @param modName the module name (deprec)
 * @param module the module name
 * @param blockType the block type (deprec)
 * @param type the block type
 * @returns bool
 * @return true on success, false on failure
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function blocks_adminapi_delete_type($args)
{
    extract($args);

    // Legacy.
    // TODO: eventually remove support for the mixed-case parameters.
    if (!empty($modName)) {$module = $modName;}
    if (!empty($blockType)) {$type = $blockType;}

    $count = xarModAPIFunc(
        'blocks', 'user', 'countblocktypes',
        array('module' => $module, 'type' => $type)
    );

    if (!isset($count)) {return;}
    if ($count == 0) {
        // Already deleted.
        return true;
    }


    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $block_types_table = $xartable['block_types'];
    $block_instances_table = $xartable['block_instances'];

    // First we need to retrieve the block ids and remove 
    // the corresponding id's from the xar_block_instances
    // and xar_block_group_instances tables
    $query = "SELECT    inst.xar_id as id
              FROM      $block_instances_table as inst,
                        $block_types_table as btypes
              WHERE     btypes.xar_id = inst.xar_type_id
              AND       btypes.xar_module = ?
              AND       btypes.xar_type = ?";

    $result = $dbconn->Execute($query, array($module, $type));
    if (!$result) return;

    while (!$result->EOF) {
        list($bid) = $result->fields;

        // Pass ids to API
        xarModAPIFunc(
            'blocks', 'admin', 'delete_instance', array('bid' => $bid)
        );

        $result->MoveNext();
    }

    $result->Close();

    // Delete the block type
    $query = "DELETE FROM $block_types_table WHERE xar_module = ? AND xar_type = ?";
    $result = $dbconn->Execute($query, array($module, $type));
    if (!$result) return;

    return true;
}

?>