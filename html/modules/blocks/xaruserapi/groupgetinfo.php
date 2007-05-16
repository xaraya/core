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
 * Get block group information
 *
 * @author Jim McDonald, Paul Rosania
 * @access public
 * @param integer blockGroupId the block group id
 * @return array lock information
 * @throws DATABASE_ERROR, BAD_PARAM, ID_NOT_EXIST
 */
function blocks_userapi_groupgetinfo($args)
{
    extract($args);

    if (empty($gid) || !is_numeric($gid)) {$gid = 0;}

    if (empty($name)) {$name = '';}

    if (empty($name) && empty($gid)) {
        throw new EmptyParameterException('name or gid');
    }

    if (xarVarIsCached('Block.Group.Infos', $gid)) {
        return xarVarGetCached('Block.Group.Infos', $gid);
    }

    $dbconn = xarDB::getConn();
    $tables =& xarDBGetTables();

    $blockInstancesTable      = $tables['block_instances'];
    $blockTypesTable          = $tables['block_types'];
    $blockGroupsTable         = $tables['block_groups'];
    $blockGroupInstancesTable = $tables['block_group_instances'];
    $modulesTable             = $tables['modules'];

    $query = 'SELECT    id as id,
                        name as name,
                        template as template
              FROM      ' . $blockGroupsTable;

    $bindvars = array();
    if (!empty($gid)) {
        $query .= ' WHERE id = ?';
        $bindvars=array($gid);
    } elseif (!empty($name)) {
        $query .= ' WHERE name = ?';
        $bindvars=array($name);
    }

    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery($bindvars,ResultSet::FETCHMODE_ASSOC);

    // Return if we don't get exactly one result.
    if ($result->getRecordCount() != 1) {
        return;
    }
    $result->next();
    $group = $result->fields;
    $result->close();

    // If the name was used to find the group, then get the GID from the fetched group.
    if (empty($gid)) {
        $gid = $group['id'];
    }

    // Query for instances in this group
    // NOTE: same query as in includes/xarBlocks.php
    $query = "SELECT    inst.id as id,
                        btypes.type as type,
                        mods.name as module,
                        inst.title as title,
                        inst.name as name,
                        group_inst.position as position
              FROM      $blockGroupInstancesTable as group_inst
              LEFT JOIN $blockGroupsTable as bgroups ON group_inst.group_id = bgroups.id
              LEFT JOIN $blockInstancesTable as inst ON inst.id = group_inst.instance_id
              LEFT JOIN $blockTypesTable as btypes   ON btypes.id = inst.type_id
              LEFT JOIN $modulesTable as mods        ON btypes.modid = mods.id
              WHERE     bgroups.id = ?
              ORDER BY  group_inst.position ASC";
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery(array($gid),ResultSet::FETCHMODE_ASSOC);

    // Load up list of group's instances
    $instances = array();
    while($result->next()) {
        $instances[] = $result->fields;
    }
    $result->close();

    $group['instances'] = $instances;

    xarVarSetCached('Block.Group.Infos', $gid, $group);
    return $group;
}

?>
