<?php
/**
 * Data Store is a variable SQL table (= only xar_dynamic_data for now)
 *
 * @package dynamicdata
 * @subpackage datastores
**/

/*
 * Parent class is SQL datastore
 *
 */
sys::import('datastores.sql');

/**
 * Data store is a variable SQL table
 *
 * @package dynamicdata
**/
class DataVariableTable_DataStore extends DataSQL_DataStore
{
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
        $itemid = $args['itemid'];

        $propids = array_keys($this->fields);
        if (count($propids) < 1) {
            return;
        }

        $dynamicdata = $this->tables['dynamic_data'];

        $bindmarkers = '?' . str_repeat(',?',count($propids)-1);
        $query = "SELECT xar_dd_propid, xar_dd_value
                    FROM $dynamicdata
                   WHERE xar_dd_propid IN ($bindmarkers)
                     AND xar_dd_itemid = ?";
        $bindvars = $propids;
        $bindvars[] = (int)$itemid;

        $result =& $this->db->Execute($query,$bindvars,ResultSet::FETCHMODE_NUM);

        if ($result->EOF) {
            return;
        }
        while (!$result->EOF) {
            list($propid, $value) = $result->getRow();
            if (isset($value)) {
                // set the value for this property
                $this->fields[$propid]->setValue($value);
            }
            $result->next();
        }

