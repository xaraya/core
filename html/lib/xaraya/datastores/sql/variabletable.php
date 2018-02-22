<?php
/**
 * Data Store is a variable SQL table (= only xar_dynamic_data for now)
 *
 * @package core\datastores
 * @subpackage datastores
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
**/

/*
 * Parent class is SQL datastore
 *
 */
sys::import('xaraya.datastores.sql');

/**
 * Data store is a variable SQL table
 *
**/
class VariableTableDataStore extends SQLDataStore
{
    protected $table = 'dynamic_data';
    
    /**
     * Get the field name used to identify this property (we use the property id here)
     */
    function getFieldName(DataProperty &$property)
    {
        return (int)$property->id;
    }
    /**
     * Get the item
     * @param id $args['itemid']
     */
    function getItem(array $args = array())
    {
        // Get the itemid from the params or from the object definition
        $itemid = isset($args['itemid']) ? $args['itemid'] : $this->object->itemid;

        if (empty($itemid))
            throw new Exception(xarML('Cannot get itemid 0'));

        //Make sure we have a primary field
        if (empty($this->object->primary)) throw new Exception(xarML('The object #(1) has no primary key', $this->object->name));

        // Bail if the object has no properties
        if (count($this->object->properties) < 1) return;
        
        $propids = array();
        $propnames = array_keys($this->object->properties);
        $properties = array();
        foreach ($this->object->properties as $property) {
            $propids[] = $property->id;
            $properties[$property->id] = $property;
        }
        if (count($propids) < 1) return;

        $dynamicdata = $this->getTable($this->table);

        $bindmarkers = '?' . str_repeat(',?',count($propids)-1);
        $query = "SELECT property_id, value
                  FROM $dynamicdata
                  WHERE property_id IN ($bindmarkers) AND
                        item_id = ?";
        $bindvars = $propids;
        $bindvars[] = (int)$itemid;
        $stmt = $this->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if(!$result->getRecordCount()) return;

        while ($result->next()) {
            list($propid, $value) = $result->getRow();
            if (isset($value)) {
                // set the value for this property
                $properties[$propid]->value = $value;
            }
        }
        $result->close();
        return $itemid;
    }
    /**
     * Create an item
     * @param array $args with $itemid,
     */
    function createItem(array $args = array())
    {
        // Bail if the object has no properties
        if (count($this->object->properties) < 1) return;

        // Get the itemid from the params or from the object definition
        $itemid = isset($args['itemid']) ? $args['itemid'] : $this->object->itemid;

        // we need to manage our own item ids here, and we can't use some sequential field
        if (empty($itemid)) {
            $itemid = $this->getNextId($this->object->objectid);
            if (empty($itemid)) return;
            if (isset($this->object->primary)) {
                $this->object->properties[$this->object->primary]->setValue($itemid);
            }
        }

        $props = array_keys($this->object->properties);

        $dynamicdata = $this->getTable($this->table);
        foreach ($props as $prop) {
            // ignore empty datasource
            if (empty($this->object->properties[$prop]->source)) continue;

            // get the id and value from the corresponding property
            $propid = $this->object->properties[$prop]->id;
            $value = $this->object->properties[$prop]->value;

            // invalid prop_id or undefined value (empty is OK, though !)
            if (empty($propid) || !is_numeric($propid) || !isset($value)) continue;

            $query = "INSERT INTO $dynamicdata (property_id,item_id,value)
                      VALUES (?,?,?)";
            $bindvars = array($propid,$itemid, (string) $value);
            $stmt = $this->db->prepareStatement($query);
            $stmt->executeUpdate($bindvars);
        }
        return $itemid;
    }

