<?php
/**
 * Data Store is a flat SQL table (= typical module tables)
 *
 * @package dynamicdata
 * @subpackage datastores
 */

/**
 * Parent class is SQL datastore
 *
 */
sys::import('datastores.sql');

/**
 * Class for flat table
 *
 * @package dynamicdata
 */
class Dynamic_FlatTable_DataStore extends Dynamic_SQL_DataStore
{
    /**
     * Get the field name used to identify this property (we use the name of the table field here)
     */
    function getFieldName(DataProperty &$property)
    {
        if (!is_object($property)) debug($property); // <-- this throws an exception
        // support [database.]table.field syntax
        if (preg_match('/^(.+)\.(\w+)$/', $property->source, $matches)) {
            $table = $matches[1];
            $field = $matches[2];
            return $field;
        }
    }

    function getItem(array $args = array())
    {
        $itemid = $args['itemid'];
        $table = $this->name;
        $itemidfield = $this->primary;

        // can't really do much without the item id field at the moment
        if (empty($itemidfield)) {
            return;
        }

        $tables = array($table);
        $more = '';

        // join with another table
        if (count($this->join) > 0) {
            $keys = array();
            $where = array();
            $andor = 'AND';
            foreach ($this->join as $info) {
                $tables[] = $info['table'];
                foreach ($info['fields'] as $field) {
                    $this->fields[$field] =& $this->extra[$field];
                }
                if (!empty($info['key'])) {
                    $keys[] = $info['key'] . ' = ' . $itemidfield;
                }
                if (!empty($info['where'])) {
                    $where[] = '(' . $info['where'] . ')';
                }
                // not relevant here
                //if (!empty($info['andor'])) {
                //    $andor = $info['andor'];
                //}
                if (!empty($info['more'])) {
                    $more .= ' ' . $info['more'];
                }
            }
        }

        $fieldlist = array_keys($this->fields);
        if (count($fieldlist) < 1) {
            return;
        }

        $query = "SELECT $itemidfield, " . join(', ', $fieldlist) . "
                    FROM " . join(', ', $tables) . $more . "
                   WHERE $itemidfield = ?";

        if (count($this->join) > 0) {
            if (count($keys) > 0) {
                $query .= " AND " . join(' AND ', $keys);
            }
            if (count($where) > 0) {
                $query .= " AND " . join(' AND ', $where);
            }
        }
        $stmt = $this->db->prepareStatement($query);
        $result = $stmt->executeQuery(array((int)$itemid),ResultSet::FETCHMODE_NUM);

        if (!$result->first()) {
            return;
        }
        $values = $result->getRow();
        $result->Close();

        $newitemid = array_shift($values);
        // oops, something went seriously wrong here...
        if (empty($itemid) || $newitemid != $itemid || count($values) != count($fieldlist)) {
            return;
        }

        foreach ($fieldlist as $field) {
            // set the value for this property
            $this->fields[$field]->setValue(array_shift($values));
        }
        return $itemid;
    }

    /**
     * Create an item in the flat table
     *
     * @return bool true on success, false on failure
     * @throws BadParameterException
     **/
    function createItem(array $args = array())
    {
        $itemid = $args['itemid'];
        $table = $this->name;
        $itemidfield = $this->primary;

        // can't really do much without the item id field at the moment
        if (empty($itemidfield)) {
            return;
        }

        $fieldlist = array_keys($this->fields);
        if (count($fieldlist) < 1) {
            return;
        }

        // TODO: this won't work for objects with several static tables !
        if (empty($itemid)) {
            // get the next id (or dummy)
            $itemid = null;
            $checkid = true;
        } else {
            $checkid = false;
        }
        $this->fields[$itemidfield]->setValue($itemid);

        $query = "INSERT INTO $table ( ";
        $join = '';
        foreach ($fieldlist as $field) {
            // get the value from the corresponding property
            $value = $this->fields[$field]->getValue();
            // skip fields where values aren't set
            if (!isset($value)) {
                continue;
            }
            $query .= $join . $field;
            $join = ', ';
        }
        $query .= " ) VALUES ( ";
        $join = '';
        $bindvars = array();
        foreach ($fieldlist as $field) {
            // get the value from the corresponding property
            $value = $this->fields[$field]->getValue();
            // skip fields where values aren't set
            if (!isset($value)) {
                continue;
            }
            // TODO: improve this based on static table info
            $query .= $join . " ? ";
            $bindvars[] = $value;
            $join = ', ';
        }
        $query .= " )";
        $stmt = $this->db->prepareStatement($query);
        $result = $stmt->executeUpdate($bindvars);

        // get the last inserted id
        if ($checkid) {
            $itemid = $this->db->getLastId($table);
        }

        if (empty($itemid)) {
            $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
            throw new BadParameterException(array('item id from table '.$table, 'DataFlatTable_DataStore', 'createItem', 'DynamicData'),$msg);
        }
        $this->fields[$itemidfield]->setValue($itemid);
        return $itemid;
    }

