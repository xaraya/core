<?php
/**
 * @package modules\blocks
 * @scenario soloblock
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/13.html
 */

/**
 * Fetches multiple items from the API
 * 
 * @author Chris Powis <crisp@xaraya.com>
 * 
 * @param array $args Parameter data array
 * @return array Items fetched from API
 * @throws BadParameterException
 */
function blocks_typesapi_getitems(Array $args=array())
{
    extract($args);
    
    if (!empty($type_id) && !is_numeric($type_id))
        $invalid[] = 'type_id';

    if (!empty($type) && !is_string($type))
        $invalid[] = 'type';

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

    if (isset($module)) {
        if (empty($module)) {
            $module_id = 0;
        } elseif (!is_string($module) || 
            (!xarMod::isAvailable($module) && 
                (!xarVarIsCached('Blocks.event', 'modremove') || xarVarGetCached('Blocks.event',  'modremove') != $module))
            ) {
            $invalid[] = 'module';
        } else {
            $modinfo = xarMod::getBaseInfo($module);
            $module_id = $modinfo['systemid'];
        }
    }
    
    if (isset($module_id) && !is_numeric($module_id))
        $invalid[] = 'module_id';
    
    if (isset($startnum) && !is_numeric($startnum))
        $invalid[] = 'startnum';
    
    if (isset($numitems) && !is_numeric($numitems))
        $invalid[] = 'numitems';
    
    if (!empty($invalid)) {
        $msg = 'Invalid #(1) for #(2) module #(3) function #(4)()';
        $vars = array(join(', ', $invalid), 'blocks', 'typesapi', 'getitems');
        throw new BadParameterException($vars, $msg);
    }

    $dbconn = xarDB::getConn();
    $tables =& xarDB::getTables();
    $types_table   = $tables['block_types'];
    $modules_table = $tables['modules'];
    
    $select = array();
    $where = array();
    // $orderby = array(); todo
    $bindvars = array();

    $select['type_id'] = 'types.id';
    $select['type'] = 'types.type';
    $select['type_info'] = 'types.info';
    $select['type_category'] = 'types.category';
    $select['type_state'] = 'types.state';
    $select['module'] = 'mods.name';
        
    $query = "SELECT " . join(',',$select);
    $query .= " FROM $types_table types
                LEFT JOIN $modules_table mods ON mods.id = types.module_id";

    if (!empty($type_id)) {
        $where[] = 'types.id = ?';
        $bindvars[] = $type_id;
    }
    if (!empty($type)) {
        $where[] = 'types.type = ?';
        $bindvars[] = $type;
    }
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
    
    if (!empty($where))
        $query .= ' WHERE ' . join(' AND ', $where);
    
    if (empty($orderby)) {
        $orderby[] = 'types.type ASC';
        $orderby[] = 'mods.name ASC';    
    }
    
    if (!empty($orderby)) 
        $query .= ' ORDER BY ' . join(',', $orderby);    

    $stmt = $dbconn->prepareStatement($query);
    if (!empty($numitems)) {
        $stmt->setLimit($numitems);
        if (empty($startnum))
            $startnum = 1;
        $stmt->setOffset($startnum - 1);
    }

    $result = $stmt->executeQuery($bindvars);
    if (!$result) return;

    $types = array();
    while ($result->next()) {
        $item = array();
        foreach (array_keys($select) as $field) {
            $val = array_shift($result->fields);
            switch ($field) {
                case 'type_info':
                    // normalize content
                    $val = @unserialize($val);
                    $item[$field] = $val;
                    $item['content'] = $val;
                break;
                case 'module':
                    $item[$field] = !empty($val) ? $val : '';
                break;
                default:
                    $item[$field] = $val;
                break;
            }
        }
        $types[$item['type_id']] = $item;
    }
    $result->close();                  

    return $types;   
   
}
?>