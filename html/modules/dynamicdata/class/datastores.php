<?php
/**
 * File: $Id$
 * 
 * Dynamic Data Store Classes
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
*/


/**
 * Utility Class to manage Dynamic Data Stores
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_DataStore_Master
{
    /**
     * Class method to get a new dynamic data store (of the right type)
     */
    function &getDataStore($name = '_dynamic_data_', $type = 'data')
    {
        switch ($type)
        {
            case 'table':
                $datastore = new Dynamic_FlatTable_DataStore($name);
                break;
            case 'data':
                $datastore = new Dynamic_VariableTable_DataStore($name);
                break;
            case 'hook':
                $datastore = new Dynamic_Hook_DataStore($name);
                break;
            case 'function':
                $datastore = new Dynamic_Function_DataStore($name);
                break;
            case 'uservars':
            // TODO: integrate user variable handling with DD
                $datastore = new Dynamic_UserVariables_DataStore($name);
                break;
            case 'modulevars':
            // TODO: integrate module variable handling with DD
                $datastore = new Dynamic_ModuleVariables_DataStore($name);
                break;

       // TODO: other data stores
            case 'ldap':
                $datastore = new Dynamic_LDAP_DataStore($name);
                break;
            case 'xml':
                $datastore = new Dynamic_XMLFile_DataStore($name);
                break;
            case 'csv':
                $datastore = new Dynamic_CSVFile_DataStore($name);
                break;
            default:
                $datastore = new Dynamic_Dummy_DataStore($name);
                break;
        }
        return $datastore;
    }

    function getDataStores()
    {
    }

    /**
     * Get possible data sources (// TODO: for a module ?)
     */
    function &getDataSources($args = array())
    {
        list($dbconn) = xarDBGetConn();
        $xartable = xarDBGetTables();

        $systemPrefix = xarDBGetSystemTablePrefix();
        $metaTable = $systemPrefix . '_tables';

    // TODO: remove Xaraya system tables from the list of available sources ?
        $query = "SELECT xar_table,
                         xar_field,
                         xar_type,
                         xar_size
                  FROM $metaTable
                  ORDER BY xar_table ASC, xar_field ASC";

        $result =& $dbconn->Execute($query);

        if (!$result) return;

        $sources = array();

        // default data source is dynamic data
        $sources[] = 'dynamic_data';

        // user variables
        $sources[] = 'user variables';

        // module variables
        $sources[] = 'module variables';

        // session variables // TODO: perhaps someday, if this makes sense
        //$sources[] = 'session variables';

    // TODO: re-evaluate this once we're further along
        // hook modules manage their own data
        $sources[] = 'hook module';

        // user functions manage their own data
        $sources[] = 'user function';

        // add the list of table + field
        while (!$result->EOF) {
            list($table, $field, $type, $size) = $result->fields;
        // TODO: what kind of security checks do we want/need here ?
            //if (xarSecAuthAction(0, 'DynamicData::Field', "$name:$type:$id", ACCESS_READ)) {
            //}
            $sources[] = "$table.$field";
            $result->MoveNext();
        }

        $result->Close();

        return $sources;
    }
}

/**
 * Base class for Dynamic Data Stores
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_DataStore
{
    var $name;     // some static name, or the table name, or the moduleid + itemtype, or ...
    var $type;
    var $fields;   // array of $name => reference to property in Dynamic_Object*
    var $primary;

    var $sort;
    var $where;
    var $join;
    var $itemids;  // reference to itemids in Dynamic_Object_List
    var $itemtype; // reference to itemtype in Dynamic_Object

    function Dynamic_DataStore($name)
    {
        $this->name = $name;
        $this->fields = array();
        $this->primary = null;
        $this->sort = array();
        $this->where = array();
        $this->join = array();
    }

    /**
     * Get the field name used to identify this property (by default, the property name itself)
     */
    function getFieldName(&$property)
    {
        return $property->name;
    }

    /**
     * Add a field to get/set in this data store, and its corresponding property
     */
    function addField(&$property)
    {
        $name = $this->getFieldName($property);
        if (!isset($name)) return;

        $this->fields[$name] = &$property; // use reference to original property

        if (!isset($this->primary) && $property->type == 21) { // Item ID
            $this->setPrimary($property);
        }
    }

    /**
     * Set the primary key for this data store (only 1 allowed for now)
     */
    function setPrimary(&$property)
    {
        $name = $this->getFieldName($property);
        if (!isset($name)) return;

        $this->primary = $name;
    }

    /**
     * Add a sort criteria for this data store (for getItems)
     */
    function addSort(&$property, $sortorder = 'ASC')
    {
        $name = $this->getFieldName($property);
        if (!isset($name)) return;

        $this->sort[] = array('field'     => $name,
                              'sortorder' => $sortorder);
    }

    /**
     * Remove all sort criteria for this data store (for getItems)
     */
    function cleanSort()
    {
        $this->sort = array();
    }

    /**
     * Add a where clause for this data store (for getItems)
     */
    function addWhere(&$property, $clause, $join)
    {
        $name = $this->getFieldName($property);
        if (!isset($name)) return;

        $this->where[] = array('field'  => $name,
                               'clause' => $clause,
                               'join'   => $join);
    }

    /**
     * Remove all where criteria for this data store (for getItems)
     */
    function cleanWhere()
    {
        $this->where = array();
    }

    /**
     * Add a join condition to this data store (TODO)
     */
    function addJoin(&$property, $condition)
    {
        $name = $this->getFieldName($property);
        if (!isset($name)) return;

        $this->join[] = array('field'     => $name,
                              //'source'    => $source,
                              'condition' => $condition);
    }

    /**
     * Remove all join criteria for this data store (for getItems)
     */
    function cleanJoin()
    {
        $this->join = array();
    }

}