        $result->Close();
        return $itemid;
    }
    /**
     * Create an item
     * @param array $args with $itemid,
     */
    function createItem(array $args = array())
    {
        extract($args);

        // we need to manage our own item ids here, and we can't use some sequential field
        if (empty($itemid)) {
            $itemid = $this->getNextId($args);
            if (empty($itemid)) return;
            if (isset($this->primary)) {
                $this->fields[$this->primary]->setValue($itemid);
            }
        }

        $propids = array_keys($this->fields);
        if (count($propids) < 1) {
            return $itemid;
        }

        $dynamicdata = $this->tables['dynamic_data'];

        foreach ($propids as $propid) {
            // get the value from the corresponding property
            $value = $this->fields[$propid]->getValue();

            // invalid prop_id or undefined value (empty is OK, though !)
            if (empty($propid) || !is_numeric($propid) || !isset($value)) {
                continue;
            }

            $nextId = $this->db->GenId($dynamicdata);

            $query = "INSERT INTO $dynamicdata (xar_dd_id,xar_dd_propid,xar_dd_itemid,xar_dd_value)
                      VALUES (?,?,?,?)";
            $bindvars = array($nextId,$propid,$itemid, (string) $value);
            $this->db->Execute($query,$bindvars);

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
        $itemid = $args['itemid'];

        $propids = array_keys($this->fields);
        if (count($propids) < 1) {
            return $itemid;
        }

        $dynamicdata = $this->tables['dynamic_data'];

        // get the current dynamic data fields for all properties of this item
        $bindmarkers = '?' . str_repeat(',?',count($propids)-1);
        $query = "SELECT xar_dd_id, xar_dd_propid
                    FROM $dynamicdata
                   WHERE xar_dd_propid IN ($bindmarkers)
                     AND xar_dd_itemid = ?";
        $bindvars = $propids;
        $bindvars[] = (int)$itemid;

        $result =& $this->db->Execute($query,$bindvars,ResultSet::FETCHMODE_NUM);

        $datafields = array();
        while (!$result->EOF) {
            list($dd_id,$propid) = $result->getRow();
            $datafields[$propid] = $dd_id;
            $result->next();
        }
        $result->Close();

        foreach ($propids as $propid) {
            // get the value from the corresponding property
            $value = $this->fields[$propid]->getValue();

            // invalid prop_id or undefined value (empty is OK, though !)
            if (empty($propid) || !is_numeric($propid) || !isset($value)) {
                continue;
            }

            // update the dynamic data field if it exists
            if (!empty($datafields[$propid])) {
                $query = "UPDATE $dynamicdata SET xar_dd_value = ? WHERE xar_dd_id = ?";
                $bindvars = array((string) $value, $datafields[$propid]);
            // or create it if necessary (e.g. when you add properties afterwards etc.)
            } else {
                $nextId = $this->db->GenId($dynamicdata);

                $query = "INSERT INTO $dynamicdata
                            (xar_dd_id, xar_dd_propid, xar_dd_itemid, xar_dd_value)
                          VALUES (?,?,?,?)";
                $bindvars = array($nextId,$propid,$itemid, (string) $value);
            }
            $this->db->Execute($query,$bindvars);
        }
        return $itemid;
    }

    function deleteItem(array $args = array())
    {
        $itemid = $args['itemid'];

        $propids = array_keys($this->fields);
        if (count($propids) < 1) {
            return $itemid;
        }

        $dynamicdata = $this->tables['dynamic_data'];

        // get the current dynamic data fields for all properties of this item
        $bindmarkers = '?' . str_repeat(',?', count($propids) -1);
        $query = "DELETE FROM $dynamicdata
                   WHERE xar_dd_propid IN ($bindmarkers)
                     AND xar_dd_itemid = ?";
        $bindvars = $propids;
        $bindvars[] = (int)$itemid;
        $this->db->Execute($query,$bindvars);

        return $itemid;
    }

    function getItems(array $args = array())
    {
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

        $propids = array_keys($this->fields);
        if (count($propids) < 1) {
            return;
        }

        $dynamicdata = $this->tables['dynamic_data'];

        // easy case where we already know the items we want
        if (count($itemids) > 0) {
            $bindmarkers = '?' . str_repeat(',?',count($propids)-1);
            $query = "SELECT xar_dd_itemid, xar_dd_propid, xar_dd_value
                        FROM $dynamicdata
                       WHERE xar_dd_propid IN ($bindmarkers) ";
            $bindvars = $propids;

            if (count($itemids) > 1) {
                $bindmarkers = '?' . str_repeat(',?',count($itemids)-1);
                $query .= " AND xar_dd_itemid IN ($bindmarkers) ";
                foreach ($itemids as $itemid) {
                    $bindvars[] = (int) $itemid;
                }
            } else {
                $query .= " AND xar_dd_itemid = ?";
                $bindvars[] = (int)$itemids[0];
            }
            if (!empty($this->cache)) {
                $result =& $this->db->CacheExecute($this->cache,$query,$bindvars);
            } else {
                $result =& $this->db->Execute($query,$bindvars);
            }


            if (count($this->sort) > 0) {
                $items = array();
                $dosort = 1;
            } else {
                $dosort = 0;
            }

            while (!$result->EOF) {
                list($itemid,$propid, $value) = $result->getRow();
                if (isset($value)) {
                    if ($dosort) {
                        $items[$itemid][$propid] = $value;
                    } else {
                        // add the item to the value list for this property
                        $this->fields[$propid]->setItemValue($itemid,$value);
                    }
                }
                $result->next();
            }

            $result->Close();

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
                    $keys[] = $info['key'] . ' = xar_dd_itemid';
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
            $query = "SELECT DISTINCT xar_dd_itemid, xar_dd_propid, xar_dd_value";
            if (count($fields) > 0) {
                $query .= ", " . join(', ',$fields);
            }
            $query .= " FROM $dynamicdata, " . join(', ',$tables) . $more . "
                       WHERE xar_dd_propid IN (" . join(', ',$propids) . ") ";
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
                    $query .= $whereitem['join'] . " (xar_dd_propid = " . $whereitem['field'] . ' AND xar_dd_value ' . $whereitem['clause'] . ') ';
                }
                $query .= " )";
            }
            if (count($where) > 0) {
                $query .= " )";
            }

            // TODO: combine with sort someday ? Not sure if that's possible in this way...
            if ($numitems > 0) {
                $query .= ' ORDER BY xar_dd_itemid, xar_dd_propid';
                // Note : this assumes that every property of the items is stored in the table
                $numrows = $numitems * count($propids);
                if ($startnum > 1) {
                    $startrow = ($startnum - 1) * count($propids) + 1;
                } else {
                    $startrow = 1;
                }
                if (!empty($this->cache)) {
                    $result =& $this->db->CacheSelectLimit($this->cache, $query, $numrows, $startrow-1);
                } else {
                    $result =& $this->db->SelectLimit($query, $numrows, $startrow-1);
                }
            } else {
                if (!empty($this->cache)) {
                    $result =& $this->db->CacheExecute($this->cache, $query);
                } else {
                    $result =& $this->db->Execute($query);
                }
            }


            $itemidlist = array();
            while (!$result->EOF) {
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
                $result->next();
            }
            // add the itemids to the list
            $this->_itemids = array_keys($itemidlist);

            $result->Close();

        // TODO: make sure this is portable !
        // more difficult case where we need to create a pivot table, basically
        } elseif ($numitems > 0 || count($this->sort) > 0 || count($this->where) > 0 || count($this->groupby) > 0) {

            $dbtype = xarDBGetType();
            if (substr($dbtype,0,4) == 'oci8') {
                $propval = 'TO_CHAR(xar_dd_value)';
            } elseif (substr($dbtype,0,5) == 'mssql') {
            // CHECKME: limited to 8000 characters ?
                $propval = 'CAST(xar_dd_value AS VARCHAR(8000))';
            } else {
                $propval = 'xar_dd_value';
            }

        /*
            Note : Alternate syntax for Postgres if contrib/tablefunc.sql is installed

            $query = "SELECT * FROM crosstab(
                'SELECT xar_dd_itemid, xar_dd_propid, xar_dd_value
                 FROM $dynamicdata
                 WHERE xar_dd_propid IN (" . join(', ',$propids) . ")
                 ORDER BY xar_dd_itemid, xar_dd_propid;', " . count($propids) . ")
            AS dd(itemid int, dd_" . join(' text, dd_',$propids) . " text)";

            if (count($this->where) > 0) {
                $query .= " WHERE ";
                foreach ($this->where as $whereitem) {
                    $query .= $whereitem['join'] . ' ' . $whereitem['pre'] . 'dd_' . $whereitem['field'] . ' ' . $whereitem['clause'] . $whereitem['post'] . ' ';
                }
            }
        */

            $query = "SELECT xar_dd_itemid ";
            foreach ($propids as $propid) {
                $query .= ", MAX(CASE WHEN xar_dd_propid = $propid THEN $propval ELSE '' END) AS dd_$propid \n";
            }
            $query .= " FROM $dynamicdata
                       WHERE xar_dd_propid IN (" . join(', ',$propids) . ")
                    GROUP BY xar_dd_itemid ";

            if (count($this->where) > 0) {
                $query .= " HAVING ";
                foreach ($this->where as $whereitem) {
                    // Postgres does not support column aliases in HAVING clauses, but you can use the same aggregate function
                    if (substr($dbtype,0,8) == 'postgres') {
                        $query .= $whereitem['join'] . ' ' . $whereitem['pre'] . 'MAX(CASE WHEN xar_dd_propid = ' . $whereitem['field'] . " THEN $propval ELSE '' END) " . $whereitem['clause'] . $whereitem['post'] . ' ';
                    } else {
                        $query .= $whereitem['join'] . ' ' . $whereitem['pre'] . 'dd_' . $whereitem['field'] . ' ' . $whereitem['clause'] . $whereitem['post'] . ' ';
                    }
                }
            }

            if (count($this->sort) > 0) {
                $query .= " ORDER BY ";
                $join = '';
                foreach ($this->sort as $sortitem) {
                    $query .= $join . 'dd_' . $sortitem['field'] . ' ' . $sortitem['sortorder'];
                    $join = ', ';
                }
            }

            if ($numitems > 0) {
                if (!empty($this->cache)) {
                    $result =& $this->db->CacheSelectLimit($this->cache, $query, $numitems, $startnum-1);
                } else {
                    $result = $this->db->SelectLimit($query, $numitems, $startnum-1);
                }
            } else {
                if (!empty($this->cache)) {
                    $result =& $this->db->CacheExecute($this->cache, $query);
                } else {
                    $result =& $this->db->Execute($query);
                }
            }


            $isgrouped = 0;
            if (count($this->groupby) > 0) {
                $isgrouped = 1;
                $items = array();
                $combo = array();
                $id = 0;
                $process = array();
                foreach ($propids as $propid) {
                    if (in_array($propid,$this->groupby)) {
                        continue;
                    } elseif (empty($this->fields[$propid]->operation)) {
                        continue; // all fields should be either GROUP BY or have some operation
                    }
                    array_push($process, $propid);
                }
            }
            while (!$result->EOF) {
                $values = $result->getRow();
                $itemid = array_shift($values);
                // oops, something went seriously wrong here...
                if (empty($itemid) || count($values) != count($propids)) {
                    $result->MoveNext();
                    continue;
                }
                if (!$isgrouped) {
                    // add this itemid to the list
                    $this->_itemids[] = $itemid;

                    foreach ($propids as $propid) {
                        // add the item to the value list for this property
                        $this->fields[$propid]->setItemValue($itemid,array_shift($values));
                    }

                } else {
                    // TODO: use sub-query to do this in the database for MySQL 4.1+ and others ?
                    $propval = array();
                    foreach ($propids as $propid) {
                        $propval[$propid] = array_shift($values);
                    }
                    $groupid = '';
                    foreach ($this->groupby as $propid) {
                        $groupid .= $propval[$propid] . '~';
                    }
                    if (!isset($combo[$groupid])) {
                        $id++;
                        $combo[$groupid] = $id;
                        // add this "itemid" to the list
                        $this->_itemids[] = $id;
                        foreach ($this->groupby as $propid) {
                            // add the item to the value list for this property
                            $this->fields[$propid]->setItemValue($id,$propval[$propid]);
                        }
                        foreach ($process as $propid) {
                            // add the item to the value list for this property
                            $this->fields[$propid]->setItemValue($id,null);
                        }
                    }
                    $curid = $combo[$groupid];
                    foreach ($process as $propid) {
                        $curval = $this->fields[$propid]->getItemValue($curid);
                        switch ($this->fields[$propid]->operation) {
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
                                // TODO: divide total by count afterwards
                                break;
                            default:
                                break;
                        }
                        $this->fields[$propid]->setItemValue($curid,$curval);
                    }
                }
                $result->next();
            }
            $result->Close();

            // divide total by count afterwards
            if ($isgrouped) {
                $divide = array();
                foreach ($process as $propid) {
                    if ($this->fields[$propid]->operation == 'AVG') {
                        $divide[] = $propid;
                    }
                }
                if (count($divide) > 0) {
                    foreach ($this->_itemids as $curid) {
                        foreach ($divide as $propid) {
                            $curval = $this->fields[$propid]->getItemValue($curid);
                            if (!empty($curval) && is_array($curval) && !empty($curval['count'])) {
                                $newval = $curval['total'] / $curval['count'];
                                $this->fields[$propid]->setItemValue($curid,$newval);
                            }
                        }
                    }
                }
            }

        // here we grab everyting
        } else {
            $bindmarkers = '?' . str_repeat(',?',count($propids)-1);
            $query = "SELECT DISTINCT xar_dd_propid,
                             xar_dd_itemid,
                             xar_dd_value
                        FROM $dynamicdata
                       WHERE xar_dd_propid IN ($bindmarkers)";

            if (!empty($this->cache)) {
                $result =& $this->db->CacheExecute($this->cache,$query,$propids);
            } else {
                $result =& $this->db->Execute($query,$propids);
            }

            $itemidlist = array();
            while (!$result->EOF) {
                list($propid,$itemid,$value) = $result->getRow();
                $itemidlist[$itemid] = 1;
                if (isset($value)) {
                    // add the item to the value list for this property
                    $this->fields[$propid]->setItemValue($itemid,$value);
                }
                $result->next();
            }
            // add the itemids to the list
            $this->_itemids = array_keys($itemidlist);

            $result->Close();
        }
    }

    function countItems(array $args = array())
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

        $dynamicdata = $this->tables['dynamic_data'];

        $propids = array_keys($this->fields);

        // easy case where we already know the items we want
        if (count($itemids) > 0) {
            $bindmarkers = '?' . str_repeat(',?',count($propids)-1);
            if($this->db->databaseType == 'sqlite') {
                $query = "SELECT COUNT(*)
                          FROM (SELECT DISTINCT xar_dd_itemid
                                WHERE xar_dd_propid IN ($bindmarkers) "; // WATCH OUT, STILL UNBALANCED
            } else {
                $query = "SELECT COUNT(DISTINCT xar_dd_itemid)
                        FROM $dynamicdata
                       WHERE xar_dd_propid IN ($bindmarkers) ";
            }
            $bindvars = $propids;

            if (count($itemids) > 1) {
                $bindmarkers = '?' . str_repeat(',?',count($itemids)-1);
                $query .= " AND xar_dd_itemid IN ($bindmarkers) ";
                foreach ($itemids as $itemid) {
                    $bindvars[] = (int) $itemid;
                }
            } else {
                $query .= " AND xar_dd_itemid = ? ";
                $bindvars[] = (int)$itemids[0];
            }

            // Balance parentheses.
            if($this->db->databaseType == 'sqlite') $query .= ")";
            if (!empty($this->cache)) {
                $result =& $this->db->CacheExecute($this->cache,$query,$bindvars);
            } else {
                $result =& $this->db->Execute($query,$bindvars);
            }

            if ($result->EOF) return;

            $numitems = $result->getInt(1);

            $result->Close();

            return $numitems;

            // TODO: make sure this is portable !
        // more difficult case where we need to create a pivot table, basically
        } elseif (count($this->where) > 0) {

        // TODO: this only works for OR conditions !!!
            if($this->db->databaseType == 'sqlite') {
                $query = "SELECT COUNT(*)
                          FROM ( SELECT DISTINCT xar_dd_itemid FROM $dynamicdata WHERE "; // WATCH OUT, STILL UNBALANCED
            } else {
                $query = "SELECT COUNT(DISTINCT xar_dd_itemid)
                        FROM $dynamicdata
                       WHERE ";
            }
            // only grab the fields we're interested in here...
// TODO: support pre- and post-parts here too ? (cfr. bug 3090)
            foreach ($this->where as $whereitem) {
                $query .= $whereitem['join'] . ' (xar_dd_propid = ' . $whereitem['field'] . ' AND xar_dd_value ' . $whereitem['clause'] . ') ';
            }

            // Balance parentheses.
            if($this->db->databaseType == 'sqlite') $query .= ")";
            if (!empty($this->cache)) {
                $result =& $this->db->CacheExecute($this->cache, $query);
            } else {
                $result =& $this->db->Execute($query);
            }

            if ($result->EOF) return;

            $numitems = $result->getInt(1);

            $result->Close();

            return $numitems;

        // here we grab everyting
        } else {
            $bindmarkers = '?' . str_repeat(',?',count($propids)-1);
            if($this->db->databaseType == 'sqlite' ) {
                $query = "SELECT COUNT(*)
                          FROM (SELECT DISTINCT xar_dd_itemid FROM $dynamicdata
                          WHERE xar_dd_propid IN ($bindmarkers)) ";
            } else {
                $query = "SELECT COUNT(DISTINCT xar_dd_itemid)
                        FROM $dynamicdata
                       WHERE xar_dd_propid IN ($bindmarkers) ";
            }

            if (!empty($this->cache)) {
                $result =& $this->db->CacheExecute($this->cache,$query,$propids);
            } else {
                $result =& $this->db->Execute($query,$propids);
            }

            if ($result->EOF) return;

            $numitems = $result->getInt(1);

            $result->Close();

            return $numitems;
        }
    }

    /**
     * get next item id (for objects stored only in dynamic data table)
     *
     * @param $args['objectid'] dynamic object id for the item, or
     * @param $args['modid'] module id for the item +
     * @param $args['itemtype'] item type of the item
     * @return integer value of the next id
     * @throws BadParameterException
     */
    function getNextId(array $args)
    {
        extract($args);

        $invalid = '';
        if (isset($objectid) && !is_numeric($objectid)) {
            $invalid = 'object id';
        } elseif (!isset($modid) || !is_numeric($modid)) {
            $invalid = 'module id';
        } elseif (!isset($itemtype) || !is_numeric($itemtype)) {
            $invalid = 'item type';
        }

        if (!empty($invalid)) {
            $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
            throw new BadParameterException(array($invalid, 'DataVariableTable_DataStore', 'getNextId', 'DynamicData'),$msg);
        }

        $dynamicobjects = $this->tables['dynamic_objects'];

        // increase the max id for this object
        $bindvars = array();
        $query = "UPDATE $dynamicobjects
                     SET xar_object_maxid = xar_object_maxid + 1 ";
        if (!empty($objectid)) {
            $query .= "WHERE xar_object_id = ? ";
            $bindvars[] = (int)$objectid;
        } else {
            $query .= "WHERE xar_object_moduleid = ?
                         AND xar_object_itemtype = ?";
            $bindvars[] = (int)$modid;
            $bindvars[] = (int)$itemtype;
        }
        $this->db->Execute($query,$bindvars);

        // get it back (WARNING : this is *not* guaranteed to be unique on heavy-usage sites !)
        $bindvars = array();
        $query = "SELECT xar_object_maxid
                    FROM $dynamicobjects ";
        if (!empty($objectid)) {
            $query .= "WHERE xar_object_id = ? ";
            $bindvars[] = (int)$objectid;
        } else {
            $query .= "WHERE xar_object_moduleid = ?
                         AND xar_object_itemtype = ? ";
            $bindvars[] = (int)$modid;
            $bindvars[] = (int)$itemtype;
        }

        $result = $this->db->Execute($query,$bindvars,ResultSet::FETCHMODE_NUM);
        if ($result->EOF) return;

        $nextid = $result->getInt(1);

        $result->Close();

        return $nextid;
    }

}

?>
