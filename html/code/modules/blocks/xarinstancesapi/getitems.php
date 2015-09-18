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
 * Fetches items from API
 * 
 * @author Chris Powis <crisp@xaraya.com>
 * 
 * @param array $args Parameter data array
 * @return array Array of items fetched.
 * @throws BadParameterException
 */
function blocks_instancesapi_getitems(Array $args=array())
{
    extract($args);    

    if (isset($block_id)) {
        if (empty($block_id)) {
            $invalid[] = 'block_id';
        } elseif (is_numeric($block_id)) {
            $block_id = array($block_id);
        } elseif (is_array($block_id)) {
            foreach ($block_id as $dt) {
                if (!is_numeric($dt)) {
                    $invalid[] = 'block_id';
                    break;
                }
            }
        }
    }
      
    if (isset($name) && !is_string($name))
        $invalid[] = 'name';
        
    if (isset($state)) {
        if (is_numeric($state)) {
            $state = array($state);
        } elseif (is_array($state)) {
            foreach ($state as $dt) {
                if (!is_numeric($dt)) {
                    $invalid[] = 'state';
                    break;
                }
            }
        }
    }

    if (isset($type) && !is_string($type))
        $invalid[] = 'type';
        
    if (isset($type_id) && (empty($type_id) || !is_numeric($type_id)))
        $invalid[] = 'type_id';
    
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
        }else {
            $modinfo = xarMod::getBaseInfo($module);
            $module_id = $modinfo['systemid'];
        }
    }
    
    if (isset($type_category) && !is_string($type_category))
        $invalid[] = 'type_category';

    if (isset($filter) && !is_string($filter))
        $invalid[] = 'filter';
    
    if (isset($filter_fields) && !empty($filter_fields)) {
        if (is_string($filter_fields))
            $filter_fields = array($filter_fields);
        if (is_array($filter_fields)) {
            $allowed = array('name', 'type', 'module');
            foreach ($filter_fields as $dt) {
                if (!in_array($dt, $allowed) || !is_string($dt)) {
                    $invalid[] = 'filter_fields';
                    break;
                }
            }
        } else {
            $invalid[] = 'filter_fields';
        }
    }

    if (!empty($invalid)) {
        $msg = 'Invalid #(1) for #(2) module #(3) function #(4)()';
        $vars = array(join(', ',$invalid), 'blocks', 'instancesapi', 'getitems');
        throw new BadParameterException($vars, $msg);
    }

    $dbconn = xarDB::getConn();
    $tables =& xarDB::getTables();
    $blocks_table  = $tables['block_instances'];
    $types_table   = $tables['block_types'];
    $modules_table = $tables['modules'];

    // assemble query parts    
    $select = array();
    $from = array();
    $join = array();
    $where = array();
    $orderby = array();
    $groupby = array();
    $bindvars = array();

    $select['block_id'] = 'blocks.id';
    $select['name'] = 'blocks.name';    
    $select['title'] = 'blocks.title';
    $select['state'] = 'blocks.state';
    $select['content'] = 'blocks.content';
    $select['type_id'] = 'types.id';
    $select['type'] = 'types.type';
    $select['type_info'] = 'types.info';
    $select['type_state'] = 'types.state';
    $select['type_category'] = 'types.category';
    $select['module'] = 'mods.name';

    $from[] = "$blocks_table blocks";
    $from[] = "$types_table types";

    $join[] = "LEFT JOIN $modules_table mods ON mods.id = types.module_id";

    $where[] = 'blocks.type_id = types.id';

    if (!empty($block_id)) {
        $where[] = 'blocks.id IN (' . implode(',', array_fill(0, count($block_id), '?')) . ')';
        $bindvars = array_merge($bindvars, $block_id);
    }
    if (!empty($name)) {
        $where[] = 'blocks.name = ?';
        $bindvars[] = $name;
    }
    if (!empty($state)) {
        $where[] = 'blocks.state IN (' . implode(',', array_fill(0, count($state), '?')) . ')';
        $bindvars = array_merge($bindvars, $state);
    }    
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

    if (!empty($filter)) {
        if (empty($filter_fields))
            $filter_fields = array('name');
        $likes = array();
        foreach ($filter_fields as $field) {
            $likes[] = $select[$field] . " LIKE ?";
            $bindvars[] = "$filter%";
        }
        $where[] = '(' . join(' OR ', $likes) . ')';
    }
    
    if (empty($orderby)) {
        $orderby[] = 'blocks.name ASC';
        $orderby[] = 'mods.name ASC';
    }

    // build query from parts 
    $query = "SELECT " . join(',',$select);
    $query .= " FROM " . join(',',$from);
    $query .= " " . join(' ',$join);
    if (!empty($where))
        $query .= ' WHERE ' . join(' AND ', $where);    
    if (!empty($orderby))
        $query .= ' ORDER BY ' . join(',', $orderby);
    if (!empty($groupby))
        $query .= ' GROUP BY ' . join(',', $groupby);

    $stmt = $dbconn->prepareStatement($query);
    if (!empty($numitems)) {
        $stmt->setLimit($numitems);
        if (empty($startnum))
            $startnum = 1;
        $stmt->setOffset($startnum - 1);
    }

    $result = $stmt->executeQuery($bindvars);
    if (!$result) return;

    $items = array();
    while ($result->next()) {
        $item = array();
        foreach (array_keys($select) as $field) {
            $val = array_shift($result->fields);
            switch ($field) {
                case 'content':
                case 'type_info':
                    $val = @unserialize($val);                                      
                    $item[$field] = $val;
                break;
                case 'module':
                    $item[$field] = !empty($val) ? $val : '';
                break;
                default:
                    $item[$field] = $val;
                break;
            }
        }
        $items[$item['block_id']] = $item;
    }

    $result->close();
    return $items;  
}
?>