/**
 * Base class for SQL Data Stores
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_SQL_DataStore extends Dynamic_DataStore
{
    // some common methods/properties for SQL here
}

/**
 * Data Store is a flat SQL table (= typical module tables)
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
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

        if (count($itemids) == 0) {
            $saveids = 1;
        } else {
            $saveids = 0;
        }
        while (!$result->EOF) {
            $values = $result->fields;
            $itemid = array_shift($values);
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

}

/**
 * Data Store is a variable SQL table (= only xar_dynamic_data for now)
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
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

            while (!$result->EOF) {
                list($itemid,$propid, $value) = $result->fields;
                // add this itemid to the list
                if (!in_array($itemid,$this->itemids)) {
                    $this->itemids[] = $itemid;
                }
                if (isset($value)) {
                    // add the item to the value list for this property
                    $this->fields[$propid]->setItemValue($itemid,$value);
                }
                $result->MoveNext();
            }

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
     * @returns integer
     * @return value of the next id
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

/**
 * Data Store is managed by a hook/utility module
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_Hook_DataStore extends Dynamic_DataStore
{
    /**
     * Get the field name used to identify this property (we use the hook name here)
     */
    function getFieldName(&$property)
    {
        // check if this is a known module, based on the name of the property type
        $proptypes = Dynamic_Property_Master::getPropertyTypes();
        $curtype = $property->type;
        if (!empty($proptypes[$curtype]['name'])) {
            return $proptypes[$curtype]['name'];
        }
    }

    function setPrimary(&$property)
    {
        // not applicable !?
    }

    function getItem($args)
    {
        $modid = $args['modid'];
        $itemtype = $args['itemtype'];
        $itemid = $args['itemid'];
        $modname = $args['modname'];

        foreach (array_keys($this->fields) as $hook) {
            if (xarModIsAvailable($hook)) {
            // TODO: find some more consistent way to do this !
                $value = xarModAPIFunc($hook,'user','get',
                                       array('modname' => $modname,
                                             'modid' => $modid,
                                             'itemtype' => $itemtype,
                                             'itemid' => $itemid,
                                             'objectid' => $itemid));
                // see if we got something interesting in return
                if (isset($value)) {
                    $this->fields[$hook]->setValue($value);
                } elseif (xarExceptionMajor() != XAR_NO_EXCEPTION) {
                    // ignore any exceptions on retrieval for now
                    xarExceptionFree();
                }
            }
        }
    }

}

