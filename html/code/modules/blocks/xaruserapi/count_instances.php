<?php
/**
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/*
 * Count block instances
 * @param array    $args array of optional parameters<br/>
 *        string   $args['order'] optionally count from group instances table<br/>
 *        integer  $args['type'] optionally count by block type<br/>
 *        string   $args['module'] optionally count by module<br/>
 *        integer  $args['gid'] optionally count by group id<br/>
 *        integer  $args['state'] optionally count by block state
 * @author Chris Powis <crisp@xaraya.com>
 * @throws DB_ERROR
 * @return int number of instances
*/

function blocks_userapi_count_instances(Array $args=array())
{
    extract($args);

    $order = !empty($order) && xarVarValidate('pre:trim:lower:enum:group', $order, true) ? $order : '';
    $filter = !empty($filter) && xarVarValidate('str', $filter, true) ? $filter : '';
    if (!empty($type) && !xarVarValidate('str', $type)) {return;}
    if (!empty($module) && !xarVarValidate('str', $module)) {return;}
    if (!empty($gid) && !xarVarValidate('int:1:', $gid)) {return;}
    if (isset($state) && !xarVarValidate('int', $state)) {return;}

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    $block_instances = $xartable['block_instances'];
    $group_instances = $xartable['block_group_instances'];
    $block_types     = $xartable['block_types'];
    $modules_table   = $xartable['modules'];

    $query = "SELECT COUNT(1)";
    $where = array();
    $bindvars = array();

    if ($order == 'group') {
        // count group instances
        $query .= " FROM $modules_table, $group_instances
                    LEFT JOIN $block_instances ON $block_instances.id = $group_instances.instance_id
                    LEFT JOIN $block_types ON $block_types.id = $block_instances.type_id";
    } else {
        // count block instances
        $query .= " FROM $modules_table, $block_instances
                    LEFT JOIN $block_types ON $block_types.id = $block_instances.type_id";
        // only need to join on group instances if we're filtering by group id here
        if (!empty($gid)) {
            $query.= " LEFT JOIN $group_instances ON $group_instances.instance_id = $block_instances.id";
        }
    }

    // only get instances where a module exists
    $where[] = "$modules_table.id = $block_types.module_id";

    // filter by block name
    if (!empty($filter)) {
        $where[] = "lower($block_instances.name) LIKE ?";
        $bindvars[] = '%'. strtolower($filter) . '%';
    }
    // filter by group id
    if (!empty($gid)) {
        $where[] = "$group_instances.group_id = ?";
        $bindvars[] = $gid;
    }
    // filter by module
    if (!empty($module)) {
        $where[] = "$modules_table.name = ?";
        $bindvars[] = $module;
    }
    // filter by block type
    if (!empty($type)) {
        $where[] = "$block_types.name = ?";
        $bindvars[] = $type;
    }
    // filter by block state
    if (isset($state)) {
        $where[] = "$block_instances.state = ?";
        $bindvars[] = $state;
    }

    if (!empty($where)) {
        $query .= " WHERE " . join(' AND ', $where);
    }

    $result = &$dbconn->Execute($query,$bindvars);
    if (!$result) return;
    list($numitems) = $result->fields;
    $result->Close();

    return $numitems;
}
?>