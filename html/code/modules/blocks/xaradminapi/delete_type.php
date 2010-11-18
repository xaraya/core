<?php
/**
 * Delete a block type
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * Delete block type
 *
 * @author Jim McDonald
 * @author Paul Rosania
 * @access public
 * @param array    $args array of optional parameters<br/>
 *        string   $args['modName'] the module name (deprec)<br/>
 *        string   $args['module'] the module name<br/>
 *        string   $args['blockType'] the block type (deprec)<br/>
 *        string   $args['type'] the block type
 * @return boolean true on success, false on failure
 */
function blocks_adminapi_delete_type(Array $args=array())
{
    extract($args);

    // Legacy.
    // TODO: eventually remove support for the mixed-case parameters.
    if (!empty($modName)) {$module = $modName;}
    if (!empty($blockType)) {$type = $blockType;}

    $count = xarMod::apiFunc('blocks', 'user', 'countblocktypes',
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
              AND       btypes.name = ?";
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery(array(xarMod::getId($module), $type));

    while ($result->next()) {
        // Pass ids to API
        xarMod::apiFunc('blocks', 'admin', 'delete_instance', array('bid' => $result->getInt(1)));
    }
    $result->close();

    // Delete the block type
    $query = "DELETE FROM $block_types_table WHERE module_id = ? AND name = ?";
    $dbconn->Execute($query, array(xarMod::getId($module), $type));
    return true;
}

?>
