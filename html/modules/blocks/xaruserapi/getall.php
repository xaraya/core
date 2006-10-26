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
/*
 * Get one or all block instances.
 * @param args[$bid] optional block instance ID
 * @param args[$name] optional block instance name
 * @param args[$order] optional ordering
 * @author Jim McDonald, Paul Rosania
*/

function blocks_userapi_getall($args)
{
    extract($args);

    // Check parameters
    if (!empty($bid) && !xarVarValidate('int:1:', $bid)) {return;}
    if (!empty($name) && !xarVarValidate('str', $name)) {return;}
    
    if (!empty($order) && xarVarValidate('strlist:,|:enum:name:title:id', $order, true)) {
        $orderby = ' ORDER BY binst.xar_' . implode(', inst.xar_', explode(',', $order));
    } else {
        $orderby = '';
    }

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $block_instances_table = $xartable['block_instances'];
    $block_group_instances_table = $xartable['block_group_instances'];
    $block_types_table  = $xartable['block_types'];
    $block_groups_table = $xartable['block_groups'];
    $modules_table      = $xartable['modules'];
    // Fetch instance details.
    $query = "SELECT binst.xar_id, binst.xar_name,
                     binst.xar_title, binst.xar_template,
                     binst.xar_content, binst.xar_refresh, binst.xar_state,
                     btypes.xar_id, mods.xar_name, btypes.xar_type
              FROM   $modules_table mods, $block_instances_table binst
              LEFT JOIN $block_types_table btypes  ON btypes.xar_id = binst.xar_type_id 
              WHERE  mods.xar_id = btypes.xar_modid ";

    $bindvars = array();
    if (!empty($bid)) {
        $query .= "AND binst.xar_id = ? ";
        $bindvars[] = $bid;
    } elseif (!empty($name)) {
        $query .= "AND binst.xar_name = ? ";
        $bindvars[] = $name;
    } elseif (!empty($filter)) {
        $query .= "AND lower(binst.xar_name) LIKE ?";
        $bindvars[] = '%'. strtolower($filter) . '%';
    }
    $query .= ' ' . $orderby;

    // Prepare it
    $stmt = $dbconn->prepareStatement($query);
    
    // Return if no details retrieved.
    if (isset($startat) && isset($rowstodo)) {
        $stmt->setLimit($rowstodo);
        $stmt->setOffset($startat - 1);
    }
    $result = $stmt->executeQuery($bindvars);
    
    // Group query
    $querygroup = "SELECT bgroup_inst.xar_id,
                          bgroup_inst.xar_group_id,
                          bgroup_inst.xar_position,
                          bgroup_inst.xar_template as xar_group_inst_template,
                          bgroups.xar_name,
                          bgroups.xar_template as xar_group_template
                   FROM   $block_group_instances_table bgroup_inst
                   LEFT JOIN $block_groups_table bgroups ON bgroups.xar_id = bgroup_inst.xar_group_id
                   WHERE  bgroup_inst.xar_instance_id = ?";
    $grpStmt = $dbconn->prepareStatement($querygroup);
    
    // The main result array.
    $instances = array();

    while ($result->next()) {
        // Fetch instance data
        list($bid, $name, $title, $template, $content, $refresh, $state, $tid, $module, $type) = $result->fields;

        // TODO: is we use assoc fetching we get this for free
        $instance = array(
            'bid'       => $bid,
            'name'      => $name,
            'title'     => $title,
            'template'  => $template,
            'content'   => $content,
            'refresh'   => $refresh,
            'state'     => $state,
            'tid'       => $tid,
            'module'    => $module,
            'type'      => $type
            );

        // Fetch group details - there may be none, one or many groups.
        $resultgroup = $grpStmt->executeQuery(array($bid));
        while ($resultgroup->next()) {
            list($giid, $gid, $position, $group_inst_template, $name, $group_template) = $resultgroup->fields;

            // TODO: if we use assoc fetching we get this for free
            $group_instance = array(
                'giid'      => $giid,
                'gid'       => $gid,
                'position'  => $position,
                'name'      => $name,
                // Return the original templates values as well as the over-riding templates.
                'group_template'      => $group_template,
                'group_inst_template' => $group_inst_template
            );
            $instance['groups'][$gid] = $group_instance;
        }
        // Close group query.
        $resultgroup->close();

        // Put the instance into the result array.
        // Using references helps prevent copying data structures around.
        $instances[$bid] =& $instance;
        unset($instance);
    }
    // Close main query.
    $result->close();

    return $instances;
}

?>