    /**
     * Update the item
     * @param id $itemid in array $args
     * @return id $itemid
     */
    function updateItem(array $args = array())
    {
        // Get the itemid from the params or from the object definition
        $itemid = isset($args['itemid']) ? $args['itemid'] : $this->object->itemid;

        if (empty($itemid))
            throw new BadParameterException(xarML('Cannot update itemid 0'));

        // Bail if the object has no properties
        if (count($this->object->properties) < 1) return $itemid;

        $goodproperties = array();
        foreach ($this->object->fieldlist as $key => $fieldname) {
            $field = $this->object->properties[$fieldname];
            $fieldtablealias = explode('.', $field->source);
            if (empty($field->source)) {
                // Ignore fields with no source
                continue;
            } elseif ($field->name == $this->object->primary) {
                // Ignore the primary value
                continue;
            } elseif ($field->getInputStatus() == DataPropertyMaster::DD_INPUTSTATE_IGNORED) {
                // Ignore the fields with IGNORE status
                continue;
            } elseif (isset($args[$field->name])) {
                // We have an override through the methods parameters
                // Encrypt if required
                if (!empty($field->initialization_encrypt))
                    throw new Exception(xarML('Cannot encrypt data for variable table store'));
            } else {
                // No override, just take the value the property already has
                // Encrypt if required
                if (!empty($field->initialization_encrypt)) {
                    throw new Exception(xarML('Cannot encrypt data for variable table store'));
                }
            }
            $goodproperties[$fieldname] =& $this->object->properties[$fieldname];
        }

        $props = array_keys($goodproperties);

        $propids = array();
        foreach ($this->object->properties as $prop) $propids[] = $prop->id;

        $dynamicdata = $this->getTable($this->table);

        // get the current dynamic data fields for all properties of this item
        $bindmarkers = '?' . str_repeat(',?',count($propids)-1);
        $query = "SELECT id, property_id
                  FROM $dynamicdata
                  WHERE property_id IN ($bindmarkers) AND
                        item_id = ?";
        $bindvars = $propids;
        $bindvars[] = (int)$itemid;

        $stmt = $this->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);

        $datafields = array();
        while ($result->next()) {
            list($id,$propid) = $result->getRow();
            $datafields[$propid] = $id;
        }
        $result->close();