/**
 * Data Store is offered by a user function
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_Function_DataStore extends Dynamic_DataStore
{
    /**
     * Get the field name used to identify this property (the property validation holds the function name here - for now...)
     */
    function getFieldName(&$property)
    {
        return $property->validation;
    }

    function setPrimary(&$property)
    {
        // not applicable !?
    }

    function getItem($args)
    {
        $modid = $args['modid'];
        $itemtype = $args['itemtype'];
        $itemid = $args['itemid'];
        $modname = $args['modname'];

        foreach (array_keys($this->fields) as $function) {
            // split into module, type and function
    // TODO: improve this ?
            list($fmod,$ftype,$ffunc) = explode('_',$function);
            // see if the module is available
            if (!xarModIsAvailable($fmod)) {
                continue;
            }
            // see if we're dealing with an API function or a GUI one
            if (preg_match('/api$/',$ftype)) {
                $ftype = preg_replace('/api$/','',$ftype);
                // try to invoke the function with some common parameters
            // TODO: standardize this, or allow the admin to specify the arguments
                $value = xarModAPIFunc($fmod,$ftype,$ffunc,
                                       array('modname' => $modname,
                                             'modid' => $modid,
                                             'itemtype' => $itemtype,
                                             'itemid' => $itemid,
                                             'objectid' => $itemid));
                // see if we got something interesting in return
                if (isset($value)) {
                    $this->fields[$function]->setValue($value);
                } elseif (xarExceptionMajor() != XAR_NO_EXCEPTION) {
                    // ignore any exceptions on retrieval for now
                    xarExceptionFree();
                }
            } else {
            // TODO: don't we want auto-loading for xarModFunc too ???
                // try to load the module GUI
                if (!xarModLoad($fmod,$ftype)) {
                    if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
                        // ignore any exceptions on retrieval for now
                        xarExceptionFree();
                    }
                    continue;
                }
                // try to invoke the function with some common parameters
            // TODO: standardize this, or allow the admin to specify the arguments
                $value = xarModFunc($fmod,$ftype,$ffunc,
                                    array('modname' => $modname,
                                          'modid' => $modid,
                                          'itemtype' => $itemtype,
                                          'itemid' => $itemid,
                                          'objectid' => $itemid));
                // see if we got something interesting in return
                if (isset($value)) {
                    $this->fields[$function]->setValue($value);
                } elseif (xarExceptionMajor() != XAR_NO_EXCEPTION) {
                    // ignore any exceptions on retrieval for now
                    xarExceptionFree();
                }
            }
        }
    }
}

/**
 * Data Store is an LDAP directory
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_LDAP_DataStore extends Dynamic_DataStore
{
}

/**
 * Base class for File Data Stores
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_File_DataStore extends Dynamic_DataStore
{
    // some common methods/properties for files here
}

/**
 * Data Store is an XML file
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_XMLFile_DataStore extends Dynamic_File_DataStore
{
}

/**
 * Data Store is a CSV file (comma-separated values)
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_CSVFile_DataStore extends Dynamic_File_DataStore
{
}

/**
 * Data Store is a join between other data stores (?)
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_Join_DataStore extends Dynamic_DataStore
{
}

/**
 * Data Store is a dummy (for in-memory data storage, perhaps)
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_Dummy_DataStore extends Dynamic_DataStore
{
    function getItem($args = array())
    {
    }

    function getItems($args = array())
    {
    }
}

/**
 * Data Store is the user variables // TODO: integrate user variable handling with DD
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_UserVariables_DataStore extends Dynamic_DataStore
{
    var $modname;

    function Dynamic_UserVariables_DataStore($name)
    {
        // invoke the default constructor from our parent class
        $this->Dynamic_DataStore($name);

        // keep track of the concerned module for user settings
    // TODO: the concerned module is currently hiding in the third part of the name :)
        list($fixed1,$fixed2,$modid) = explode('_',$name);
        if (empty($modid)) {
            $modid = xarModGetIDFromName(xarModGetName());
        }
        $modinfo = xarModGetInfo($modid);
        if (!empty($modinfo['name'])) {
            $this->modname = $modinfo['name'];
        }
    }

    /**
     * Get the field name used to identify this property (we use the name of the module.property here)
     */
    function getFieldName(&$property)
    {
    // TODO: check this
        // we add the module name in here by default, for user preferences per module
        return $this->modname.'.'.$property->name;
    }

    function getItem($args)
    {
        if (empty($args['itemid'])) {
            // default is the current user (if any)
            $itemid = xarUserGetVar('uid');
        } else {
            $itemid = $args['itemid'];
        }

        $fieldlist = array_keys($this->fields);
        if (count($fieldlist) < 1) {
            return;
        }

    // TODO: re-introduce xarUserGetVars ?

        foreach ($fieldlist as $field) {
            // get the value from the user variables
            $value = xarUserGetVar($field,$itemid);

            // set the value for this property
            if (isset($value)) {
                $this->fields[$field]->setValue($value);
            } else {
                // use the equivalent module variable as default
                list($module,$name) = explode('.',$field);
                $this->fields[$field]->setValue(xarModGetVar($module,$name));
            }
        }
    }

    function createItem($args)
    {
        // There's no difference with updateItem() here, because xarUserSetVar() handles that
        return $this->updateItem($args);
    }

    function updateItem($args)
    {
        if (empty($args['itemid'])) {
            // default is the current user (if any)
            $itemid = xarUserGetVar('uid');
        } else {
            $itemid = $args['itemid'];
        }

        $fieldlist = array_keys($this->fields);
        if (count($fieldlist) < 1) {
            return;
        }

        foreach ($fieldlist as $field) {
            // get the value from the corresponding property
            $value = $this->fields[$field]->getValue();
            // skip fields where values aren't set
            if (!isset($value)) {
                continue;
            }
            xarUserSetVar($field,$value,$itemid);
        }
        return $itemid;
    }

    function deleteItem($args)
    {
        if (empty($args['itemid'])) {
            // default is the current user (if any)
            $itemid = xarUserGetVar('uid');
        } else {
            $itemid = $args['itemid'];
        }

        $fieldlist = array_keys($this->fields);
        if (count($fieldlist) < 1) {
            return;
        }

    // TODO: hmmm, how are we supposed to delete user variables these days ? :-)
        foreach ($fieldlist as $field) {
        //    xarUserDelVar($field,$itemid);
        }

        return $itemid;
    }

    function getItems($args = array())
    {
        // TODO: not supported by xarUser*Var
    }

    function countItems($args = array())
    {
        // TODO: not supported by xarUser*Var
        return 0;
    }

}

