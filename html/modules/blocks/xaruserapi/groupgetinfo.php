<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 */
/**
 * Get block group information
 *
 * @author Jim McDonald, Paul Rosania
 * @access public
 * @param integer blockGroupId the block group id
 * @return array lock information
 * @raise DATABASE_ERROR, BAD_PARAM, ID_NOT_EXIST
 */
function blocks_userapi_groupgetinfo($args)
{
    extract($args);

    if (empty($gid) || !is_numeric($gid)) {$gid = 0;}

    if (empty($name)) {$name = '';}

    if (empty($name) && empty($gid)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'gid/name');
        return;
    }

    if (xarVarIsCached('Block.Group.Infos', $gid)) {
        return xarVarGetCached('Block.Group.Infos', $gid);
    }

    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    $blockInstancesTable      = $tables['block_instances'];
    $blockTypesTable          = $tables['block_types'];
    $blockGroupsTable         = $tables['block_groups'];
    $blockGroupInstancesTable = $tables['block_group_instances'];

    $query = 'SELECT    xar_id as id,
                        xar_name as name,
                        xar_template as template
              FROM      ' . $blockGroupsTable;

    if (!empty($gid)) {
        $query .= ' WHERE xar_id = ' . $gid;
    } elseif (!empty($name)) {
        $query .= ' WHERE xar_name = ' . $dbconn->qstr($name);
    }

    $result =& $dbconn->Execute($query);
    if (!$result) {return;}

    // Return if we don't get exactly one result.
    if ($result->PO_RecordCount() != 1) {
        return;
    }

    $group = $result->GetRowAssoc(false);
    $result->Close();

    // If the name was used to find the group, then get the GID from the fetched group.
    if (empty($gid)) {
        $gid = $group['id'];
    }

    // Query for instances in this group
    $query = "SELECT    inst.xar_id as id,
                        btypes.xar_type as type,
                        btypes.xar_module as module,
                        inst.xar_title as title,
                        inst.xar_name as name,
                        group_inst.xar_position as position
              FROM      $blockGroupInstancesTable as group_inst
              LEFT JOIN $blockGroupsTable as bgroups
              ON        group_inst.xar_group_id = bgroups.xar_id
              LEFT JOIN $blockInstancesTable as inst
              ON        inst.xar_id = group_inst.xar_instance_id
              LEFT JOIN $blockTypesTable as btypes
              ON        btypes.xar_id = inst.xar_type_id
              WHERE     bgroups.xar_id = " . $gid . "
              ORDER BY  group_inst.xar_position ASC";

    $result =& $dbconn->Execute($query);
    if (!$result) {return;}

    // Load up list of group's instances
    $instances = array();
    while(!$result->EOF) {
        $instances[] = $result->GetRowAssoc(false);
        $result->MoveNext();
    }

    $result->Close();

    $group['instances'] = $instances;

    xarVarSetCached('Block.Group.Infos', $gid, $group);

    return $group;
}

?>