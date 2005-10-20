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
 * Unregister block type
 *
 * @access public
 * @param modName the module name
 * @param blockType the block type
 * @returns bool
 * @return true on success, false on failure
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function blocks_adminapi_unregister_block_type($args)
{
    $res = xarModAPIFunc('blocks','admin','block_type_exists',$args);
    if (!isset($res)) return; // throw back
    if (!$res) return true; // Already unregistered

    extract($args);

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
              AND       btypes.xar_module = '$modName'
              AND       btypes.xar_type = '$blockType'";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    while (!$result->EOF) {
        list($bid) = $result->fields;

        // Pass ids to API
        xarModAPIFunc('blocks',
                      'admin',
                      'delete_instance', array('bid' => $bid));

        $result->MoveNext();
    }

    $result->Close();

    // Delete the block type
    $query = "DELETE FROM $block_types_table WHERE xar_module = '$modName' AND xar_type = '$blockType';";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    return true;
}

?>