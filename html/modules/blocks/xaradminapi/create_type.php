<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * Register block type
 *
 * @author Jim McDonald, Paul Rosania
 * @access public
 * @param modName the module name (deprecated)
 * @param blockType the block type (deprecated)
 * @param args['module'] the module name
 * @param args['type'] the block type
 * @param args['info'] the info array for the block type
 * @returns ID of block type registered (even if already registered)
 * @return true on success, false on failure
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function blocks_adminapi_create_type($args)
{
    extract($args);

    // Legacy - we want to use lower-case 'module' and 'type' now.
    if (!empty($modName)) {$module = $modName;}
    if (!empty($blockType)) {$type = $blockType;}
    if (empty($info)) {
        $info = NULL;
    } else {
        // Prepare the info array for storage.
        $info = serialize($info);
    }

    $origtype = xarModAPIFunc('blocks', 'user', 'getblocktype',
                              array('module'=>$module, 'type'=>$type));

    if (!empty($origtype)) {
        // Already registered - no need to raise an error, since we are where we wanted to be.
        // Just return the type ID.
        return $origtype['tid'];
    }

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $block_types_table = $xartable['block_types'];

    $modInfo = xarMod_GetBaseInfo($module);
    $modId = $modInfo['systemid'];
    assert('$modId != 0;');
    try {
        $dbconn->begin();
        $nextID = $dbconn->GenId($block_types_table);
        $query = "INSERT INTO $block_types_table
                  (xar_id, xar_modid, xar_type, xar_info) VALUES (?, ?, ?, ?)";
        $bindvars = array($nextID, $modId, $type, $info);
        $dbconn->Execute($query, $bindvars);
        // FIXME: This is now a problem, since we have now of knowing whether that ID
        // is already known, since we might have statements pending in a transaction.
        // MySQL[Innodb] test: as long as it is inside the same transaction, it works
        if (empty($nextID)) {
            $nextID = $dbconn->PO_Insert_ID($block_types_table, 'xar_id');
        }
        assert('$nextID >0');
        // Update the block info details.
        xarModAPIfunc('blocks', 'admin', 'update_type_info', array('tid' => $nextID));
        $dbconn->commit();
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }
    return $nextID;
}

?>
