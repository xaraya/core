<?php

/**
 * resequence a blocks table
 * @returns void
 */
function blocks_adminapi_resequence()
{
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    $block_group_instances_table = $xartable['block_group_instances'];


    // Get the information
    $query = "SELECT xar_id,
                     xar_group_id,
                     xar_position
              FROM $block_group_instances_table
              ORDER BY xar_group_id,
                       xar_position ASC";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Fix sequence numbers
    $last_group = NULL;
    while(!$result->EOF) {
        list($link_id, $group, $old_position) = $result->fields;
        $result->MoveNext();

        // Reset sequence number if we've changed the group we're sorting
        if ($last_group != $group) {
            $position = 1;
            $last_group = $group;
        }
        if ($position != $old_position) {
            $query = "UPDATE $block_group_instances_table
                      SET xar_position = " . xarVarPrepForStore($position) . "
                      WHERE xar_id = " . xarVarPrepForStore($link_id);
            $result =& $dbconn->Execute($query);
            if (!$result) return;
        }

        $position++;
    }

    $result->Close();

    return true;
}

?>