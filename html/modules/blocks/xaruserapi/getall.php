<?php
/** 
 * File: $Id$
 *
 * Get one or all block instances.
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @param args[$bid] optional block instance ID
 * @param args[$name] optional block instance name
 *
 * @subpackage Blocks administration
 * @author Jim McDonald, Paul Rosania
*/

function blocks_userapi_getall($args)
{
    extract($args);

    // Check parameters.
    if (!empty($bid) && !xarVarValidate('int:1:', $bid)) {return;}
    if (!empty($name) && !xarVarValidate('str', $name)) {return;}

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $block_instances_table = $xartable['block_instances'];
    $block_group_instances_table = $xartable['block_group_instances'];
    $block_types_table = $xartable['block_types'];
    $block_groups_table = $xartable['block_groups'];

    // Fetch instance details.
    $query = 'SELECT inst.xar_id,
                     inst.xar_name,
                     inst.xar_title,
                     inst.xar_template,
                     inst.xar_content,
                     inst.xar_refresh,
                     inst.xar_state,
                     types.xar_id,
                     types.xar_module,
                     types.xar_type
              FROM   '.$block_instances_table.' as inst
              LEFT JOIN '.$block_types_table.' as types
              ON        types.xar_id = inst.xar_type_id';

    if (!empty($bid)) {
        $query .= ' WHERE inst.xar_id = ' . $bid;
    }
    if (!empty($name)) {
        $query .= ' WHERE inst.xar_name = \'' . $name . '\'';
    }

    // Return if no details retrieved.
    $result =& $dbconn->Execute($query);
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
        $querygroup = 'SELECT group_inst.xar_id,
                         group_inst.xar_group_id,
                         group_inst.xar_position,
                         group_inst.xar_template as xar_group_inst_template,
                         groups.xar_name,
                         groups.xar_template as xar_group_template
                  FROM   '.$block_group_instances_table.' as group_inst
                  LEFT JOIN '.$block_groups_table.' as groups
                  ON        groups.xar_id = group_inst.xar_group_id
                  WHERE     group_inst.xar_instance_id = ' . $bid;

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
