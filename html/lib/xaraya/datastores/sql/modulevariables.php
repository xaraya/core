<?php
/**
 * Data Store is the module variables // TODO: integrate module variable handling with DD
 *
 * @package core\datastores
 * @subpackage datastores
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

/**
 * Class to handle module variables datastores
 *
 */
sys::import('xaraya.datastores.sql.relational');

class ModuleVariablesDataStore extends RelationalDataStore
{
    public $modulename;
    public $variablename;

    function __construct($name=null)
    {
        parent::__construct($name);
        $this->setModvarName($name);
    }

    function __toString()
    {
        return "module_variables";
    }

    private function setModvarName($name="")
    {
        if (empty($name)) throw new Exception('Bad modvar name');
        $this->modulename = $name;
    }

    function getFieldName(DataProperty &$property)
    {
        return $property->name;
    }

    function getItem(Array $args = array())
    {
        $this->setModvarName($this->name);
        $itemid = !empty($args['itemid']) ? $args['itemid'] : 0;
        $fieldlist = $this->object->getFieldList();
        if (count($fieldlist) < 1) return;
        foreach ($fieldlist as $field) {
            $value = xarModItemVars::get($this->modulename,$field,$itemid);
            // set the value for this property
            $this->object->properties[$field]->value = $value;
        }
        return $itemid;
    }

    function createItem(Array $args = array())
    {
        return $this->updateItem($args);
    }

    function updateItem(Array $args = array())
    {
        $itemid = !empty($args['itemid']) ? $args['itemid'] : 0;
        $fieldlist = $this->object->getFieldList();
        if (count($fieldlist) < 1) return 0;

        foreach ($fieldlist as $field) {
            // get the value from the corresponding property
            $value = $this->object->properties[$field]->value;
            // skip fields where values aren't set
            if (!isset($value)) continue;
            if (empty($itemid)) {
                xarModVars::set($this->modulename,$field,$value);
            } else {
                xarModItemVars::set($this->modulename,$field,$value,$itemid);
            }
        }
        return $itemid;
    }

    function deleteItem(Array $args = array())
    {
        $itemid = !empty($args['itemid']) ? $args['itemid'] : 0;
        $fieldlist = $this->object->getFieldList();
        if (count($fieldlist) < 1) return 0;

        foreach ($fieldlist as $field) {
            xarModItemVars::delete($this->modulename,$field,$itemid);
        }

        return $itemid;
    }