    function updateItem(array $args = array())
    {
        $itemid = $args['itemid'];
        $table = $this->name;
        $itemidfield = $this->primary;

        // can't really do much without the item id field at the moment
        if (empty($itemidfield)) {
            return;
        }

        $fieldlist = array_keys($this->fields);
        if (count($fieldlist) < 1) {
            return;
        }

        $query = "UPDATE $table ";
        $join = 'SET ';
        $bindvars = array();
        foreach ($fieldlist as $field) {
            // get the value from the corresponding property
            $value = $this->fields[$field]->getValue();

            // skip fields where values aren't set, and don't update the item id either
            if (!isset($value) || $field == $itemidfield) {
                continue;
            }
            // TODO: improve this based on static table info
            $query .= $join . $field . '=?';
            $bindvars[] = $value;
            $join = ', ';
        }
        $query .= " WHERE $itemidfield=?";
        $bindvars[] = (int)$itemid;
        $stmt = $this->db->prepareStatement($query);
        $stmt->executeUpdate($bindvars);

        return $itemid;
    }

    function deleteItem(array $args = array())
    {
        $itemid = $args['itemid'];
        $table = $this->name;
        $itemidfield = $this->primary;

        // can't really do much without the item id field at the moment
        if (empty($itemidfield)) {
            return;
        }

        $query = "DELETE FROM $table WHERE $itemidfield = ?";
        $stmt = $this->db->prepareStatement($query);
        $stmt->executeUpdate(array((int)$itemid));
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
            return true;
        }
        // check if it's set here - could be 0 (= empty) too
        if (isset($args['cache'])) {
            $this->cache = $args['cache'];
        }

        $table = $this->name;
        $itemidfield = $this->primary;

        // can't really do much without the item id field at the moment
        if (empty($itemidfield)) {
            // CHECKME: test working without the item id field
            return;
        }

        $tables = array($table);
        $more = '';