/**
 * Data Store is the module variables // TODO: integrate module variable handling with DD
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 */
class Dynamic_ModuleVariables_DataStore extends Dynamic_DataStore
{
    var $modname;

    function Dynamic_ModuleVariables_DataStore($name)
    {
        // invoke the default constructor from our parent class
        $this->Dynamic_DataStore($name);

        // keep track of the concerned module for module settings
    // TODO: the concerned module is currently hiding in the third part of the data store name :)
        list($fixed1,$fixed2,$modid) = explode('_',$name);
        if (empty($modid)) {
            $modid = xarModGetIDFromName(xarModGetName());
        }
        $modinfo = xarModGetInfo($modid);
        if (!empty($modinfo['name'])) {
            $this->modname = $modinfo['name'];
        }
    }

    /**
     * Get the field name used to identify this property (we use the name of the property here)
     */
    function getFieldName(&$property)
    {
        return $property->name;
    }

    function getItem($args)
    {
        if (empty($args['itemid'])) {
            // by default, there's only 1 item here, except if your module has several
            // itemtypes with different values for the same bunch of settings [like articles :)]
            $itemid = 0;
        } else {
            $itemid = $args['itemid'];
        }

        $fieldlist = array_keys($this->fields);
        if (count($fieldlist) < 1) {
            return;
        }

        // let's cheat a little bit here, and preload everything :-)
        xarMod_getVarsByModule($this->modname);

        foreach ($fieldlist as $field) {
            // get the value from the module variables
        // TODO: use $field.$itemid for modules with several itemtypes ? [like articles :)]
            $value = xarModGetVar($this->modname,$field);
            // set the value for this property
            $this->fields[$field]->setValue($value);
        }
    }

    function createItem($args)
    {
        // There's no difference with updateItem() here, because xarModSetVar() handles that
        return $this->updateItem($args);
    }

    function updateItem($args)
    {
        if (empty($args['itemid'])) {
            // by default, there's only 1 item here, except if your module has several
            // itemtypes with different values for the same bunch of settings [like articles :)]
            $itemid = 0;
        } else {
            $itemid = $args['itemid'];
        }

        $fieldlist = array_keys($this->fields);
        if (count($fieldlist) < 1) {
            return;
        }

        foreach ($fieldlist as $field) {
            // get the value from the corresponding property
            $value = $this->fields[$field]->getValue();
            // skip fields where values aren't set
            if (!isset($value)) {
                continue;
            }
            xarModSetVar($this->modname,$field,$value);
        }
        return $itemid;
    }

    function deleteItem($args)
    {
        if (empty($args['itemid'])) {
            // by default, there's only 1 item here, except if your module has several
            // itemtypes with different values for the same bunch of settings [like articles :)]
            $itemid = 0;
        } else {
            $itemid = $args['itemid'];
        }

        $fieldlist = array_keys($this->fields);
        if (count($fieldlist) < 1) {
            return;
        }

        foreach ($fieldlist as $field) {
            xarModDelVar($this->modname,$field);
        }

        return $itemid;
    }

    function getItems($args = array())
    {
        // TODO: not supported by xarMod*Var
    }

    function countItems($args = array())
    {
        // TODO: not supported by xarMod*Var
        return 0;
    }

}

?>