    function getItems(Array $args = array())
    {
        // FIXME: only the last clause has been done!!

        if (!empty($args['numitems'])) {
            $numitems = $args['numitems'];
        } else {
            $numitems = 0;
        }
        if (!empty($args['startnum'])) {
            $startnum = $args['startnum'];
        } else {
            $startnum = 1;
        }
        if (!empty($args['itemids'])) {
            $itemids = $args['itemids'];
        } elseif (isset($this->_itemids)) {
            $itemids = $this->_itemids;
        } else {
            $itemids = array();
        }
        // check if it's set here - could be 0 (= empty) too
        if (isset($args['cache'])) {
            $this->cache = $args['cache'];
        }

        $properties = $this->object->getProperties();
        if (count($properties) < 1) {
            return;
        }
//                    var_dump(array_keys($this->fields));
//                    var_dump($this->groupby);exit;

        $modvars = $this->getTable('module_vars');
        $moditemvars = $this->getTable('module_itemvars');

        // easy case where we already know the items we want
        if (count($itemids) > 0) {
            // split the fields to be gotten up by module
            $modulefields['dynamicdata'] = array();
            foreach ($fields as $field) {
                $modulefields[$this->modulename][] = $field;
            }

            if (count($this->sort) > 0) {
                $items = array();
                $dosort = 1;
            } else {
                $dosort = 0;
            }

            foreach ($modulefields as $key => $values) {
                if (count($values)<1) continue;
                $modid = xarMod::getID($key);
                $bindmarkers = '?' . str_repeat(',?',count($values)-1);
                $query = "SELECT DISTINCT m.name,
                                 mi.item_id,
                                 mi.value
                            FROM $modvars m INNER JOIN $moditemvars mi ON m.id = mi.module_var_id
                           WHERE m.name IN ($bindmarkers) AND m.module_id = $modid";

                $bindvars = $values;
                if (count($itemids) > 1) {
                    $bindmarkers = '?' . str_repeat(',?',count($itemids)-1);
                    $query .= " AND mi.item_id IN ($bindmarkers) ";
                    foreach ($itemids as $itemid) {
                        $bindvars[] = (int) $itemid;
                    }
                } else {
                    $query .= " AND mi.item_id = ?";
                    $bindvars[] = (int)$itemids[0];
                }
                $stmt = $this->prepareStatement($query);
                $result = $stmt->executeQuery($bindvars);

                $itemidlist = array();
                while ($result->next()) {
                    list($field,$itemid,$value) = $result->getRow();
// CHECKME: no longer valid / needed !?
                    //if ($key != 'dynamic_data') $field .= '_' . $key;
                    $itemidlist[$itemid] = 1;
                    if (isset($value)) {
                        if ($dosort) {
                            $items[$itemid][$propid] = $value;
                        } else {
                            // add the item to the value list for this property
                            $this->fields[$field]->setItemValue($itemid,$value);
                        }
                    }
                }
                // add the itemids to the list
                $this->_itemids = array_keys($itemidlist);
                $result->close();
            }

            if ($dosort) {
                $code = '';
                foreach ($this->sort as $sortitem) {
                    $code .= 'if (!isset($a['.$sortitem['field'].'])) $a['.$sortitem['field'].'] = "";';
                    $code .= 'if (!isset($b['.$sortitem['field'].'])) $b['.$sortitem['field'].'] = "";';
                    $code .= 'if ($a['.$sortitem['field'].'] != $b['.$sortitem['field'].']) {';
                    if (!empty($sortitem['sortorder']) && strtolower($sortitem['sortorder']) == 'desc') {
                        $code .= 'return ($b['.$sortitem['field'].'] > $a['.$sortitem['field'].']) ? 1 : -1;';
                    } else {
                        $code .= 'return ($a['.$sortitem['field'].'] > $b['.$sortitem['field'].']) ? 1 : -1;';
                    }
                    $code .= '} else {';
                }
                $code .= 'return 0;';
                foreach ($this->sort as $sortitem) {
                    $code .= '}';
                }
                $compare = create_function('$a, $b', $code);
                uasort($items,$compare);
                foreach ($items as $itemid => $values) {
                    foreach ($values as $propid => $value) {
                        $this->fields[$propid]->setItemValue($itemid,$value);
                    }
                }
                unset($items);
            }

        // join between dynamic_data and another table
        // (all items, single key, no sort, DD where clauses limited to ORing)
        } elseif (count($this->join) > 0) {
            $tables = array();
            $fields = array();
            $keys = array();
            $where = array();
            $andor = 'AND';
            $more = '';
            foreach ($this->join as $info) {
                $tables[] = $info['table'];
                foreach ($info['fields'] as $field) {
                    $fields[] = $field;
                }
                if (!empty($info['key'])) {
                    $keys[] = $info['key'] . ' = itemid';
                }
                if (!empty($info['where'])) {
                    $where[] = '(' . $info['where'] . ')';
                }
                if (!empty($info['andor'])) {
                    $andor = $info['andor'];
                }
                if (!empty($info['more'])) {
                    $more .= ' ' . $info['more'];
                }
                // TODO: sort clauses for the joined table ?
            }
            $query = "SELECT DISTINCT itemid, propid, value";
            if (count($fields) > 0) {
                $query .= ", " . join(', ',$fields);
            }
            $query .= " FROM $dynamicdata, " . join(', ',$tables) . $more . "
                       WHERE propid IN (" . join(', ',$propids) . ") ";
            if (count($keys) > 0) {
                $query .= " AND " . join(' AND ', $keys);
            }
            if (count($where) > 0) {
                $query .= " AND ( " . join(' AND ', $where);
            }
            if (count($this->where) > 0) {
                $query .= " $andor ( ";
                // we're looking for combinations (propid + where clause) here - only OR is supported !
                // TODO: support pre- and post-parts here too ? (cfr. bug 3090)
                foreach ($this->where as $whereitem) {
                    $query .= $whereitem['join'] . " (propid = " . $whereitem['field'] . ' AND value ' . $whereitem['clause'] . ') ';
                }
                $query .= " )";
            }
            if (count($where) > 0) {
                $query .= " )";
            }

            // TODO: combine with sort someday ? Not sure if that's possible in this way...
            if ($numitems > 0) {
                // <mrb> Why is this only here?
                $query .= ' ORDER BY itemid, propid';
                $stmt = $this->prepareStatement($query);

                // Note : this assumes that every property of the items is stored in the table
                $numrows = $numitems * count($propids);
                if ($startnum > 1) {
                    $startrow = ($startnum - 1) * count($propids) + 1;
                } else {
                    $startrow = 1;
                }
                $stmt->setLimit($numrows);
                $stmt->setOffset($startrow-1);
            } else {
                $stmt = $this->prepareStatement($query);
            }
            // All prepared, lets go
            $result = $stmt->executeQuery();

            $itemidlist = array();
            while ($result->next()) {
                $values = $result->getRow();
                $itemid = array_shift($values);
                $itemidlist[$itemid] = 1;
                $propid = array_shift($values);
                $value = array_shift($values);
                if (isset($value)) {
                    // add the item to the value list for this property
                    $this->fields[$propid]->setItemValue($itemid,$value);
                }
                // save the extra fields too
                foreach ($fields as $field) {
                    $value = array_shift($values);
                    if (isset($value)) {
                        $this->extra[$field]->setItemValue($itemid,$value);
                    }
                }
            }
            // add the itemids to the list
            $this->_itemids = array_keys($itemidlist);
            $result->close();

        // TODO: make sure this is portable !
        // more difficult case where we need to create a pivot table, basically
        } elseif ($numitems > 0 || count($this->sort) > 0 || count($this->where) > 0 || count($this->groupby) > 0) {

            $dbtype = $this->getType();
            if (substr($dbtype,0,4) == 'oci8') {
                $propval = 'TO_CHAR(mi.value)';
            } elseif (substr($dbtype,0,5) == 'mssql') {
            // CHECKME: limited to 8000 characters ?
                $propval = 'CAST(mi.value AS VARCHAR(8000))';
            } else {
                $propval = 'mi.value';
            }

        /*
            Note : Alternate syntax for Postgres if contrib/tablefunc.sql is installed

            $query = "SELECT * FROM crosstab(
                'SELECT itemid, propid, value
                 FROM $dynamicdata
                 WHERE propid IN (" . join(', ',$propids) . ")
                 ORDER BY itemid, propid;', " . count($propids) . ")
            AS dd(itemid int, " . join(' text, ',$propids) . " text)";

            if (count($this->where) > 0) {
                $query .= " WHERE ";
                foreach ($this->where as $whereitem) {
                    $query .= $whereitem['join'] . ' ' . $whereitem['pre'] . $whereitem['field'] . ' ' . $whereitem['clause'] . $whereitem['post'] . ' ';
                }
            }
        */

            foreach ($properties as $field) {
                $this->setModvarName($field->name);
                $modulefields[$this->modulename][] = $field->name;
            }

            foreach ($modulefields as $key => $fieldvalues) {
                if (count($fieldvalues)<1) continue;
                $query = "SELECT mi.item_id ";
                foreach ($fieldvalues as $fieldvalue) {
                    $query .= ", MAX(CASE WHEN m.name = '" . $fieldvalue . "' THEN $propval ELSE '' END) AS $fieldvalue \n";
                }
                $bindmarkers = '?' . str_repeat(',?',count($fieldvalues)-1);
                $query .= " FROM $modvars m INNER JOIN $moditemvars mi ON m.id = mi.module_var_id
                           WHERE m.name IN ($bindmarkers)
                        GROUP BY mi.item_id ";
            if (count($this->where) > 0) {
                $query .= " HAVING ";
                foreach ($this->where as $whereitem) {
                    // Postgres does not support column aliases in HAVING clauses, but you can use the same aggregate function
                    if (substr($dbtype,0,8) == 'postgres') {
                        $query .= $whereitem['join'] . ' ' . $whereitem['pre'] . 'MAX(CASE WHEN m.name = ' . $whereitem['field'] . " THEN $propval ELSE '' END) " . $whereitem['clause'] . $whereitem['post'] . ' ';
                    } else {
                        $query .= $whereitem['join'] . ' ' . $whereitem['pre'] . $whereitem['field'] . ' ' . $whereitem['clause'] . $whereitem['post'] . ' ';
                    }
                }
            }

            if (count($this->sort) > 0) {
                $query .= " ORDER BY ";
                $join = '';
                foreach ($this->sort as $sortitem) {
                    $query .= $join . $sortitem['field'] . ' ' . $sortitem['sortorder'];
                    $join = ', ';
                }
            }

            // we got the query
            $stmt = $this->prepareStatement($query);

            if ($numitems > 0) {
                $stmt->setLimit($numitems);
                $stmt->setOffset($startnum - 1);
            }
            // All prepared, run it
            $result = $stmt->executeQuery($fieldvalues);


            $isgrouped = 0;
            if (count($this->groupby) > 0) {
                $groupbys = array();
                foreach ($this->groupby as $groupby) {
                    $this->setModvarName(groupby);
                    $groupbys[] = $this->modulename;
                }
                $isgrouped = 1;
                $items = array();
                $combo = array();
                $id = 0;
                $process = array();
                foreach ($fieldvalues as $fieldvalue) {
                    if (in_array($fieldvalue,$this->groupby)) {
                        continue;
                    } elseif (empty($this->fields[$fieldvalue]->operation)) {
                        continue; // all fields should be either GROUP BY or have some operation
                    }
                    array_push($process, $fieldvalue);
                }
            }
            while ($result->next()) {
                $values = $result->getRow();
                $itemid = array_shift($values);
                // oops, something went seriously wrong here...
                if (empty($itemid) || count($values) != count($properties)) {
                    continue;
                }
                if (!$isgrouped) {
                    // add this itemid to the list
                    $this->_itemids[] = $itemid;

                    foreach ($fieldvalues as $fieldvalue) {
                        // add the item to the value list for this property
                        $this->fields[$fieldvalue]->setItemValue($itemid,array_shift($values));
                    }
                } else {
                    // TODO: use sub-query to do this in the database for MySQL 4.1+ and others ?
                    $propval = array();
                    foreach ($fieldvalues as $fieldvalue) {
                        $propval[$fieldvalue] = array_shift($values);
                    }
                    $groupid = '';
                    foreach ($groupbys as $field) {
                        $groupid .= $propval[$field] . '~';
                    }
                    if (!isset($combo[$groupid])) {
                        $id++;
                        $combo[$groupid] = $id;
                        // add this "itemid" to the list
                        $this->_itemids[] = $id;
                        foreach ($this->groupby as $field) {
                            // add the item to the value list for this property
                            $this->setModvarName($field);
                            $this->fields[$field]->setItemValue($id,$propval[$this->modulename]);
                        }
                        foreach ($process as $field) {
                            // add the item to the value list for this property
                            $this->fields[$field]->setItemValue($id,null);
                        }
                    }
                    $curid = $combo[$groupid];
                    foreach ($process as $field) {
                        $curval = $this->fields[$field]->getItemValue($curid);
                        switch ($this->fields[$field]->operation) {
                            case 'COUNT':
                                if (!isset($curval)) {
                                    $curval = 0;
                                }
                                $curval++;
                                break;
                            case 'SUM':
                                if (!isset($curval)) {
                                    $curval = $propval[$field];
                                } else {
                                    $curval += $propval[$field];
                                }
                                break;
                            case 'MIN':
                                if (!isset($curval)) {
                                    $curval = $propval[$field];
                                } elseif ($curval > $propval[$field]) {
                                    $curval = $propval[$field];
                                }
                                break;
                            case 'MAX':
                                if (!isset($curval)) {
                                    $curval = $propval[$field];
                                } elseif ($curval < $propval[$field]) {
                                    $curval = $propval[$field];
                                }
                                break;
                            case 'AVG':
                                if (!isset($curval)) {
                                    $curval = array('total' => $propval[$field], 'count' => 1);
                                } else {
                                    $curval['total'] += $propval[$field];
                                    $curval['count']++;
                                }
                                // TODO: divide total by count afterwards
                                break;
                            default:
                                break;
                        }
                        $this->fields[$field]->setItemValue($curid,$curval);
                    }
                }
            }
            }
            $result->close();

            // divide total by count afterwards
            if ($isgrouped) {
                $divide = array();
                foreach ($process as $field) {
                    if ($this->fields[$field]->operation == 'AVG') {
                        $divide[] = $field;
                    }
                }
                if (count($divide) > 0) {
                    foreach ($this->_itemids as $curid) {
                        foreach ($divide as $field) {
                            $curval = $this->fields[$field]->getItemValue($curid);
                            if (!empty($curval) && is_array($curval) && !empty($curval['count'])) {
                                $newval = $curval['total'] / $curval['count'];
                                $this->fields[$field]->setItemValue($curid,$newval);
                            }
                        }
                    }
                }
            }

        // here we grab everyting
        } else {
            // split the fields to be gotten up by module
            foreach ($properties as $field) {
                $this->setModvarName($field->name);
                $modulefields[$this->modulename]['name'] = $field->name;
                $modulefields[$this->modulename]['source'] = $field->source;
            }

            foreach ($modulefields as $key => $values) {
                if (count($values)<1) continue;
                $modid = xarMod::getID(substr(trim($values['source']),17));
                $bindmarkers = '?' . str_repeat(',?',count($values['name'])-1);
                $query = "SELECT DISTINCT m.name,
                                 mi.item_id,
                                 mi.value
                            FROM $modvars m INNER JOIN $moditemvars mi ON m.id = mi.module_var_id
                           WHERE m.name IN ($bindmarkers) AND m.module_id = $modid";

                $stmt = $this->prepareStatement($query);
                $result = $stmt->executeQuery($values);

                $itemidlist = array();
                while ($result->next()) {
                    list($field,$itemid,$value) = $result->getRow();
                    if ($key != 'dynamic_data') $field .= '_' . $key;
                    $itemidlist[$itemid] = 1;
                    if (isset($value)) {
                        // add the item to the value list for this property
                        $this->fields[$field]->setItemValue($itemid,$value);
                    }
                }
                // add the itemids to the list
                $this->_itemids = array_keys($itemidlist);
                $result->close();
            }
        }
    }

