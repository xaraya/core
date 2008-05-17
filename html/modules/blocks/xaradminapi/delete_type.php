<?php
/**
 * Delete a block type
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * Delete block type
 *
 * @author Jim McDonald, Paul Rosania
 * @access public
 * @param modName the module name (deprec)
 * @param module the module name
 * @param blockType the block type (deprec)
 * @param type the block type
 * @return bool true on success, false on failure
 */
function blocks_adminapi_delete_type($args)
{
    extract($args);

    // Legacy.
    // TODO: eventually remove support for the mixed-case parameters.
    if (!empty($modName)) {$module = $modName;}
    if (!empty($blockType)) {$type = $blockType;}

    $count = xarModAPIFunc('blocks', 'user', 'countblocktypes',
                           array('module' => $module, 'type' => $type)
    );

    if (!isset($count)) {return;}
    if ($count == 0) {
        // Already deleted.
        return true;
    }


    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    $block_types_table = $xartable['block_types'];
    $block_instances_table = $xartable['block_instances'];

    // First we need to retrieve the block ids and remove
    // the corresponding id's from the xar_block_instances
    // and xar_block_group_instances tables
    $query = "SELECT    inst.id as id
              FROM      $block_instances_table as inst,
                        $block_types_table as btypes
              WHERE     btypes.id = inst.type_id
              AND       btypes.module_id = ?
              AND       btypes.type = ?";
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery(array($module, $type));

    while ($result->next()) {
        // Pass ids to API
        xarModAPIFunc('blocks', 'admin', 'delete_instance', array('bid' => $result->getInt(1)));
    }
    $result->close();

    // Delete the block type
    $query = "DELETE FROM $block_types_table WHERE module_id = ? AND type = ?";
    $dbconn->Execute($query, array($module, $type));
    return true;
}

?>