        // join with another table
        if (count($this->join) > 0) {
            $keys = array();
            $where = array();
            $andor = 'AND';
            foreach ($this->join as $info) {
                $tables[] = $info['table'];
                foreach ($info['fields'] as $field) {
                    $this->fields[$field] =& $this->extra[$field];
                }
                if (!empty($info['key'])) {
                    $keys[] = $info['key'] . ' = ' . $itemidfield;
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
        }

        $fieldlist = array_keys($this->fields);
        if (count($fieldlist) < 1) {
            return;
        }

        // check if we're dealing with GROUP BY fields and/or COUNT, SUM etc. operations
        $isgrouped = 0;
        if (count($this->groupby) > 0) {
            $isgrouped = 1;
        }
        $newfields = array();
        foreach ($fieldlist as $field) {
            if (!empty($this->fields[$field]->operation)) {
                $newfields[] = $this->fields[$field]->operation . '(' . $field . ') AS ' . $this->fields[$field]->operation . '_' . $this->fields[$field]->name;
                $isgrouped = 1;
            } else {
                $newfields[] = $field;
            }
        }

        /*
        // CHECKME: test working without the item id field
        if (empty($itemidfield)) {
            $isgrouped = 1;
        }
        */
        if ($isgrouped) {
            $query = "SELECT " . join(', ', $newfields) . "
                        FROM " . join(', ', $tables) . $more . " ";
        } else {
            // Note: Oracle doesn't like having the same field in a sub-query twice,
            //       so we use an alias for the primary field here
            $query = "SELECT DISTINCT $itemidfield AS ddprimaryid, " . join(', ', $fieldlist) .
                        " FROM " . join(', ', $tables) . $more . " ";
        }

        $next = 'WHERE';
        if (count($this->join) > 0) {
            if (count($keys) > 0) {
                $query .= " $next " . join(' AND ', $keys);
                $next = 'AND';
            }
            if (count($where) > 0) {
                $query .= " $next ( " . join(' AND ', $where);
                $next = $andor;
            }
        }

        $bindvars = array();
        if (count($itemids) > 1) {
            $bindmarkers = '?' . str_repeat(',?',count($itemids)-1);
            $query .= " $next $itemidfield IN ($bindmarkers) ";
            foreach ($itemids as $itemid) {
                $bindvars[] = (int) $itemid;
            }
        } elseif (count($itemids) == 1) {
            $query .= " $next $itemidfield = ? ";
            $bindvars[] = (int)$itemids[0];
        } elseif (count($this->where) > 0) {
            $query .= " $next ";
            foreach ($this->where as $whereitem) {
                $query .= $whereitem['join'] . ' ' . $whereitem['pre'] . $whereitem['field'] . ' ' . $whereitem['clause'] . $whereitem['post'] . ' ';
            }
        }
        if (count($this->join) > 0 && count($where) > 0) {
            $query .= " ) ";
        }

        if (count($this->groupby) > 0) {
            $query .= " GROUP BY " . join(', ', $this->groupby);
        }

        if (count($this->sort) > 0) {
            $query .= " ORDER BY ";
            $join = '';
            foreach ($this->sort as $sortitem) {
                if (empty($this->fields[$sortitem['field']]->operation)) {
                    $query .= $join . $sortitem['field'] . ' ' . $sortitem['sortorder'];
                } else {
                    $query .= $join . $this->fields[$sortitem['field']]->operation . '_' . $this->fields[$sortitem['field']]->name . ' ' . $sortitem['sortorder'];
                }
                $join = ', ';
            }
        } elseif (!$isgrouped) {
            $query .= " ORDER BY ddprimaryid";
        }

        // We got the query, prepare it
        $stmt = $this->db->prepareStatement($query);
        
        if ($numitems > 0) {
            $stmt->setLimit($numitems);
            $stmt->setOffset($startnum - 1);
        }
        $result = $stmt->executeQuery($bindvars);

        if (count($itemids) == 0 && !$isgrouped) {
            $saveids = 1;
        } else {
            $saveids = 0;
        }
        $itemid = 0;
        while ($result->next()) {
            $values = $result->getRow();
            if ($isgrouped) {
                $itemid++;
            } else {
                $itemid = array_shift($values);
            }
            // oops, something went seriously wrong here...
            if (empty($itemid) || count($values) != count($fieldlist)) {
                continue;
            }

            // add this itemid to the list
            if ($saveids) {
                $this->_itemids[] = $itemid;
            }

            foreach ($fieldlist as $field) {
                // add the item to the value list for this property
                $this->fields[$field]->setItemValue($itemid,array_shift($values));
            }
        }
        $result->close();
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

        $table = $this->name;
        $itemidfield = $this->primary;

        // can't really do much without the item id field at the moment
        if (empty($itemidfield)) {
            return;
        }

        if($this->db->databaseType == 'sqlite') {
            $query = "SELECT COUNT(*)
                      FROM (SELECT DISTINCT $itemidfield FROM $table "; // WATCH OUT, STILL UNBALANCED
        } else {
            $query = "SELECT COUNT(DISTINCT $itemidfield)
                    FROM $table ";
        }

        $bindvars = array();
        if (count($itemids) > 1) {
            $bindmarkers = '?' . str_repeat(',?',count($itemids)-1);
            $query .= " WHERE $itemidfield IN ($bindmarkers) ";
            foreach ($itemids as $itemid) {
                $bindvars[] = (int) $itemid;
            }
        } elseif (count($itemids) == 1) {
            $query .= " WHERE $itemidfield = ? ";
            $bindvars[] = (int)$itemids[0];
        } elseif (count($this->where) > 0) {
            $query .= " WHERE ";
            foreach ($this->where as $whereitem) {
                $query .= $whereitem['join'] . ' ' . $whereitem['pre'] . $whereitem['field'] . ' ' . $whereitem['clause'] . $whereitem['post'] . ' ';
            }
        }

        // TODO: GROUP BY, LEFT JOIN, ... ? -> cfr. relationships
        if($this->db->databaseType == 'sqlite') $query.=")";
        
        $stmt = $this->db->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars);
        if (!$result->first()) return;

        $numitems = $result->getInt(1);
        $result->close();

        return $numitems;
    }

    /**
     * Get the primary key of this item
     *
     * @return string name of the column which is the primary key
     * @todo ill defined, primary key can have combined columns, should perhaps return object(set) reference
     **/
    function getPrimary()
    {
        if (!empty($this->primary)) {
            return $this->primary;
        }

        $dbInfo =& $this->db->getDatabaseInfo();
        $tblInfo=& $dbInfo->getTable($this->name);
        $keyInfo=& $tblInfo->getPrimaryKey();

        $columns = $keyInfo->getColumns();
        if(count($columns) > 1) {
            // TODO: support composite keys
            throw new BadParameterException($this->name,'The table "#(1)" has more than one column in its primary key. We only support single column keys at this moment');
        }
        $this->primary = $columns[0]->getName();
        return $this->primary;
    }

    function getNext(Array $args = array())
    {
        static $temp = array();

        $table = $this->name;
        $itemidfield = $this->primary;

        // can't really do much without the item id field at the moment
        if (empty($itemidfield)) return;

        $fieldlist = array_keys($this->fields);
        // Something to do for us?
        if (count($fieldlist) < 1) return;

        if (!isset($temp['result'])) {
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

            $query = "SELECT $itemidfield, " . join(', ', $fieldlist) . "
                      FROM $table ";

            $bindvars = array();
            if (count($itemids) > 1) {
                $bindmarkers = '?' . str_repeat(',?',count($itemids)-1);
                $query .= " WHERE $itemidfield IN ($bindmarkers) ";
                foreach ($itemids as $itemid) {
                    $bindvars[] = (int) $itemid;
                }
            } elseif (count($itemids) == 1) {
                $query .= " WHERE $itemidfield = ? ";
                $bindvars[] = (int)$itemids[0];
            } elseif (count($this->where) > 0) {
                $query .= " WHERE ";
                foreach ($this->where as $whereitem) {
                    $query .= $whereitem['join'] . ' ' . $whereitem['pre'] . $whereitem['field'] . ' ' . $whereitem['clause'] . $whereitem['post'] . ' ';
                }
            }

            // TODO: GROUP BY, LEFT JOIN, ... ? -> cfr. relationships

            if (count($this->sort) > 0) {
                $query .= " ORDER BY ";
                $join = '';
                foreach ($this->sort as $sortitem) {
                    $query .= $join . $sortitem['field'] . ' ' . $sortitem['sortorder'];
                    $join = ', ';
                }
            } else {
                $query .= " ORDER BY $itemidfield";
            }
            // We got the query, prepare it
            $stmt = $this->db->prepareStatement($query);
            
            // Now set additional parameters if we need to
            if ($numitems > 0) {
                $stmt->setLimit($numitems);
                $stmt->setOffset($startnum-1);
            }
            // Execute it
            $result = $stmt->executeQuery($bindvars);
            $temp['result'] =& $result;
        }

        $result =& $temp['result'];

        // Try to fetch the next row
        if (!$result->next()) {
            $result->close();

            $temp['result'] = null;
            return;
        }

        $values = $result->getRow();
        $itemid = array_shift($values);
        // oops, something went seriously wrong here...
        if (empty($itemid) || count($values) != count($this->fields)) {
            $result->close();

            $temp['result'] = null;
            return;
        }

        $this->fields[$itemidfield]->setValue($itemid);
        foreach ($fieldlist as $field) {
            // set the value for this property
            $this->fields[$field]->setValue(array_shift($values));
        }
        return $itemid;
    }

}

?>
