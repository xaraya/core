<?php

/**
 * delete a block
 * @param $args['bid'] the ID of the block to delete
 * @returns bool
 * @return true on success, false on failure
 */
function blocks_adminapi_delete_instance($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($bid)) {
        xarSessionSetVar('errormsg', _MODARGSERROR);
        return false;
    }

    // Security
	if(!xarSecurityCheck('DeleteBlock',1,'Block',"::$bid")) return;

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    $block_instances_table = $xartable['block_instances'];
    $block_group_instances_table = $xartable['block_group_instances'];

    $query = "DELETE FROM $block_instances_table
              WHERE xar_id=" . xarVarPrepForStore($bid);
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $query = "DELETE FROM $block_group_instances_table
              WHERE xar_instance_id=" . xarVarPrepForStore($bid);
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    xarModAPIFunc('blocks', 'admin', 'resequence');

    xarModCallHooks('item', 'delete', $bid, '');

    return true;
}

?>
