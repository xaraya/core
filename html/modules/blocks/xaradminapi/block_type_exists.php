<?php

/**
 * Check for existance of a block type
 *
 * @access public
 * @param modName the module name
 * @param blockType the block type
 * @returns bool
 * @return true if exists, false if not found
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function blocks_adminapi_block_type_exists($args)
{
    extract($args);
    if (empty($modName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    if (empty($blockType)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'blockType');
        return;
    }

    list ($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $block_types_table = $xartable['block_types'];

    $query = "SELECT    xar_id as id
              FROM      $block_types_table
              WHERE     xar_module = '$modName'
              AND       xar_type = '$blockType'";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Got exactly 1 result, it exists
    if ($result->PO_RecordCount() == 1) {
        list ($id) = $result->fields;
        return $id;
    }

    // Freak if we don't get zero or one one result
    if ($result->PO_RecordCount() > 1) {
        $msg = xarML('Multiple instances of block type #(1) found in module #(2)!', $blockType, $modName);
        xarExceptionSet(XAR_USER_EXCEPTION, 'MultipleInstances',
                       new DefaultUserException($msg));
        return;
    }

    return false;
}

?>