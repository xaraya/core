<?php
/** 
 * File: $Id$
 *
 * Update attributes of a block instance
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks administration
 * @author Jim McDonald, Paul Rosania
*/
/**
 * update the group details for a block instance
 * TODO: parameter descriptions
 * @param $args['bid'] the ID of the block to update
 * @param $args['title'] the new title of the block
 * @param $args['group_id'] the new position of the block (deprecated)
 * @param $args['groups'] optional array of group memberships
 * @param $args['template'] the template of the block instance
 * @param $args['content'] the new content of the block
 * @param $args['refresh'] the new refresh rate of the block
 * @returns bool
 * @return true on success, false on failure
 */
function blocks_adminapi_update_instance_groups($args)
{
    extract($args);

    // The group instances are updated according to the
    // $groups parameter.
    // $groups is an array of _current_ group memberships.
    // Each group membership is an array:
    // 'gid' - group ID
    // 'template' - the over-ride template for this block group instance
    // This function will add, update or delete group memberships for
    // the block instance to leave the group membership state as defined
    // by the $groups array.

    if (!isset($groups) || !is_array($groups) || !isset($bid) || !is_numeric($bid)) {
        return;
    }

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    $block_groups_table = $xartable['block_groups'];
    $block_instances_table = $xartable['block_instances'];
    $block_group_instances_table = $xartable['block_group_instances'];

    // Get the current group membership for this block instance.
    // TODO: select template from table when available.
    $query = 'SELECT xar_id, xar_group_id, xar_template'
        . ' FROM ' . $block_group_instances_table
        . ' WHERE xar_instance_id = ' . $bid;

    $result =& $dbconn->Execute($query);

    $current = array();
    while (!$result->EOF) {
        $current[$result->fields[1]] = array (
            'id' => $result->fields[0],
            'gid' => $result->fields[1],
            'template' => $result->fields[2]
        );
        $result->MoveNext();
    }

    // Get the full list of block groups.
    // TODO: move this to an API.
    $query = 'SELECT xar_id FROM ' . $block_groups_table;
    $result =& $dbconn->Execute($query);

    $allgroups = array();
    while (!$result->EOF) {
        $allgroups[] = $result->fields[0];
        $result->MoveNext();
    }

    // Key the new groups on the gid for convenience
    $newgroups = array();
    foreach($groups as $group) {
        $newgroups[$group['gid']] = $group;
    }

    $query_arr = array();

    // Now we need to create a set of insert/update/delete commands.
    // Loop for each group.
    foreach ($allgroups as $gid) {
        // If the group is not in the $groups array, and is in the 
        // current instance groups, then it should be deleted.
        if (!isset($newgroups[$gid]) && isset($current[$gid])) {
            $query_arr[] = 'DELETE FROM ' . $block_group_instances_table
                . ' WHERE xar_id = ' . $current[$gid]['id'];
            //echo " delete:$gid ";
        }

        // If the new group does not exist, then create it.
        if (isset($newgroups[$gid]) && !isset($current[$gid])) {
            $nextId = $dbconn->GenId($block_group_instances_table);
            $query_arr[] = 'INSERT INTO ' . $block_group_instances_table
                . ' (xar_id, xar_group_id, xar_instance_id, xar_position, xar_template)'
                . ' VALUES (' . $nextId . ', ' . $gid . ', ' . $bid . ', 0, \''
                . xarVarPrepForStore($newgroups[$gid]['template']) . '\')';
            //echo " create:$gid with " . $newgroups[$gid]['template'];
        }

        // If the new group already exists, then update it.
        if (isset($newgroups[$gid]) && isset($current[$gid])
            && $newgroups[$gid]['template'] != $current[$gid]['template']) {
            $query_arr[] = 'UPDATE ' . $block_group_instances_table
                . ' SET xar_template = \'' . xarVarPrepForStore($newgroups[$gid]['template']) . '\''
                . ' WHERE xar_id = ' . $current[$gid]['id'];
            //echo " update:$gid with " . $newgroups[$gid]['template'];
        }
    }

    // TODO: use ADODB array query function?
    // TODO: error handling?
    foreach ($query_arr as $query) {
        $result =& $dbconn->Execute($query);
    }

    return true;
}

?>
