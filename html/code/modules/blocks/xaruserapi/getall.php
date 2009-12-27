<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
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
        $orderby = ' ORDER BY binst.' . $order;
    } elseif (!empty($order) && $order == 'type') {
        $orderby = ' ORDER BY btypes.name';
    } elseif (!empty($order) && $order == 'group') {
        $orderby = ' ORDER BY bgroups.name, binst.name';
    } else {
        $order = '';
        $orderby = '';
    }

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    $block_instances_table = $xartable['block_instances'];
    $block_group_instances_table = $xartable['block_group_instances'];
    $block_types_table  = $xartable['block_types'];
    $block_groups_table = $xartable['block_groups'];
    $modules_table      = $xartable['modules'];
    // Fetch instance details.
    if ($order == 'group') {
        $query = "SELECT binst.id, binst.name,
                         binst.title, binst.template,
                         binst.content, binst.refresh, binst.state,
                         btypes.id, mods.name, btypes.name, bgroups.id, bgroups.name
                  FROM   $modules_table mods, $block_instances_table binst
                  LEFT JOIN $block_types_table btypes  ON btypes.id = binst.type_id
                  INNER JOIN $block_group_instances_table gi  ON gi.instance_id = binst.id
                  LEFT JOIN $block_groups_table bgroups  ON bgroups.id = gi.group_id
                  WHERE  mods.id = btypes.module_id ";
    } else {
        $query = "SELECT binst.id, binst.name,
                         binst.title, binst.template,
                         binst.content, binst.refresh, binst.state,
                         btypes.id, mods.name, btypes.name
                  FROM   $modules_table mods, $block_instances_table binst
                  LEFT JOIN $block_types_table btypes  ON btypes.id = binst.type_id
                  WHERE  mods.id = btypes.module_id ";
    }

    $bindvars = array();
    if (!empty($bid)) {
        $query .= "AND binst.id = ? ";
        $bindvars[] = $bid;
    } elseif (!empty($name)) {
        $query .= "AND binst.name = ? ";
        $bindvars[] = $name;
    } elseif (!empty($filter)) {
        $query .= "AND lower(binst.name) LIKE ?";
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

    // The main result array.
    $instances = array();

    if ($order =='group') {
        while ($result->next()) {
            // Fetch instance data
            list($bid, $name, $title, $template, $content, $refresh, $state, $tid, $module, $type, $gid, $group) = $result->fields;

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
                'type'      => $type,
                'groupid'   => $gid,
                'group'      => $group,
                );

            // Put the instance into the result array.
            // Using references helps prevent copying data structures around.
            $instances[$bid] =& $instance;
            unset($instance);
        }
    } else {
        // Group query
        $querygroup = "SELECT bgroup_inst.id,
                              bgroup_inst.group_id,
                              bgroup_inst.position,
                              bgroup_inst.template as group_inst_template,
                              bgroups.name,
                              bgroups.template as group_template
                       FROM   $block_group_instances_table bgroup_inst
                       LEFT JOIN $block_groups_table bgroups ON bgroups.id = bgroup_inst.group_id
                       WHERE  bgroup_inst.instance_id = ?";
        $grpStmt = $dbconn->prepareStatement($querygroup);

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
                list($giid, $id, $position, $group_inst_template, $name, $group_template) = $resultgroup->fields;

                // TODO: if we use assoc fetching we get this for free
                $group_instance = array(
                    'giid'      => $giid,
                    'id'       => $id,
                    'position'  => $position,
                    'name'      => $name,
                    // Return the original templates values as well as the over-riding templates.
                    'group_template'      => $group_template,
                    'group_inst_template' => $group_inst_template
                );
                $instance['groups'][$id] = $group_instance;
            }
            // Close group query.
            $resultgroup->close();

            // Put the instance into the result array.
            // Using references helps prevent copying data structures around.
            $instances[$bid] =& $instance;
            unset($instance);
        }
    }
    // Close main query.
    $result->close();
    return $instances;
}

?>