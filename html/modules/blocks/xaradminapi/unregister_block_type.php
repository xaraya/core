<?php

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

    list ($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $block_types_table = $xartable['block_types'];

    $query = "DELETE FROM $block_types_table WHERE xar_module = '$modName' AND xar_type = '$blockType';";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    return true;
}

?>
