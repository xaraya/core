<?php
/** 
 * File: $Id$
 *
 * Register block type
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
 * Register block type
 *
 * @access public
 * @param modName the module name (deprecated)
 * @param blockType the block type (deprecated)
 * @param args['module'] the module name
 * @param args['type'] the block type
 * @returns ID of block type registered (even if already registered)
 * @return true on success, false on failure
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function blocks_adminapi_register_block_type($args)
{
    extract($args);

    if (!empty($modName)) {$module = $modName;}
    if (!empty($blockType)) {$type = $blockType;}

    $origtype = xarModAPIFunc('blocks', 'user', 'getblocktype', array('module'=>$module, 'type'=>$type));

    if (!empty($origtype)) {
        // Already registered - no need to raise an error, since we are where we wanted to be.
        // Just return the type ID.
        return $origtype['tid'];
    }

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $block_types_table = $xartable['block_types'];

    $nextID = $dbconn->GenId($block_types_table);
    $query = "INSERT INTO $block_types_table (xar_id, xar_module, xar_type)"
        . " VALUES ($nextID, '" . xarVarPrepForStore($module) . "', '" . xarVarPrepForStore($type) . "');";
    $result =& $dbconn->Execute($query);
    if (!$result) {return;}

    if (empty($nextID)) {
        $nextID = $dbconn->PO_Insert_ID($block_types_table, 'xar_id');
    }

    return $nextID;
}

?>