<?php
/**
 * Data Store is a variable SQL table (= only xar_dynamic_data for now)
 *
 * @package dynamicdata
 * @subpackage datastores
 */


/**
 * Include the base class
 *
 */
include_once "includes/datastores/Dynamic_SQL_DataStore.php";

/**
 * Data store is a variable SQL table
 *
 * @package dynamicdata
 */
class Dynamic_VariableTable_DataStore extends Dynamic_SQL_DataStore
{

    /**
     * Get the field name used to identify this property (we use the property id here)
     */
    function getFieldName(&$property)
    {
        return $property->id;
    }

    function getItem($args)
    {
        $itemid = $args['itemid'];

        $propids = array_keys($this->fields);
        if (count($propids) < 1) {
            return;
        }

        list($dbconn) = xarDBGetConn();
        $xartable = xarDBGetTables();

        $dynamicdata = $xartable['dynamic_data'];

        $query = "SELECT xar_dd_propid,
                         xar_dd_value
                    FROM $dynamicdata
                   WHERE xar_dd_propid IN (" . join(', ',$propids) . ")
                     AND xar_dd_itemid = " . xarVarPrepForStore($itemid);

        $result =& $dbconn->Execute($query);

        if (!$result) return;

        while (!$result->EOF) {
            list($propid, $value) = $result->fields;
            if (isset($value)) {
                // set the value for this property
                $this->fields[$propid]->setValue($value);
            }
            $result->MoveNext();
        }

        $result->Close();
    }

    function createItem($args)
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

        list($dbconn) = xarDBGetConn();
        $xartable = xarDBGetTables();

        $dynamicdata = $xartable['dynamic_data'];

        foreach ($propids as $propid) {
            // get the value from the corresponding property
            $value = $this->fields[$propid]->getValue();

            // invalid prop_id or undefined value (empty is OK, though !)
            if (empty($propid) || !is_numeric($propid) || !isset($value)) {
                continue;
            }

            $nextId = $dbconn->GenId($dynamicdata);

            $query = "INSERT INTO $dynamicdata (
                          xar_dd_id,
                          xar_dd_propid,
                          xar_dd_itemid,
                          xar_dd_value)
                      VALUES (
                          $nextId,
                          " . xarVarPrepForStore($propid) . ",
                          " . xarVarPrepForStore($itemid) . ",
                          '" . xarVarPrepForStore($value) . "')";