        foreach ($props as $prop) {
            // ignore empty datasource
            if (empty($this->object->properties[$prop]->source)) continue;

            // get the id and value from the corresponding property
            $propid = $this->object->properties[$prop]->id;
            $value = $this->object->properties[$prop]->value;

            // invalid prop_id or undefined value (empty is OK, though !)
            if (empty($propid) || !is_numeric($propid) || !isset($value)) {
                continue;
            }

            // update the dynamic data field if it exists
            if (!empty($datafields[$propid])) {
                $query = "UPDATE $dynamicdata SET value = ? WHERE id = ?";
                $bindvars = array((string) $value, $datafields[$propid]);
            // or create it if necessary (e.g. when you add properties afterwards etc.)
            } else {
                $query = "INSERT INTO $dynamicdata
                            (property_id, item_id, value)
                          VALUES (?,?,?)";
                $bindvars = array($propid,$itemid, (string) $value);
            }
            $stmt = $this->prepareStatement($query);
            $stmt->executeUpdate($bindvars);
        }
        return $itemid;
    }

    function deleteItem(array $args = array())
    {
        // Get the itemid from the params or from the object definition
        $itemid = isset($args['itemid']) ? $args['itemid'] : $this->object->itemid;

        // Bail if the object has no properties
        if (count($this->object->properties) < 1) return $itemid;

        $propids = array();
        foreach ($this->object->properties as $prop) $propids[] = $prop->id;
        $dynamicdata = $this->getTable($this->table);

        // get the current dynamic data fields for all properties of this item
        $bindmarkers = '?' . str_repeat(',?', count($propids) -1);
        $query = "DELETE FROM $dynamicdata
                  WHERE property_id IN ($bindmarkers) AND
                        item_id = ?";
        $bindvars = $propids;
        $bindvars[] = (int)$itemid;
        $stmt = $this->prepareStatement($query);
        $stmt->executeUpdate($bindvars);
        return $itemid;
    }

    function getItems(array $args = array())
    {
        // Bail if no properties have yet been defined
        if(count($this->object->properties) == 0) return array();
        
        // FIXME: this is a hack
        if (!empty($this->object->where) && !is_array($this->object->where)) {
            $this->object->where = array($this->object->where);
        }
        
        if (!empty($args['numitems'])) {
            $this->object->numitems = $args['numitems'];
        } else {
            $this->object->numitems = 0;
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

        $propids = array();
        $propnames = array_keys($this->object->properties);
        $properties = array();
        foreach ($this->object->properties as $property) {
            $propids[] = $property->id;
            $properties[$property->id] = $property;
        }
        if (count($propids) < 1) return;

        $process = array();
        foreach ($propids as $propid) {
            if (!empty($this->object->groupby) && in_array($propid,$this->object->groupby)) {
                continue;
            } elseif (empty($properties[$propid]->operation)) {
                continue; // all fields should be either GROUP BY or have some operation
            }
            $process = $propid;
        }

        $dynamicdata = $this->getTable($this->table);

        // ------------------------------------------------------
        // easy case where we already know the items we want
        if (count($itemids) > 0) {
            $bindmarkers = '?' . str_repeat(',?',count($propids)-1);
            $query = "SELECT item_id, property_id, value
                      FROM $dynamicdata
                      WHERE property_id IN ($bindmarkers) ";
            $bindvars = $propids;

            if (count($itemids) > 1) {
                $bindmarkers = '?' . str_repeat(',?',count($itemids)-1);
                $query .= " AND item_id IN ($bindmarkers) ";
                foreach ($itemids as $itemid) {
                    $bindvars[] = (int) $itemid;
                }
            } else {
                $query .= " AND item_id = ?";
                $bindvars[] = (int)$itemids[0];
            }

            // CHECKME: there was a cache execute here, it N/A anymore now, as the method is non-existent.
            $stmt = $this->prepareStatement($query);
            $result = $stmt->executeQuery($bindvars);

            if (count($this->object->sort) > 0) {
                $items = array();
                $dosort = 1;
            } else {
                $dosort = 0;
            }

            while ($result->next()) {
                list($itemid,$propid, $value) = $result->getRow();
                if (isset($value)) {
                    if ($dosort) {
                        $items[$itemid][$propid] = $value;
                    } else {
                        // add the item to the value list for this property
                        $properties[$propid]->setItemValue($itemid,$value);
                    }
                }
            }
            $result->close();

            if ($dosort) {
                $code = '';
                foreach ($this->object->sort as $sortitem) {
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
                foreach ($this->object->sort as $sortitem) {
                    $code .= '}';
                }
                $compare = create_function('$a, $b', $code);
                uasort($items,$compare);
                foreach ($items as $itemid => $values) {
                    foreach ($values as $propid => $value) {
                        $properties[$propid]->setItemValue($itemid,$value);
                    }
                }
                unset($items);
            }

        // ------------------------------------------------------
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
            $query = "SELECT DISTINCT item_id, property_id, value";
            if (count($fields) > 0) {
                $query .= ", " . join(', ',$fields);
            }
            $query .= " FROM $dynamicdata, " . join(', ',$tables) . $more . "
                       WHERE property_id IN (" . join(', ',$propids) . ") ";
            if (count($keys) > 0) {
                $query .= " AND " . join(' AND ', $keys);
            }
            if (count($where) > 0) {
                $query .= " AND ( " . join(' AND ', $where);
            }
            if (count($this->where) > 0) {
                $query .= " $andor ( ";
                // we're looking for combinations (property_id + where clause) here - only OR is supported !
                // TODO: support pre- and post-parts here too ? (cfr. bug 3090)
                foreach ($this->where as $whereitem) {
                    $query .= $whereitem['join'] . " (property_id = " . $whereitem['field'] . ' AND value ' . $whereitem['clause'] . ') ';
                }
                $query .= " )";
            }
            if (count($where) > 0) {
                $query .= " )";
            }

            // TODO: combine with sort someday ? Not sure if that's possible in this way...
            if ($this->object->numitems > 0) {
                // <mrb> Why is this only here?
                $query .= ' ORDER BY item_id, property_id';
                $stmt = $this->prepareStatement($query);

                // Note : this assumes that every property of the items is stored in the table
                $numrows = $this->object->numitems * count($propids);
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
                    $properties[$propid]->setItemValue($itemid,$value);
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

        // ------------------------------------------------------
        // TODO: make sure this is portable !
        // more difficult case where we need to create a pivot table, basically
        } elseif ($this->object->numitems > 0 || !empty($this->object->sort) || !empty($this->object->where) || !empty($this->object->groupby)) {

            $dbtype = $this->getType();
            if (substr($dbtype,0,4) == 'oci8') {
                $propval = 'TO_CHAR(value)';
            } elseif (substr($dbtype,0,5) == 'mssql') {
            // CHECKME: limited to 8000 characters ?
                $propval = 'CAST(value AS VARCHAR(8000))';
            } else {
                $propval = 'value';
            }

        /*
            Note : Alternate syntax for Postgres if contrib/tablefunc.sql is installed

            $query = "SELECT * FROM crosstab(
                'SELECT item_id, property_id, value
                 FROM $dynamicdata
                 WHERE property_id IN (" . join(', ',$propids) . ")
                 ORDER BY item_id, property_id;', " . count($propids) . ")
            AS dd(item_id int, " . join(' text, ',$propids) . " text)";

            if (count($this->object->where) > 0) {
                $query .= " WHERE ";
                foreach ($this->object->where as $whereitem) {
                    $query .= $whereitem['join'] . ' ' . $whereitem['pre'] . $whereitem['field'] . ' ' . $whereitem['clause'] . $whereitem['post'] . ' ';
                }
            }
        */

            $query = "SELECT item_id ";
            foreach ($propids as $propid) {
                $query .= ", MAX(CASE WHEN property_id = $propid THEN $propval ELSE '' END) AS dd_$propid \n";
            }
            $query .= " FROM $dynamicdata
                       WHERE property_id IN (" . join(', ',$propids) . ")
                    GROUP BY item_id ";
/*
            if (count($this->object->where) > 0) {
                  $query .= " HAVING ";
                foreach ($this->object->where as $whereitem) {
                    // Postgres does not support column aliases in HAVING clauses, but you can use the same aggregate function
                    if (substr($dbtype,0,8) == 'postgres') {
                        $query .= $whereitem['join'] . ' ' . $whereitem['pre'] . 'MAX(CASE WHEN property_id = ' . 'dd_' . $whereitem['field'] . " THEN $propval ELSE '' END) " . $whereitem['clause'] . $whereitem['post'] . ' ';
                    } else {
                        $query .= $whereitem['join'] . ' ' . $whereitem['pre'] . 'dd_' . $whereitem['field'] . ' ' . $whereitem['clause'] . $whereitem['post'] . ' ';
                    }
                }
            }

            if (count($this->object->sort) > 0) {
                $query .= " ORDER BY ";
                $join = '';//var_dump($this->object->sort);exit;
                foreach ($this->object->sort as $sortitem) {
                    if (empty($sortitem)) continue;
                    $query .= $join . 'dd_' . $sortitem['field'] . ' ' . $sortitem['sortorder'];
                    $join = ', ';
                }
            }
            */

            // we got the query
            $stmt = $this->prepareStatement($query);

            if ($this->object->numitems > 0) {
                $stmt->setLimit($this->object->numitems);
                $stmt->setOffset($startnum - 1);
            }
            // All prepared, run it
            $result = $stmt->executeQuery();

            $isgrouped = 0;
            if (count($this->object->groupby) > 0) {
                $isgrouped = 1;
                $items = array();
                $combo = array();
                $id = 0;
                $process = array();
                foreach ($propids as $propid) {
                    if (in_array($propid,$this->object->groupby)) {
                        // Note: we'll process the *TIME_BY_* operations for the groupid
                        continue;
                    } elseif (empty($properties[$propid]->operation)) {
                        continue; // all fields should be either GROUP BY or have some operation
                    }
                    array_push($process, $propid);
                }
            }
            while ($result->next()) {
                $values = $result->getRow();
                $itemid = array_shift($values);
                // oops, something went seriously wrong here...
                if (empty($itemid) || count($values) != count($propids)) {
                    continue;
                }
                if (!$isgrouped) {
                    // add this itemid to the list
                    $this->_itemids[] = $itemid;

                    foreach ($propids as $propid) {
                        // add the item to the value list for this property
                        $properties[$propid]->setItemValue($itemid,array_shift($values));
                    }
                } else {
                    // TODO: use sub-query to do this in the database for MySQL 4.1+ and others ?
                    $propval = array();
                    foreach ($propids as $propid) {
                        $propval[$propid] = array_shift($values);
                    }
                    $groupid = '';
                    foreach ($this->object->groupby as $propid) {
                        // handle *TIME_BY_* operations here
                        if (!empty($propval[$propid]) && !empty($properties[$propid]->operation)) {
                            switch ($properties[$propid]->operation) {
                                case 'UNIXTIME_BY_YEAR':
                                    $propval[$propid] = gmdate('Y',$propval[$propid]);
                                    break;
                                case 'UNIXTIME_BY_MONTH':
                                    $propval[$propid] = gmdate('Y-m',$propval[$propid]);
                                    break;
                                case 'UNIXTIME_BY_DAY':
                                    $propval[$propid] = gmdate('Y-m-d',$propval[$propid]);
                                    break;
                                case 'DATETIME_BY_YEAR':
                                    $propval[$propid] = substr($propval[$propid],0,4);
                                    break;
                                case 'DATETIME_BY_MONTH':
                                    $propval[$propid] = substr($propval[$propid],0,7);
                                    break;
                                case 'DATETIME_BY_DAY':
                                    $propval[$propid] = substr($propval[$propid],0,10);
                                    break;
                                default:
                                    break;
                            }
                        }
                        $groupid .= $propval[$propid] . '~';
                    }
                    if (!isset($combo[$groupid])) {
                        $id++;
                        $combo[$groupid] = $id;
                        // add this "itemid" to the list
                        $this->_itemids[] = $id;
                        foreach ($this->object->groupby as $propid) {
                            // add the item to the value list for this property
                            $properties[$propid]->setItemValue($id,$propval[$propid]);
                        }
                        foreach ($process as $propid) {
                            // add the item to the value list for this property
                            $properties[$propid]->setItemValue($id,null);
                        }
                    }
                    $curid = $combo[$groupid];
                    foreach ($process as $propid) {
                        $curval = $properties[$propid]->getItemValue($curid);
                        switch ($properties[$propid]->operation) {
                            case 'COUNT':
                                if (!isset($curval)) {
                                    $curval = 0;
                                }
                                $curval++;
                                break;
                            case 'SUM':
                                if (!isset($curval)) {
                                    $curval = $propval[$propid];
                                } else {
                                    $curval += $propval[$propid];
                                }
                                break;
                            case 'MIN':
                                if (!isset($curval)) {
                                    $curval = $propval[$propid];
                                } elseif ($curval > $propval[$propid]) {
                                    $curval = $propval[$propid];
                                }
                                break;
                            case 'MAX':
                                if (!isset($curval)) {
                                    $curval = $propval[$propid];
                                } elseif ($curval < $propval[$propid]) {
                                    $curval = $propval[$propid];
                                }
                                break;
                            case 'AVG':
                                if (!isset($curval)) {
                                    $curval = array('total' => $propval[$propid], 'count' => 1);
                                } else {
                                    $curval['total'] += $propval[$propid];
                                    $curval['count']++;
                                }
                                // Note: divide total by count afterwards
                                break;
                            case 'COUNT_DISTINCT':
                                if (!isset($curval)) {
                                    $curval = array();
                                }
                                $curval[$propval[$propid]] = 1;
                                // Note: count distinct keys afterwards
                                break;
                            case 'UNIXTIME_BY_YEAR': // etc. - do nothing
                            default:
                                break;
                        }
                        $properties[$propid]->setItemValue($curid,$curval);
                    }
                }
            }
            $result->close();

            // divide total by count afterwards
            // count distinct keys afterwards
            if ($isgrouped) {
                $divide = array();
                $distinct = array();
                foreach ($process as $propid) {
                    if ($properties[$propid]->operation == 'AVG') {
                        $divide[] = $propid;
                    } elseif ($properties[$propid]->operation == 'COUNT_DISTINCT') {
                        $distinct[] = $propid;
                    }
                }
                if (count($divide) > 0) {
                    foreach ($this->_itemids as $curid) {
                        foreach ($divide as $propid) {
                            $curval = $properties[$propid]->getItemValue($curid);
                            if (!empty($curval) && is_array($curval) && !empty($curval['count'])) {
                                $newval = $curval['total'] / $curval['count'];
                                $properties[$propid]->setItemValue($curid,$newval);
                            }
                        }
                    }
                }
                if (count($distinct) > 0) {
                    foreach ($this->_itemids as $curid) {
                        foreach ($distinct as $propid) {
                            $curval = $properties[$propid]->getItemValue($curid);
                            if (!empty($curval) && is_array($curval)) {
                                $newval = count(array_keys($curval));
                                $properties[$propid]->setItemValue($curid,$newval);
                            }
                        }
                    }
                }
            }

        // ------------------------------------------------------
        // here we grab everyting and process it - TODO: better way to do this ?
        } elseif (count($process) > 0) {
            $bindmarkers = '?' . str_repeat(',?',count($propids)-1);
            $query = "SELECT DISTINCT property_id,
                             item_id,
                             value
                        FROM $dynamicdata
                       WHERE property_id IN ($bindmarkers)";

            $stmt = $this->prepareStatement($query);
            $result = $stmt->executeQuery($propids);

            // we only have one "itemid" with the result of the operations
            $curid = 1;
            foreach ($process as $propid) {
                // add the item to the value list for this property
                $properties[$propid]->setItemValue($curid,null);
            }

            while ($result->next()) {
                list($propid,$itemid,$value) = $result->getRow();
                if (isset($value)) {
                    $curval = $properties[$propid]->getItemValue($curid);
                    switch ($properties[$propid]->operation) {
                        case 'COUNT':
                            if (!isset($curval)) {
                                $curval = 0;
                            }
                            $curval++;
                            break;
                        case 'SUM':
                            if (!isset($curval)) {
                                $curval = $value;
                            } else {
                                $curval += $value;
                            }
                            break;
                        case 'MIN':
                            if (!isset($curval)) {
                                $curval = $value;
                            } elseif ($curval > $value) {
                                $curval = $value;
                            }
                            break;
                        case 'MAX':
                            if (!isset($curval)) {
                                $curval = $value;
                            } elseif ($curval < $value) {
                                $curval = $value;
                            }
                            break;
                        case 'AVG':
                            if (!isset($curval)) {
                                $curval = array('total' => $value, 'count' => 1);
                            } else {
                                $curval['total'] += $value;
                                $curval['count']++;
                            }
                            // Note: divide total by count afterwards
                            break;
                        case 'COUNT_DISTINCT':
                            if (!isset($curval)) {
                                $curval = array();
                            }
                            $curval[$value] = 1;
                            // Note: count distinct keys afterwards
                            break;
                        default:
                            break;
                    }
                    $properties[$propid]->setItemValue($curid,$curval);
                }
            }
            // add this "itemid" to the list
            $this->_itemids[] = $curid;
            $result->close();

            // divide total by count afterwards
            // count distinct keys afterwards
            $divide = array();
            $distinct = array();
            foreach ($process as $propid) {
                if ($properties[$propid]->operation == 'AVG') {
                    $divide[] = $propid;
                } elseif ($properties[$propid]->operation == 'COUNT_DISTINCT') {
                    $distinct[] = $propid;
                }
            }
            if (count($divide) > 0) {
                foreach ($this->_itemids as $curid) {
                    foreach ($divide as $propid) {
                        $curval = $properties[$propid]->getItemValue($curid);
                        if (!empty($curval) && is_array($curval) && !empty($curval['count'])) {
                            $newval = $curval['total'] / $curval['count'];
                            $properties[$propid]->setItemValue($curid,$newval);
                        }
                    }
                }
            }
            if (count($distinct) > 0) {
                foreach ($this->_itemids as $curid) {
                    foreach ($distinct as $propid) {
                        $curval = $properties[$propid]->getItemValue($curid);
                        if (!empty($curval) && is_array($curval)) {
                            $newval = count(array_keys($curval));
                            $properties[$propid]->setItemValue($curid,$newval);
                        }
                    }
                }
            }

        // here we grab everyting
        } else {
            $bindmarkers = '?' . str_repeat(',?',count($propids)-1);
            $query = "SELECT DISTINCT property_id,
                             item_id,
                             value
                        FROM $dynamicdata
                       WHERE property_id IN ($bindmarkers)";

            $stmt = $this->prepareStatement($query);
            $result = $stmt->executeQuery($propids);

            $itemidlist = array();
            while ($result->next()) {
                list($propid,$itemid,$value) = $result->getRow();
                $itemidlist[$itemid] = 1;
                if (isset($value)) {
                    // add the item to the value list for this property
                    $properties[$propid]->setItemValue($itemid,$value);
                }
            }
            // add the itemids to the list
            $this->_itemids = array_keys($itemidlist);
            $result->close();
        }
    }

    function countItems(array $args = array())
    {
        // Bail if no properties have yet been defined
        if(count($this->object->properties) == 0) return array();
        
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

        $dynamicdata = $this->getTable($this->table);

        $propids = array();
        foreach ($this->object->properties as $prop) $propids[] = $prop->id;

        // easy case where we already know the items we want
        if (count($itemids) > 0) {
            $bindmarkers = '?' . str_repeat(',?',count($propids)-1);
            if($this->getType() == 'sqlite') {
                $query = "SELECT COUNT(*)
                          FROM (SELECT DISTINCT item_id
                                WHERE property_id IN ($bindmarkers) "; // WATCH OUT, STILL UNBALANCED
            } else {
                $query = "SELECT COUNT(DISTINCT item_id)
                        FROM $dynamicdata
                       WHERE property_id IN ($bindmarkers) ";
            }
            $bindvars = $propids;

            if (count($itemids) > 1) {
                $bindmarkers = '?' . str_repeat(',?',count($itemids)-1);
                $query .= " AND item_id IN ($bindmarkers) ";
                foreach ($itemids as $itemid) {
                    $bindvars[] = (int) $itemid;
                }
            } else {
                $query .= " AND item_id = ? ";
                $bindvars[] = (int)$itemids[0];
            }

            // Balance parentheses.
            if($this->getType() == 'sqlite') $query .= ")";

            $stmt = $this->prepareStatement($query);
            $result = $stmt->executeQuery($bindvars);

            if ($result->first()) return;
            $this->object->numitems = $result->getInt(1);
            $result->close();

            return $this->object->numitems;

            // TODO: make sure this is portable !
        } elseif (count($this->where) > 0) {
            // more difficult case where we need to create a pivot table, basically
            // TODO: this only works for OR conditions !!!
            if($this->getType() == 'sqlite') {
                $query = "SELECT COUNT(*)
                          FROM ( SELECT DISTINCT item_id FROM $dynamicdata WHERE "; // WATCH OUT, STILL UNBALANCED
            } else {
                $query = "SELECT COUNT(DISTINCT item_id)
                        FROM $dynamicdata
                       WHERE ";
            }
            // only grab the fields we're interested in here...
            // TODO: support pre- and post-parts here too ? (cfr. bug 3090)
            foreach ($this->where as $whereitem) {
                $query .= $whereitem['join'] . ' (property_id = ' . $whereitem['field'] . ' AND value ' . $whereitem['clause'] . ') ';
            }

            // Balance parentheses.
            if($this->getType() == 'sqlite') $query .= ")";

            $stmt = $this->prepareStatement($query);
            $result = $stmt->executeQuery();
            if (!$result->first()) return;

            $this->object->numitems = $result->getInt(1);
            $result->close();

            return $this->object->numitems;

        // here we grab everyting
        } else {
            $bindmarkers = '?' . str_repeat(',?',count($propids)-1);
            if($this->getType() == 'sqlite' ) {
                $query = "SELECT COUNT(*)
                          FROM (SELECT DISTINCT item_id FROM $dynamicdata
                          WHERE property_id IN ($bindmarkers)) ";
            } else {
                $query = "SELECT COUNT(DISTINCT item_id)
                          FROM $dynamicdata
                          WHERE property_id IN ($bindmarkers) ";
            }

            $stmt = $this->prepareStatement($query);
            $result = $stmt->executeQuery($propids);
            if (!$result->first()) return;

            $this->object->numitems = $result->getInt(1);
            $result->close();

            return $this->object->numitems;
        }
    }

    /**
     * get next item id (for objects stored only in dynamic data table)
     *
     * @param $args['objectid'] dynamic object id for the item
     * @return integer value of the next id
     * @throws BadParameterException
     */
    function getNextId($objectid)
    {
        $invalid = '';
        if (isset($objectid) && !is_numeric($objectid)) $invalid = 'object id';

        if (!empty($invalid)) {
            $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
            throw new BadParameterException(array($invalid, 'DataVariableTable_DataStore', 'getNextId', 'DynamicData'),$msg);
        }

        $dynamicobjects = $this->getTable('dynamic_objects');

        // increase the max id for this object
        // TODO: figure out a way to do this more reliable.
        // - does a transaction help here? (esp. considering they are mostly emulated)
        $bindvars = array();
        $query = "UPDATE $dynamicobjects
                     SET maxid = maxid + 1 ";
            $query .= "WHERE id = ? ";
            $bindvars[] = (int)$objectid;
        $stmt = $this->prepareStatement($query);
        $stmt->executeUpdate($bindvars);
        // get it back (WARNING : this is *not* guaranteed to be unique on heavy-usage sites !)
        $bindvars = array();
        $query = "SELECT maxid
                    FROM $dynamicobjects ";
            $query .= "WHERE id = ? ";
            $bindvars[] = (int)$objectid;
        $stmt = $this->db->prepareStatement($query);
        $result= $stmt->executeQuery($bindvars);
        if (!$result->first()) return; // this should not happen

        $nextid = $result->getInt(1);
        $result->close();
        return $nextid;
    }
}
?>