    function countItems(Array $args = array())
    {
        if (!empty($args['itemids'])) {
            $itemids = $args['itemids'];
        } elseif (isset($this->_itemids)) {
            $itemids = $this->_itemids;
        } else {
            $itemids = array();
        }
        // check if it's set here - could be 0 (= empty) too
        if (isset($args['cache'])) {
            $this->cache = $args['cache'];
        }

        $modvars = $this->getTable('module_vars');
        $moditemvars = $this->getTable('module_itemvars');

        // easy case where we already know the items we want
        if (count($itemids) > 0) {
            $bindmarkers = '?' . str_repeat(',?',count($fields)-1);
            if($this->getType() == 'sqlite') {
                $query = "SELECT COUNT(*)
                          FROM (SELECT DISTINCT mi.item_id
                                FROM $modvars m INNER JOIN $moditemvars mi ON m.id = mi.module_var_id
                                WHERE mi.name IN ($bindmarkers) "; // WATCH OUT, STILL UNBALANCED
            } else {
                $query = "SELECT COUNT(DISTINCT mi.item_id)
                        FROM $modvars m INNER JOIN $moditemvars mi ON m.id = mi.module_var_id
                       WHERE m.name IN ($bindmarkers) ";
            }
            $bindvars = $fields;

            if (count($itemids) > 1) {
                $bindmarkers = '?' . str_repeat(',?',count($itemids)-1);
                $query .= " AND mi.item_id IN ($bindmarkers) ";
                foreach ($itemids as $itemid) {
                    $bindvars[] = (int) $itemid;
                }
            } else {
                $query .= " AND mi.item_id = ? ";
                $bindvars[] = (int)$itemids[0];
            }

            // Balance parentheses.
            if($this->getType() == 'sqlite') $query .= ")";

            $stmt = $this->prepareStatement($query);
            $result = $stmt->executeQuery($bindvars);

            if ($result->first()) return;
            $numitems = $result->getInt(1);
            $result->close();

            return $numitems;

            // TODO: make sure this is portable !
        } elseif (count($this->where) > 0) {
            // more difficult case where we need to create a pivot table, basically
            // TODO: this only works for OR conditions !!!
            if($this->getType() == 'sqlite') {
                $query = "SELECT COUNT(*)
                          FROM ( SELECT DISTINCT mi.item_id FROM $modvars m INNER JOIN $moditemvars mi ON m.id = mi.module_var_id
                          WHERE "; // WATCH OUT, STILL UNBALANCED
            } else {
                $query = "SELECT COUNT(DISTINCT mi.item_id)
                        FROM $modvars m INNER JOIN $moditemvars mi ON m.id = mi.module_var_id
                       WHERE ";
            }
            // only grab the fields we're interested in here...
            // TODO: support pre- and post-parts here too ? (cfr. bug 3090)
            foreach ($this->where as $whereitem) {
                $query .= $whereitem['join'] . ' (m.name = ' . $whereitem['field'] . ' AND mi.value ' . $whereitem['clause'] . ') ';
            }

            // Balance parentheses.
            if($this->getType() == 'sqlite') $query .= ")";

            $stmt = $this->prepareStatement($query);
            $result = $stmt->executeQuery();
            if (!$result->first()) return;

            $numitems = $result->getInt(1);
            $result->close();

            return $numitems;

        // here we grab everyting
        } else {

            $properties = $this->object->getProperties();

            // split the fields to be gotten up by module
            foreach ($properties as $field) {
                $this->setModvarName($field->name);
                $modulefields[$this->modulename]['name'] = $field->name;
                $modulefields[$this->modulename]['source'] = $field->source;
            }
            $numitems = 0;
            foreach ($modulefields as $key => $values) {
                if (count($values)<1) continue;
                $modid = xarMod::getID(substr(trim($values['source']),17));
                $bindmarkers = '?' . str_repeat(',?',count($values['name'])-1);
                if($this->getType() == 'sqlite' ) {
                    $query = "SELECT COUNT(*)
                              FROM (SELECT DISTINCT mi.item_id FROM $modvars m INNER JOIN $moditemvars mi ON m.id = mi.module_var_id
                              WHERE m.name IN ($bindmarkers)) AND m.module_id = $modid";
                } else {
                    $query = "SELECT COUNT(DISTINCT mi.item_id)
                              FROM $modvars m INNER JOIN $moditemvars mi ON m.id = mi.module_var_id
                              WHERE m.name IN ($bindmarkers) AND m.module_id = $modid";
                }

                $stmt = $this->prepareStatement($query);
                $result = $stmt->executeQuery($values);
                if (!$result->first()) return;

                $numitems += $result->getInt(1);
                $result->close();
            }

            return $numitems;
        }
    }

}

?>