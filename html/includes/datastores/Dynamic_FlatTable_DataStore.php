<?php
/**
 * Data Store is a flat SQL table (= typical module tables)
 *
 * @package dynamicdata
 * @subpackage datastores
 */

/**
 * include the base class
 *
 */
include_once "includes/datastores/Dynamic_SQL_DataStore.php";

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
    function getFieldName(&$property)
    {
        if (preg_match('/^(\w+)\.(\w+)$/', $property->source, $matches)) {
            $table = $matches[1];
            $field = $matches[2];
            return $field;
        }
    }

    function getItem($args)
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

        list($dbconn) = xarDBGetConn();

        $query = "SELECT $itemidfield, " . join(', ', $fieldlist) . "
                    FROM $table
                   WHERE $itemidfield = " . xarVarPrepForStore($itemid);

        $result =& $dbconn->Execute($query);

        if (!$result) return;

        if ($result->EOF) {
            return;
        }
        $values = $result->fields;
        $result->Close();

        $newitemid = array_shift($values);
        // oops, something went seriously wrong here...
        if (empty($itemid) || $newitemid != $itemid || count($values) != count($this->fields)) {
            return;
        }

        foreach ($fieldlist as $field) {
            // set the value for this property
            $this->fields[$field]->setValue(array_shift($values));
        }
    }

    function createItem($args)
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

        list($dbconn) = xarDBGetConn();

    // TODO: this won't work for objects with several static tables !
        if (empty($itemid)) {
            // get the next id (or dummy) from ADODB for this table
            $itemid = $dbconn->GenId($table);
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
        foreach ($fieldlist as $field) {
            // get the value from the corresponding property
            $value = $this->fields[$field]->getValue();
            // skip fields where values aren't set
            if (!isset($value)) {
                continue;
            }
            // TODO: improve this based on static table info
            if (is_numeric($value)) {
                $query .= $join . $value;
            } else {
                $query .= $join . "'" . xarVarPrepForStore($value) . "'";
            }
            $join = ', ';
        }
        $query .= " )";
        $result = & $dbconn->Execute($query);
        if (!$result) return;

        // get the real next id from ADODB for this table now
        $itemid = $dbconn->PO_Insert_ID($table, $itemidfield);

        if (empty($itemid)) {
            $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                         'item id from table '.$table, 'Dynamic_FlatTable_DataStore', 'createItem', 'DynamicData');
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                            new SystemException($msg));
            return;
        }
        $this->fields[$itemidfield]->setValue($itemid);
        return $itemid;
    }

    function updateItem($args)
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

        list($dbconn) = xarDBGetConn();

        $query = "UPDATE $table ";
        $join = 'SET ';
        foreach ($fieldlist as $field) {
            // get the value from the corresponding property
            $value = $this->fields[$field]->getValue();

            // skip fields where values aren't set, and don't update the item id either
            if (!isset($value) || $field == $itemidfield) {
                continue;
            }
            // TODO: improve this based on static table info
            if (is_numeric($value)) {
                $query .= $join . $field . ' = ' . $value;
            } else {
                $query .= $join . $field . ' = ' . "'" . xarVarPrepForStore($value) . "'";
            }
            $join = ', ';
        }
        $query .= " WHERE $itemidfield = " . xarVarPrepForStore($itemid);

        $result =& $dbconn->Execute($query);
        if (!$result) return;

        return $itemid;
    }

    function deleteItem($args)
    {
        $itemid = $args['itemid'];
        $table = $this->name;
        $itemidfield = $this->primary;

        // can't really do much without the item id field at the moment
        if (empty($itemidfield)) {
            return;
        }

        list($dbconn) = xarDBGetConn();

        $query = "DELETE FROM $table 
                   WHERE $itemidfield = " . xarVarPrepForStore($itemid);

        $result =& $dbconn->Execute($query);
        if (!$result) return;

        return $itemid;
    }

    function getItems($args = array())
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
        } elseif (isset($this->itemids)) {
            $itemids = $this->itemids;
        } else {
            $itemids = array();
        }

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

        list($dbconn) = xarDBGetConn();

        if ($isgrouped) {
            $query = "SELECT " . join(', ', $newfields) . "
                        FROM $table ";
        } else {
            $query = "SELECT $itemidfield, " . join(', ', $fieldlist) . "
                        FROM $table ";
        }

        // TODO: LEFT JOIN, ... ? -> cfr. relationships

        if (count($itemids) > 1) {
            $query .= " WHERE $itemidfield IN (" . join(', ',$itemids) . ") ";
        } elseif (count($itemids) == 1) {
            $query .= " WHERE $itemidfield = " . xarVarPrepForStore($itemids[0]) . " ";
        } elseif (count($this->where) > 0) {
            $query .= " WHERE ";
            foreach ($this->where as $whereitem) {
                $query .= $whereitem['join'] . ' ' . $whereitem['field'] . ' ' . $whereitem['clause'] . ' ';
            }
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
            $query .= " ORDER BY $itemidfield";
        }

        if ($numitems > 0) {
            $result =& $dbconn->SelectLimit($query, $numitems, $startnum-1);
        } else {
            $result =& $dbconn->Execute($query);
        }
        if (!$result) return;

        if (count($itemids) == 0 && !$isgrouped) {
            $saveids = 1;
        } else {
            $saveids = 0;
        }
        $itemid = 0;
        while (!$result->EOF) {
            $values = $result->fields;
            if ($isgrouped) {
                $itemid++;
            } else {
                $itemid = array_shift($values);
            }
            // oops, something went seriously wrong here...
            if (empty($itemid) || count($values) != count($this->fields)) {
                continue;
            }

            // add this itemid to the list
            if ($saveids) {
                $this->itemids[] = $itemid;
            }

            foreach ($fieldlist as $field) {
                // add the item to the value list for this property
                $this->fields[$field]->setItemValue($itemid,array_shift($values));
            }

            $result->MoveNext();
        }
        $result->Close();
    }

    function countItems($args = array())
    {
        if (!empty($args['itemids'])) {
            $itemids = $args['itemids'];
        } elseif (isset($this->itemids)) {
            $itemids = $this->itemids;
        } else {
            $itemids = array();
        }

        $table = $this->name;
        $itemidfield = $this->primary;

        // can't really do much without the item id field at the moment
        if (empty($itemidfield)) {
            return;
        }

        list($dbconn) = xarDBGetConn();

        $query = "SELECT COUNT(DISTINCT $itemidfield)
                    FROM $table ";

        if (count($itemids) > 1) {
            $query .= " WHERE $itemidfield IN (" . join(', ',$itemids) . ") ";
        } elseif (count($itemids) == 1) {
            $query .= " WHERE $itemidfield = " . xarVarPrepForStore($itemids[0]) . " ";
        } elseif (count($this->where) > 0) {
            $query .= " WHERE ";
            foreach ($this->where as $whereitem) {
                $query .= $whereitem['join'] . ' ' . $whereitem['field'] . ' ' . $whereitem['clause'] . ' ';
            }
        }

        // TODO: GROUP BY, LEFT JOIN, ... ? -> cfr. relationships

        $result =& $dbconn->Execute($query);
        if (!$result || $result->EOF) return;

        $numitems = $result->fields[0];

        $result->Close();

        return $numitems;
    }

    function getPrimary()
    {
        if (!empty($this->primary)) {
            return $this->primary;
        }

        // Try to get the primary field via the meta table

        $table = $this->name;

        list($dbconn) = xarDBGetConn();

        $systemPrefix = xarDBGetSystemTablePrefix();
        $metaTable = $systemPrefix . '_tables';

    // TODO: improve this once we can define better relationships
        $query = "SELECT xar_field, xar_type
                    FROM $metaTable
                   WHERE xar_primary_key = 1
                     AND xar_table='" . xarVarPrepForStore($table) . "'";

        $result =& $dbconn->Execute($query);

        if (!$result || $result->EOF) return;

        list($field, $type) = $result->fields;
        $result->Close();

        $this->primary = $field;
        return $field;
    }

    function getNext($args = array())
    {
        static $temp = array();

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
            } elseif (isset($this->itemids)) {
                $itemids = $this->itemids;
            } else {
                $itemids = array();
            }

            list($dbconn) = xarDBGetConn();

            $query = "SELECT $itemidfield, " . join(', ', $fieldlist) . "
                        FROM $table ";

            if (count($itemids) > 1) {
                $query .= " WHERE $itemidfield IN (" . join(', ',$itemids) . ") ";
            } elseif (count($itemids) == 1) {
                $query .= " WHERE $itemidfield = " . xarVarPrepForStore($itemids[0]) . " ";
            } elseif (count($this->where) > 0) {
                $query .= " WHERE ";
                foreach ($this->where as $whereitem) {
                    $query .= $whereitem['join'] . ' ' . $whereitem['field'] . ' ' . $whereitem['clause'] . ' ';
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

            if ($numitems > 0) {
                $result =& $dbconn->SelectLimit($query, $numitems, $startnum-1);
            } else {
                $result =& $dbconn->Execute($query);
            }
            if (!$result) return;
            $temp['result'] =& $result;
        }

        $result =& $temp['result'];

        if ($result->EOF) {
            $result->Close();

            $temp['result'] = null;
            return;
        }

        $values = $result->fields;
        $itemid = array_shift($values);
        // oops, something went seriously wrong here...
        if (empty($itemid) || count($values) != count($this->fields)) {
            $result->Close();

            $temp['result'] = null;
            return;
        }

        $this->fields[$itemidfield]->setValue($itemid);
        foreach ($fieldlist as $field) {
            // set the value for this property
            $this->fields[$field]->setValue(array_shift($values));
        }

        $result->MoveNext();
        return $itemid;
    }

}

?>
