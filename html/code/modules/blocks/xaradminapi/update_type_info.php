<?php
/**
 * Read the info details of a block type
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * Read the info details of a block type into the database.
 *
 * @author Jim McDonald, Paul Rosania
 * @access public
 * @param modName the module name (deprecated)
 * @param blockType the block type (deprecated)
 * @param args['tid'] the type id
 * @param args['module'] the module name
 * @param args['type'] the block type
 * @returns ID of block type registered (even if already registered)
 * @return true on success, false on failure
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function blocks_adminapi_update_type_info($args)
{
    extract($args);

    // Get the type details from the database.
    $type = xarMod::apiFunc('blocks', 'user', 'getblocktype', $args);

    if (empty($type)) {
        // No type registered in the database.
        return;
    }

    // Load and execute the info function of the block.
    $block_info = xarMod::apiFunc('blocks', 'user', 'read_type_info',
                                array('module' => $type['module'],
                                      'type' => $type['type']));
    if (empty($block_info)) {return;}

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    $block_types_table =& $xartable['block_types'];

    // Update the info column for the block in the database.
    $query = "UPDATE $block_types_table SET info = ? WHERE id = ?";
    $bind = array(serialize($block_info), $type['tid']);
    $dbconn->Execute($query, $bind);
    return true;
}

?>
