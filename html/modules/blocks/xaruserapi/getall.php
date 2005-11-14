<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
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

    // Check parameters.
    if (!empty($bid) && !xarVarValidate('int:1:', $bid)) {return;}
    if (!empty($name) && !xarVarValidate('str', $name)) {return;}

    if (!empty($order) && xarVarValidate('strlist:,|:enum:name:title:id', $order, true)) {
        $orderby = ' ORDER BY xar_' . implode(', inst.xar_', explode(',', $order));
    } else {
        $orderby = '';
    }

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $block_instances_table = $xartable['block_instances'];
    $block_group_instances_table = $xartable['block_group_instances'];
    $block_types_table = $xartable['block_types'];
    $block_groups_table = $xartable['block_groups'];

    // Fetch instance details.
    $query = 'SELECT binst.xar_id,
                     binst.xar_name,
                     binst.xar_title,
                     binst.xar_template,
                     binst.xar_content,
                     binst.xar_refresh,
                     binst.xar_state,
                     btypes.xar_id,
                     btypes.xar_module,
                     btypes.xar_type
              FROM   '.$block_instances_table.' binst
              LEFT JOIN '.$block_types_table.' btypes
              ON        btypes.xar_id = binst.xar_type_id ';

    if (!empty($bid)) {
        $query .= ' WHERE binst.xar_id = ' . $bid;
    } elseif (!empty($name)) {
        $query .= ' WHERE binst.xar_name = \'' . $name . '\'';
    } elseif (!empty($filter)) {
        $query .= ' WHERE lower(binst.xar_name) LIKE \'%' . strtolower($filter) . '%\'';
    }
	$query .= ' ' . $orderby;

    // Return if no details retrieved.
    if (isset($startat) && isset($rowstodo)) {
		$result =& $dbconn->SelectLimit($query,$rowstodo,$startat-1);
    } else {
		$result =& $dbconn->Execute($query);
    }
	if (!$result) {return;}

    // The main result array.
    $instances = array();

    while (!$result->EOF) {
        // Fetch instance data
        list(
            $bid, $name, $title, $template, $content, $refresh, $state,
            $tid, $module, $type
        ) = $result->fields;

        $instance = array();
        $instance['bid'] = $bid;
        $instance['name'] = $name;
        $instance['title'] = $title;
        $instance['template'] = $template;
        $instance['content'] = $content;
        $instance['refresh'] = $refresh;
        $instance['state'] = $state;
        $instance['tid'] = $tid;
        $instance['module'] = $module;
        $instance['type'] = $type;

        // Fetch group details - there may be none, one or many groups.
        $querygroup = 'SELECT bgroup_inst.xar_id,
                         bgroup_inst.xar_group_id,
                         bgroup_inst.xar_position,
                         bgroup_inst.xar_template as xar_group_inst_template,
                         bgroups.xar_name,
                         bgroups.xar_template as xar_group_template
                  FROM   '.$block_group_instances_table.' bgroup_inst
                  LEFT JOIN '.$block_groups_table.' bgroups
                  ON        bgroups.xar_id = bgroup_inst.xar_group_id
                  WHERE     bgroup_inst.xar_instance_id = ' . $bid;

        $resultgroup =& $dbconn->Execute($querygroup);
        if ($resultgroup) {
            while (!$resultgroup->EOF) {
                list(
                    $giid, $gid, $position, $group_inst_template, $name, $group_template
                ) = $resultgroup->fields;

                $group_instance = array();

                $group_instance['giid'] = $giid;
                $group_instance['gid'] = $gid;
                $group_instance['position'] = $position;
                $group_instance['name'] = $name;

                // Return the original templates values as well as the over-riding templates.
                $group_instance['group_template'] = $group_template;
                $group_instance['group_inst_template'] = $group_inst_template;

                $instance['groups'][$gid] = $group_instance;

                // Next group instance row.
                $resultgroup->MoveNext();
            }
        }

        // Close group query.
        $resultgroup->Close();

        // Put the instance into the result array.
        // Using references helps prevent copying data structures around.
        $instances[$bid] =& $instance;
        unset($instance);

        // Next block instance row.
        $result->MoveNext();
    }

    // Close main query.
    $result->Close();

    return $instances;
}

?>