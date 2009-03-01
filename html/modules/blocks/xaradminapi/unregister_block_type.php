<?php
/**
 * Unregister block types
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
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
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function blocks_adminapi_unregister_block_type($args)
{
    $res = xarModAPIFunc('blocks','admin','block_type_exists',$args);
    if (!isset($res)) return; // throw back
    if (!$res) return true; // Already unregistered

    extract($args);

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    $block_types_table     = $xartable['block_types'];
    $block_instances_table = $xartable['block_instances'];
    $modules_table         = $xartable['modules'];
    // First we need to retrieve the block ids and remove
    // the corresponding id's from the block_instances
    // and block_group_instances tables
    $query = "SELECT    inst.id as id
              FROM      $block_instances_table inst, $block_types_table btypes, $modules_table mods
              WHERE     mods.id = btypes.module_id AND
                        btypes.id = inst.type_id AND
                        mods.name = ? AND
                        btypes.name = ?";
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery(array($modName,$blockType));

    try {
        $dbconn->begin();
        $modInfo = xarMod_GetBaseInfo($modName);
        $modId = $modInfo['systemid'];

        while ($result->next()) {
            // Pass ids to API
            xarModAPIFunc('blocks','admin','delete_instance', array('bid' => $result->getInt(1)));
        }
        // Delete the block type itself
        $query = "DELETE FROM $block_types_table WHERE module_id = ? AND name = ?";
        $stmt = $dbconn->prepareStatement($query);
        $stmt->executeUpdate(array($modId,$blockType));

        $dbconn->commit();
    } catch(Exception $e) { // catch any exception for now
        $dbconn->rollback();
        throw $e;
    }
    $result->Close();

    return true;
}

?>
