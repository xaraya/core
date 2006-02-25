<?php
/**
 * Unregister block types
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 */
/**
 * Unregister block type
 *
 * @author Jim McDonald, Paul Rosania
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

    $block_types_table     = $xartable['block_types'];
    $block_instances_table = $xartable['block_instances'];
    $modules_table         = $xartable['modules'];
    // First we need to retrieve the block ids and remove 
    // the corresponding id's from the xar_block_instances
    // and xar_block_group_instances tables
    $query = "SELECT    inst.xar_id as id
              FROM      $block_instances_table inst, $block_types_table btypes, $modules_table mods
              WHERE     mods.xar_id = btypes.xar_modid AND
                        btypes.xar_id = inst.xar_type_id AND
                        mods.xar_name = ? AND
                        btypes.xar_type = ?";
    $result =& $dbconn->Execute($query,array($modName,$blockType));

    try {
        $dbconn->begin();
        $modInfo = xarMod_GetBaseInfo($modName); 
        $modId = $modInfo['systemid'];

        while (!$result->EOF) {
            list($bid) = $result->fields;
            // Pass ids to API
            xarModAPIFunc('blocks','admin','delete_instance', array('bid' => $bid));
            $result->MoveNext();
        }
        // Delete the block type itself
        $query = "DELETE FROM $block_types_table WHERE xar_modid = ? AND xar_type = ?";
        $dbconn->Execute($query,array($modId,$blockType));

        $dbconn->commit();
    } catch(Exception $e) { // catch any exception for now
        $dbconn->rollback();
        throw $e;
    }
    $result->Close();

    return true;
}

?>
