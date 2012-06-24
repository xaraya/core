<?php
/**
 * @package modules
 * @subpackage blocks module
 * @scenario soloblock
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * @author Chris Powis <crisp@xaraya.com>
 * @todo 
**/
function blocks_typesapi_countitems(Array $args=array())
{
    extract($args);

    if (isset($module)) {
        if (empty($module)) {
            $module_id = 0;
        } elseif (!is_string($module) || !xarMod::isAvailable($module)) {
            $invalid[] = 'module';
        } else {
            $modinfo = xarMod::getBaseInfo($module);
            $module_id = $modinfo['systemid'];
        }
    }
    
    if (isset($module_id) && !is_numeric($module_id))
        $invalid[] = 'module_id';
    
    if (isset($type_category) && !is_string($type_category))
        $invalid[] = 'type_category';
    
    if (isset($type_state)) {
        if (is_numeric($type_state)) {
            $type_state = array($type_state);
        } elseif (is_array($type_state)) {
            foreach ($type_state as $dt) {
                if (!is_numeric($dt)) {
                    $invalid[] = 'type_state';
                    break;
                }
            }
        } else {
            $invalid[] = 'type_state';
        }
    }
    
    if (!empty($invalid)) {
        $msg = 'Invalid #(1) for #(2) module #(3) function #(4)()';
        $vars = array(join(', ', $invalid), 'blocks', 'typesapi', 'countitems');
        throw new BadParameterException($vars, $msg);
    }

    $dbconn = xarDB::getConn();
    $tables = xarDB::getTables();
    $types_table   = $tables['block_types'];
    $modules_table = $tables['modules'];

    // assemble query parts    
    $from = array();
    $join = array();
    $where = array();
    $bindvars = array();

    $from[] = "$types_table types";
    $join[] = "LEFT JOIN $modules_table mods ON mods.id = types.module_id";

    if (isset($module_id)) {
        $where[] = 'types.module_id = ?';
        $bindvars[] = $module_id;
    }

    if (!empty($type_category)) {
        $where[] = 'types.category = ?';
        $bindvars[] = $type_category;
    }
    if (!empty($type_state)) {
        $where[] = 'types.state IN (' . implode(',', array_fill(0, count($type_state), '?')) . ')';
        $bindvars = array_merge($bindvars, $type_state);
    }
    
    // build query
    $query = "SELECT COUNT(types.id)"; 
    $query .= " FROM " . join(',',$from);
    $query .= " " . join(' ',$join);
    if (!empty($where))
        $query .= ' WHERE ' . join(' AND ', $where);    
    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) return;    
    list($count) = $result->fields;

    $result->Close();
    
    return $count;    
}
?>