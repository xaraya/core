<?php
/** 
 * File: $Id$
 *
 * Delete a group
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
 * delete a group
 * @param $args['gid'] the ID of the block group to delete
 * @returns bool
 * @return true on success, false on failure
 */
function blocks_adminapi_delete_group($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($gid) || !is_numeric($gid)) {
        // FIXME: raise proper error messages through the handler.
        xarSessionSetVar('errormsg', _MODARGSERROR);
        return false;
    }

    // Security
	if (!xarSecurityCheck('DeleteBlock', 1, 'Block', "::$gid")) {return;}

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    $block_groups_table = $xartable['block_groups'];
    $block_instances_table = $xartable['block_instances'];
    $block_group_instances_table = $xartable['block_group_instances'];

    // Query for instances in this group
    $query = "SELECT    inst.xar_id as id
              FROM      $block_group_instances_table as group_inst
              LEFT JOIN $block_instances_table as inst
              ON        inst.xar_id = group_inst.xar_instance_id
              WHERE     group_inst.xar_group_id = $gid";
    $result =& $dbconn->Execute($query);
    if (!$result) {return;}

    // Load up list of group's instances
    // TODO: move to an API.
    $instances = array();
    while (!$result->EOF) {
        $instances[] = $result->GetRowAssoc(false);
        $result->MoveNext();
    }
    $result->Close();

    // Delete group member instance definitions
    foreach ($instances as $instance) {
        $query = "DELETE FROM $block_instances_table
                  WHERE       xar_id = ".$instance['id'];
        $result =& $dbconn->Execute($query);
        if (!$result) {return;}
    }

    // Delete block group definition
    $query = "DELETE FROM $block_groups_table
              WHERE xar_id=" . $gid;
    $result =& $dbconn->Execute($query);
    if (!$result) {return;}

    // Delete group-instance links
    $query = "DELETE FROM $block_group_instances_table
              WHERE xar_group_id = " . $gid;
    $result =& $dbconn->Execute($query);
    if (!$result) {return;}

    return true;
}

?>