            $result =& $dbconn->Execute($query);
            if (!$result) return;

        }

        return $itemid;
    }

    function updateItem($args)
    {
        $itemid = $args['itemid'];

        $propids = array_keys($this->fields);
        if (count($propids) < 1) {
            return $itemid;
        }

        list($dbconn) = xarDBGetConn();
        $xartable = xarDBGetTables();

        $dynamicdata = $xartable['dynamic_data'];

        // get the current dynamic data fields for all properties of this item
        $query = "SELECT xar_dd_id,
                         xar_dd_propid
                    FROM $dynamicdata
                   WHERE xar_dd_propid IN (" . join(', ',$propids) . ")
                     AND xar_dd_itemid = " . xarVarPrepForStore($itemid);

        $result =& $dbconn->Execute($query);
        if (!$result) return;

        $datafields = array();
        while (!$result->EOF) {
            list($dd_id,$propid) = $result->fields;
            $datafields[$propid] = $dd_id;
            $result->MoveNext();
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
                $query = "UPDATE $dynamicdata
                             SET xar_dd_value = '" . xarVarPrepForStore($value) . "'
                           WHERE xar_dd_id = " . xarVarPrepForStore($datafields[$propid]);

            // or create it if necessary (e.g. when you add properties afterwards etc.)
            } else {
                $nextId = $dbconn->GenId($dynamicdata);

                $query = "INSERT INTO $dynamicdata (
                              xar_dd_id,
                              xar_dd_propid,
                              xar_dd_itemid,
                              xar_dd_value)
                          VALUES (
                              $nextId,
                              " . xarVarPrepForStore($propid) . ",
                              " . xarVarPrepForStore($itemid) . ",
                              '" . xarVarPrepForStore($value) . "')";
            }

            $result =& $dbconn->Execute($query);
            if (!$result) return;
        }

        return $itemid;
    }

    function deleteItem($args)
    {
        $itemid = $args['itemid'];

        $propids = array_keys($this->fields);
        if (count($propids) < 1) {
            return $itemid;
        }

        list($dbconn) = xarDBGetConn();
        $xartable = xarDBGetTables();

        $dynamicdata = $xartable['dynamic_data'];

        // get the current dynamic data fields for all properties of this item
        $query = "DELETE FROM $dynamicdata
                   WHERE xar_dd_propid IN (" . join(', ',$propids) . ")
                     AND xar_dd_itemid = " . xarVarPrepForStore($itemid);

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

        $propids = array_keys($this->fields);
        if (count($propids) < 1) {
            return;
        }

        list($dbconn) = xarDBGetConn();
        $xartable = xarDBGetTables();
        $dynamicdata = $xartable['dynamic_data'];

        // easy case where we already know the items we want
        if (count($itemids) > 0) {
            $query = "SELECT xar_dd_itemid,
                             xar_dd_propid,
                             xar_dd_value
                        FROM $dynamicdata
                       WHERE xar_dd_propid IN (" . join(', ',$propids) . ") ";

            if (count($itemids) > 1) {
                $query .= " AND xar_dd_itemid IN (" . join(', ',$itemids) . ") ";
            } else {
                $query .= " AND xar_dd_itemid = " . xarVarPrepForStore($itemids[0]) . " ";
            }

            $result =& $dbconn->Execute($query);

            if (!$result) return;

            while (!$result->EOF) {
                list($itemid,$propid, $value) = $result->fields;
                if (isset($value)) {
                    // add the item to the value list for this property
                    $this->fields[$propid]->setItemValue($itemid,$value);
                }
                $result->MoveNext();
            }

            $result->Close();

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
                foreach ($this->where as $whereitem) {
                    $query .= $whereitem['join'] . " (xar_dd_propid = " . $whereitem['field'] . ' AND xar_dd_value ' . $whereitem['clause'] . ') ';
                }
                $query .= " )";
            }
            if (count($where) > 0) {
                $query .= " )";
            }
            $result =& $dbconn->Execute($query);

            if (!$result) return;

            $itemidlist = array();
            while (!$result->EOF) {
                $values = $result->fields;
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
                $result->MoveNext();
            }
            // add the itemids to the list
            $this->itemids = array_keys($itemidlist);

            $result->Close();

    // TODO: make sure this is portable !
        // more difficult case where we need to create a pivot table, basically
        } elseif ($numitems > 0 || count($this->sort) > 0 || count($this->where) > 0) {

            $query = "SELECT xar_dd_itemid ";
            foreach ($propids as $propid) {
                $query .= ", MAX(CASE WHEN xar_dd_propid = $propid THEN xar_dd_value ELSE '' END) AS 'dd_$propid' \n";
            }
            $query .= " FROM $dynamicdata
                       WHERE xar_dd_propid IN (" . join(', ',$propids) . ") 
                    GROUP BY xar_dd_itemid ";

            if (count($this->where) > 0) {
                $query .= " HAVING ";
                foreach ($this->where as $whereitem) {
                    $query .= $whereitem['join'] . ' dd_' . $whereitem['field'] . ' ' . $whereitem['clause'] . ' ';
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
                $result =& $dbconn->SelectLimit($query, $numitems, $startnum-1);
            } else {
                $result =& $dbconn->Execute($query);
            }

            if (!$result) return;

            while (!$result->EOF) {
                $values = $result->fields;
                $itemid = array_shift($values);
                // oops, something went seriously wrong here...
                if (empty($itemid) || count($values) != count($propids)) {
                    continue;
                }
                // add this itemid to the list
                $this->itemids[] = $itemid;

                foreach ($propids as $propid) {
                    // add the item to the value list for this property
                    $this->fields[$propid]->setItemValue($itemid,array_shift($values));
                }
                $result->MoveNext();
            }

            $result->Close();

        // here we grab everyting
        } else {
            $query = "SELECT xar_dd_itemid,
                             xar_dd_propid,
                             xar_dd_value
                        FROM $dynamicdata
                       WHERE xar_dd_propid IN (" . join(', ',$propids) . ") ";

            $result =& $dbconn->Execute($query);

            if (!$result) return;

            $itemidlist = array();
            while (!$result->EOF) {
                list($itemid,$propid, $value) = $result->fields;
                $itemidlist[$itemid] = 1;
                if (isset($value)) {
                    // add the item to the value list for this property
                    $this->fields[$propid]->setItemValue($itemid,$value);
                }
                $result->MoveNext();
            }
            // add the itemids to the list
            $this->itemids = array_keys($itemidlist);

            $result->Close();
        }
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

        list($dbconn) = xarDBGetConn();
        $xartable = xarDBGetTables();
        $dynamicdata = $xartable['dynamic_data'];

        $propids = array_keys($this->fields);

        // easy case where we already know the items we want
        if (count($itemids) > 0) {
            $query = "SELECT COUNT(DISTINCT xar_dd_itemid)
                        FROM $dynamicdata
                       WHERE xar_dd_propid IN (" . join(', ',$propids) . ") ";

            if (count($itemids) > 1) {
                $query .= " AND xar_dd_itemid IN (" . join(', ',$itemids) . ") ";
            } else {
                $query .= " AND xar_dd_itemid = " . xarVarPrepForStore($itemids[0]) . " ";
            }

            $result =& $dbconn->Execute($query);

            if (!$result || $result->EOF) return;

            $numitems = $result->fields[0];

            $result->Close();

            return $numitems;

    // TODO: make sure this is portable !
        // more difficult case where we need to create a pivot table, basically
        } elseif (count($this->where) > 0) {

        // TODO: this only works for OR conditions !!!
            $query = "SELECT COUNT(DISTINCT xar_dd_itemid)
                        FROM $dynamicdata
                       WHERE ";
            // only grab the fields we're interested in here...
            foreach ($this->where as $whereitem) {
                $query .= $whereitem['join'] . ' (xar_dd_propid = ' . $whereitem['field'] . ' AND xar_dd_value ' . $whereitem['clause'] . ') ';
            }

            $result =& $dbconn->Execute($query);

            if (!$result || $result->EOF) return;

            $numitems = $result->fields[0];

            $result->Close();

            return $numitems;

        // here we grab everyting
        } else {
            $query = "SELECT COUNT(DISTINCT xar_dd_itemid)
                        FROM $dynamicdata
                       WHERE xar_dd_propid IN (" . join(', ',$propids) . ") ";

            $result =& $dbconn->Execute($query);

            if (!$result || $result->EOF) return;

            $numitems = $result->fields[0];

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
     * @raise BAD_PARAM, NO_PERMISSION
     */
    function getNextId($args)
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
            $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                         $invalid, 'Dynamic_VariableTable_DataStore', 'getNextId', 'DynamicData');
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                            new SystemException($msg));
            return;
        }

        list($dbconn) = xarDBGetConn();
        $xartable = xarDBGetTables();

        $dynamicobjects = $xartable['dynamic_objects'];

        // increase the max id for this object
        $query = "UPDATE $dynamicobjects
                     SET xar_object_maxid = xar_object_maxid + 1 ";
        if (!empty($objectid)) {
            $query .= "WHERE xar_object_id = " . xarVarPrepForStore($objectid);
        } else {
            $query .= "WHERE xar_object_moduleid = " . xarVarPrepForStore($modid) . "
                         AND xar_object_itemtype = " . xarVarPrepForStore($itemtype);
        }

        $result =& $dbconn->Execute($query);
        if (!$result) return;

        // get it back (WARNING : this is *not* guaranteed to be unique on heavy-usage sites !)
        $query = "SELECT xar_object_maxid
                    FROM $dynamicobjects ";
        if (!empty($objectid)) {
            $query .= "WHERE xar_object_id = " . xarVarPrepForStore($objectid);
        } else {
            $query .= "WHERE xar_object_moduleid = " . xarVarPrepForStore($modid) . "
                         AND xar_object_itemtype = " . xarVarPrepForStore($itemtype);
        }

        $result =& $dbconn->Execute($query);
        if (!$result || $result->EOF) return;

        $nextid = $result->fields[0];

        $result->Close();

        return $nextid;
    }

}

?>
