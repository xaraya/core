<?php
/**
 * File: $Id$
 *
 * Dynamic Data Admin API
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
*/

// ----------------------------------------------------------------------
// Generic item create/update/delete APIs
// ----------------------------------------------------------------------

/**
 * create a new item (the whole item or some dynamic data fields for it)
 *
 * @author the DynamicData module development team
 * @param $args['modid'] module id for the original item
 * @param $args['itemtype'] item type of the original item
 * @param $args['itemid'] item id of the original item
 * @param $args['values'] array of prop_id => value, or
 * @param $args['fields'] array containing the field definitions and values
 * @returns mixed
 * @return item id on success, null on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_create($args)
{
    extract($args);

    $invalid = array();
    if (!isset($itemid) || !is_numeric($itemid)) {
        $invalid[] = 'item id';
    }
    if ((isset($fields) && is_array($fields)) ||
        (isset($values) && is_array($values)) ) {
    } else {
        $invalid[] = xarML('fields or values');
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'admin', 'create', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if (!xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:$itemid", ACCESS_ADD)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    if (!isset($fields) || !is_array($fields)) {
        $fields = array();
    }
    if (!isset($values) || !is_array($values)) {
        $values = array();
    }

    if (!xarModAPILoad('dynamicdata','user')) return;

    // different processing depending on the data source
    list($dynprops,$tables,$hooks,$functions,$itemidname) =
                          xarModAPIFunc('dynamicdata','user','splitfields',
                                                          // pass by reference
                                        array('fields' => &$fields));

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    // check that we have a valid item id, or that we can create one if it's set to 0
    if (empty($itemid)) {
        if (empty($itemidname)) {
            $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                        'item id field', 'admin', 'create', 'DynamicData');
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                           new SystemException($msg));
            return;

        // hmmm, we already have an itemid value in the fields array, apparently
        } elseif (!empty($fields[$itemidname]['value'])) {
            $itemid = $fields[$itemidname]['value'];

        // we need to manage our own item ids here, and we can't use some sequential field
        } elseif ($fields[$itemidname]['source'] == 'dynamic_data') {
            $itemid = xarModAPIFunc('dynamicdata','admin','getnextid',
                                    array('modid' => $modid,
                                          'itemtype' => $itemtype));
            if (empty($itemid)) {
                $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                            'generated item id', 'admin', 'create', 'DynamicData');
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException($msg));
                return;
            }
            $fields[$itemidname]['value'] = $itemid;

        // some dedicated table will hold the next item id -> handle that first !
        } elseif (preg_match('/^(\w+)\.(\w+)$/', $fields[$itemidname]['source'], $matches)) {
            $table = $matches[1];
            $fieldname = $matches[2];
            if (!isset($tables[$table]) || count($tables[$table]) < 1) {
                $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                            'table with item id', 'admin', 'create', 'DynamicData');
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException($msg));
                return;
            }

            // get the next id (or dummy) from ADODB for this table
            $nextId = $dbconn->GenId($table);
            $fields[$itemidname]['value'] = $nextId;

            $query = "INSERT INTO $table ( ";
            $join = '';
            foreach ($tables[$table] as $field => $name) {
                // skip fields where values aren't set
                if (!isset($fields[$name]['value'])) {
                    continue;
                }
                $query .= $join . $field;
                $join = ', ';
            }
            $query .= " ) VALUES ( ";
            $join = '';
            foreach ($tables[$table] as $field => $name) {
                // skip fields where values aren't set
                if (!isset($fields[$name]['value'])) {
                    continue;
                }
                $value = $fields[$name]['value'];
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
            if (empty($result)) return;

            // get the real next id from ADODB for this table now
            $itemid = $dbconn->PO_Insert_ID($table, $fieldname);

            if (empty($itemid)) {
                $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                            'item id from table '.$table, 'admin', 'create', 'DynamicData');
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException($msg));
                return;
            }
            $fields[$itemidname]['value'] = $itemid;

            // been there, done that :)
            unset($tables[$table]);

        // other sources for item ids are not supported at the moment !
        } else {
            $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                        'source for item id', 'admin', 'create', 'DynamicData');
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                           new SystemException($msg));
            return;
        }
    }

// TODO: create items in any other tables that are *not* the primary one :)

    // compatibility mode if we only get $values instead of $fields
    if (empty($fields) && !empty($values) && count($values) > 0) {
        $fields = array();
        $i = 1;
        foreach ($values as $prop_id => $value) {
            $name = 'name_' . $i;
            $dynprops[$prop_id] = $name;
            $fields[$name]['value'] = $value;
            $i++;
        }
    }

    $dynamicdata = $xartable['dynamic_data'];

    foreach ($dynprops as $prop_id => $name) {
        $value = $fields[$name]['value'];
        // invalid prop_id or undefined value (empty is OK, though !)
        if (empty($prop_id) || !is_numeric($prop_id) || !isset($value)) {
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
              " . xarVarPrepForStore($prop_id) . ",
              " . xarVarPrepForStore($itemid) . ",
              '" . xarVarPrepForStore($value) . "')";

        $result = $dbconn->Execute($query);
        if (!isset($result)) return;

    }

    return $itemid;
}

/**
 * delete an item (the whole item or the dynamic data fields of it)
 *
 * @author the DynamicData module development team
 * @param $args['itemid'] item id of the original item
 * @param $args['modid'] module id for the original item
 * @param $args['itemtype'] item type of the original item
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_delete($args)
{
    extract($args);

    $invalid = array();
    if (!isset($itemid) || !is_numeric($itemid)) {
        $invalid[] = 'item id';
    }
    if (!isset($modid) || !is_numeric($modid)) {
        $invalid[] = 'module id';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'admin', 'delete', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $itemtype = 0;
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if (!xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:$itemid", ACCESS_DELETE)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    if (!xarModAPILoad('dynamicdata', 'user'))
    {
        $msg = xarML('Unable to load #(1) #(2) API',
                    'dynamicdata','user');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNABLE_TO_LOAD',
                       new SystemException($msg));
        return;
    }
    $fields = xarModAPIFunc('dynamicdata','user','getprop',
                            array('modid' => $modid,
                                  'itemtype' => $itemtype));
    if (!isset($fields) || $fields == false) {
        return true;
    }


// TODO: don't delete if the data source is not in dynamic_data


    $ids = array();
    foreach ($fields as $field) {
        $ids[] = $field['id'];
    }

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $dynamicdata = $xartable['dynamic_data'];

    $sql = "DELETE FROM $dynamicdata
            WHERE xar_dd_propid IN (" . join(', ',$ids) . ")
              AND xar_dd_itemid = " . xarVarPrepForStore($itemid);

    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so raise an
    // appropriate exception
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $sql);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    return true;
}

/**
 * update an item (the whole item or the dynamic data fields of it)
 *
 * @author the DynamicData module development team
 * @param $args['itemid'] item id of the original item
 * @param $args['modid'] module id for the original item
 * @param $args['itemtype'] item type of the original item
 * @param $args['values'] array of prop_id => value, or
 * @param $args['fields'] array containing the field definitions and values
 * @returns mixed
 * @return item id on success, null on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_update($args)
{
    extract($args);

    $invalid = array();
    if (!isset($itemid) || !is_numeric($itemid) || empty($itemid)) { // we can't accept item id 0 here
        $invalid[] = 'item id';
    }
    if (!isset($modid) || !is_numeric($modid)) {
        $invalid[] = 'module id';
    }
    if ((isset($fields) && is_array($fields)) ||
        (isset($values) && is_array($values)) ) {
    } else {
        $invalid[] = xarML('fields or values');
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'admin', 'update', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $itemtype = 0;
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if (!xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:$itemid", ACCESS_EDIT)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    if (!isset($fields) || !is_array($fields)) {
        $fields = array();
    }
    if (!isset($values) || !is_array($values)) {
        $values = array();
    }

    if (!xarModAPILoad('dynamicdata','user')) return;

    // different processing depending on the data source
    list($dynprops,$tables,$hooks,$functions,$itemidname) =
                          xarModAPIFunc('dynamicdata','user','splitfields',
                                                          // pass by reference
                                        array('fields' => &$fields));

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $systemPrefix = xarDBGetSystemTablePrefix();
    $metaTable = $systemPrefix . '_tables';

// TODO: find some way to update several tables if relevant ?

    // update properties in some known table field
    foreach ($tables as $table => $fieldlist) {
        // look for the item id field
        if (!empty($itemidname) && preg_match('/^(\w+)\.(\w+)$/', $fields[$itemidname]['source'], $matches)
            && $table == $matches[1] && isset($tables[$table][$matches[2]])) {
            $field = $matches[2];
        } else {
            // For now, we look for a primary key (or increment, perhaps ?),
            // and hope it corresponds to the item id :-)
        // TODO: improve this once we can define better relationships
            $query = "SELECT xar_field, xar_type
                        FROM $metaTable
                       WHERE xar_primary_key = 1
                         AND xar_table='" . xarVarPrepForStore($table) . "'";

            $result = $dbconn->Execute($query);

            if (!isset($result)) return;

            if ($result->EOF) {
                continue;
            }
            list($field, $type) = $result->fields;
            $result->Close();
        }

        // can't really do much without the item id field at the moment
        if (empty($field)) {
            continue;
        }

        $query = "UPDATE $table ";
        $join = 'SET ';
        foreach ($tables[$table] as $fieldname => $name) {
            // skip fields where values aren't set
            if (!isset($fields[$name]['value']) || $fieldname == $field) { // don't update item id
                continue;
            }
            $value = $fields[$name]['value'];
            // TODO: improve this based on static table info
            if (is_numeric($value)) {
                $query .= $join . $fieldname . ' = ' . $value;
            } else {
                $query .= $join . $fieldname . ' = ' . "'" . xarVarPrepForStore($value) . "'";
            }
            $join = ', ';
        }
        $query .= " WHERE $field = " . xarVarPrepForStore($itemid);

        $result = $dbconn->Execute($query);
        if (!isset($result)) return;
    }

    // compatibility mode if we only get $values instead of $fields
    if (empty($fields) && !empty($values) && count($values) > 0) {
        $fields = array();
        $i = 1;
        foreach ($values as $prop_id => $value) {
            $name = 'name_' . $i;
            $dynprops[$prop_id] = $name;
            $fields[$name]['value'] = $value;
            $i++;
        }
    }

    if (count($dynprops) < 1) {
        return $itemid;
    }

    $dynamicdata = $xartable['dynamic_data'];

    // get the current dynamic data fields for all properties of this item
    $query = "SELECT xar_dd_id,
                     xar_dd_propid
                FROM $dynamicdata
               WHERE xar_dd_itemid = " . xarVarPrepForStore($itemid);

    $result = $dbconn->Execute($query);
    if (!isset($result)) return;

    $datafields = array();
    while (!$result->EOF) {
        list($dd_id,$prop_id) = $result->fields;
        $datafields[$prop_id] = $dd_id;
        $result->MoveNext();
    }

    $result->Close();

    foreach ($dynprops as $prop_id => $name) {
        $value = $fields[$name]['value'];
        // invalid prop_id or undefined value (empty is OK, though !)
        if (empty($prop_id) || !is_numeric($prop_id) || !isset($value)) {
            continue;
        }

        // update the dynamic data field if it exists
        if (!empty($datafields[$prop_id])) {
            $query = "UPDATE $dynamicdata
                         SET xar_dd_value = '" . xarVarPrepForStore($value) . "'
                       WHERE xar_dd_id = " . xarVarPrepForStore($datafields[$prop_id]);

        // or create it if necessary
        } else {
            $nextId = $dbconn->GenId($dynamicdata);

            $query = "INSERT INTO $dynamicdata (
                          xar_dd_id,
                          xar_dd_propid,
                          xar_dd_itemid,
                          xar_dd_value)
                      VALUES (
                          $nextId,
                          " . xarVarPrepForStore($prop_id) . ",
                          " . xarVarPrepForStore($itemid) . ",
                          '" . xarVarPrepForStore($value) . "')";
        }

        $result = $dbconn->Execute($query);
        if (!isset($result)) return;
    }

    return $itemid;
}

/**
 * get next item id (for objects stored only in dynamic data table)
 *
 * @author the DynamicData module development team
 * @param $args['objectid'] dynamic object id for the original item, or
 * @param $args['modid'] module id for the original item +
 * @param $args['itemtype'] item type of the original item
 * @returns integer
 * @return value of the next id
 * @raise BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_getnextid($args)
{
    extract($args);

    $invalid = array();
    if (!isset($modid) || !is_numeric($modid)) {
        $invalid[] = 'module id';
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }

    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'admin', 'getnextid', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $dynamicobjects = $xartable['dynamic_objects'];

    // increase the max id for this object
    $query = "UPDATE $dynamicobjects
                 SET xar_object_maxid = xar_object_maxid + 1
               WHERE xar_object_moduleid = " . xarVarPrepForStore($modid) . "
                 AND xar_object_itemtype = " . xarVarPrepForStore($itemtype);

    $result = $dbconn->Execute($query);
    if (!isset($result)) return;

    // get it back (WARNING : this is *not* guaranteed to be unique on heavy-usage sites !)
    $query = "SELECT xar_object_maxid
                FROM $dynamicobjects
               WHERE xar_object_moduleid = " . xarVarPrepForStore($modid) . "
                 AND xar_object_itemtype = " . xarVarPrepForStore($itemtype);

    $result = $dbconn->Execute($query);
    if (!isset($result)) return;

    if ($result->EOF) return;

    $nextid = $result->fields[0];

    $result->Close();

    return $nextid;
}


/**
 * get next item type (for objects stored only in dynamic data table)
 *
 * @author the DynamicData module development team
 * @param $args['modid'] module id for the original item +
 * @returns integer
 * @return value of the next item type
 * @raise BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_getnexttype($args)
{
    extract($args);

    $invalid = array();
    if (!isset($modid) || !is_numeric($modid)) {
        $invalid[] = 'module id';
    }

    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'admin', 'getnextid', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $dynamicobjects = $xartable['dynamic_objects'];

    $query = "SELECT MAX(xar_object_itemtype)
                FROM $dynamicobjects
               WHERE xar_object_moduleid = " . xarVarPrepForStore($modid);

    $result = $dbconn->Execute($query);
    if (!isset($result)) return;

    if ($result->EOF) return;

    $nexttype = $result->fields[0];
    $nexttype++;

    $result->Close();

    return $nexttype;
}

// ----------------------------------------------------------------------
// Specific APIs for dynamic objects (= objectid 1)
// ----------------------------------------------------------------------

/**
 * create a new dynamic object
 *
 * @author the DynamicData module development team
 * @param $args['name'] name of the object to create
 * @param $args['label'] label of the object to create
 * @param $args['moduleid'] module id of the object to create
 * @param $args['itemtype'] item type of the object to create
 * @param $args['urlparam'] URL parameter to use for the item (itemid, exid, aid, ...)
 * @param $args['config'] some configuration for the object (free to define and use)
 * @param $args['objectid'] object id of the object to create (for import only)
 * @param $args['maxid'] for purely dynamic objects, the current max. itemid (for import only)
 * @returns int
 * @return object ID on success, null on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_createobject($args)
{
    extract($args);

    if (!xarModAPILoad('dynamicdata','user')) return;

    // get the properties of the 'objects' object
    $fields = xarModAPIFunc('dynamicdata','user','getprop',
                            array('objectid' => 1)); // the objects
    if (empty($moduleid)) {
        // defaults to the current module
        $moduleid = xarModGetIDFromName(xarModGetName());
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }
    $itemid = 0;

    // if we have a dummy itemtype of -1, we look for the next available one
    if ($itemtype < 0) {
        $itemtype = xarModAPIFunc('dynamicdata','admin','getnexttype',
                                  array('modid' => $moduleid));
        $args['itemtype'] = $itemtype;
    }

    // the acceptable arguments correspond to the property names !
    foreach ($fields as $name => $field) {
        if (isset($args[$name])) {
            $fields[$name]['value'] = $args[$name];
        }
    }

    $objectid = xarModAPIFunc('dynamicdata', 'admin', 'create',
                              array('modid'    => $moduleid,
                                    'itemtype' => $itemtype,
                                    'itemid'   => $itemid,
                                    'fields'   => $fields));
    if (!isset($objectid)) return;

    return $objectid;
}

// ----------------------------------------------------------------------
// Specific APIs for dynamic properties (= objectid 2)
// ----------------------------------------------------------------------

/**
 * create a new property field for an object
 *
 * @author the DynamicData module development team
 * @param $args['name'] name of the property to create
 * @param $args['label'] label of the property to create
 * @param $args['objectid'] object id of the property to create
 * @param $args['moduleid'] module id of the property to create
 * @param $args['itemtype'] item type of the property to create
 * @param $args['type'] type of the property to create
 * @param $args['default'] default of the property to create
 * @param $args['source'] data source for the property (dynamic_data table or other)
 * @param $args['status'] status of the property to create (disabled/active/...)
 * @param $args['order'] order of the property to create
 * @param $args['validation'] validation of the property to create
 * @returns int
 * @return property ID on success, null on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_createproperty($args)
{
    extract($args);

    // Required arguments
    $invalid = array();
    if (!isset($name) || !is_string($name)) {
        $invalid[] = 'name';
    }
    if (!isset($type) || !is_numeric($type)) {
        $invalid[] = 'type';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'admin', 'createproperty', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if (!xarSecAuthAction(0, 'DynamicData::Field', "$name:$type:", ACCESS_ADMIN)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    if (empty($moduleid)) {
        // defaults to the current module
        $moduleid = xarModGetIDFromName(xarModGetName());
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }
    $itemid = 0;

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if (!xarSecAuthAction(0, 'DynamicData::Item', "$moduleid:$itemtype:", ACCESS_ADMIN)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    // get the properties of the 'properties' object
    $fields = xarModAPIFunc('dynamicdata','user','getprop',
                            array('objectid' => 2)); // the properties

    // the acceptable arguments correspond to the property names !
    foreach ($fields as $name => $field) {
        if (isset($args[$name])) {
            $fields[$name]['value'] = $args[$name];
        }
    }
/* this is already done via the table definition of xar_dynamic_properties
    // fill in some defaults if necessary
    if (empty($fields['source']['value'])) {
        $fields['source']['value'] = 'dynamic_data';
    }
    if (empty($fields['validation']['value'])) {
        $fields['validation']['value'] = '';
    }
*/

    $propid = xarModAPIFunc('dynamicdata', 'admin', 'create',
                            array('modid'    => $moduleid,
                                  'itemtype' => $itemtype,
                                  'itemid'   => $itemid,
                                  'fields'   => $fields));
    if (!isset($propid)) return;

    return $propid;
}

/**
 * import property fields from a static table
 *
 * @author the DynamicData module development team
 * @param $args['modid'] module id of the table to import
 * @param $args['itemtype'] item type of the table to import
 * @param $args['table'] name of the table you want to import
 * @param $args['objectid'] object id to assign these properties to
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_importproperties($args)
{
    extract($args);

    // Required arguments
    $invalid = array();
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module id', 'admin', 'importproperties', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if (!xarSecAuthAction(0, 'DynamicData::Field', "::", ACCESS_ADD)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    if (empty($itemtype)) {
        $itemtype = 0;
    }
    if (empty($table)) {
        $table = '';
    }

    if (!xarModAPILoad('dynamicdata', 'user')) return;

    // search for an object, or create one
    if (empty($objectid)) {
        $object = xarModAPIFunc('dynamicdata','user','getobject',
                                array('modid' => $modid,
                                      'itemtype' => $itemtype));
        if (!isset($object)) {
            $modinfo = xarModGetInfo($modid);
            $name = $modinfo['name'];
            if (!empty($itemtype)) {
                $name .= '_' . $itemtype;
            }
            $objectid = xarModAPIFunc('dynamicdata','admin','createobject',
                                      array('moduleid' => $modid,
                                            'itemtype' => $itemtype,
                                            'name' => $name,
                                            'label' => ucfirst($name)));
            if (!isset($objectid)) return;
        } else {
            $objectid = $object['id']['value'];
        }
    }

    $fields = xarModAPIFunc('dynamicdata','user','getstatic',
                            array('modid' => $modid,
                                  'itemtype' => $itemtype,
                                  'table' => $table));
    if (!isset($fields) || !is_array($fields)) return;

    // create new properties
    foreach ($fields as $name => $field) {
        $prop_id = xarModAPIFunc('dynamicdata','admin','createproperty',
                                array('name' => $name,
                                      'label' => $field['label'],
                                      'objectid' => $objectid,
                                      'moduleid' => $modid,
                                      'itemtype' => $itemtype,
                                      'type' => $field['type'],
                                      'default' => $field['default'],
                                      'source' => $field['source'],
                                      'status' => $field['status'],
                                      'order' => $field['order'],
                                      'validation' => $field['validation']));
        if (empty($prop_id)) {
            return;
        }
    }
    return true;
}


// ----------------------------------------------------------------------
// Older Properties APIs (being replace by the above)
// ----------------------------------------------------------------------

/**
 * update a property field
 *
 * @author the DynamicData module development team
 * @param $args['prop_id'] property id of the item field to update
 * @param $args['modid'] module id of the item field to update (optional)
 * @param $args['itemtype'] item type of the item field to update (optional)
 * @param $args['name'] name of the field to update (optional)
 * @param $args['label'] label of the field to update
 * @param $args['type'] type of the field to update
 * @param $args['default'] default of the field to update (optional)
 * @param $args['source'] data source of the field to update (optional)
 * @param $args['status'] status of the field to update (optional)
 * @param $args['validation'] validation of the field to update (optional)
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_updateprop($args)
{
    extract($args);

    // Required arguments
    $invalid = array();
    if (!isset($prop_id) || !is_numeric($prop_id)) {
        $invalid[] = 'property id';
    }
    if (!isset($label) || !is_string($label)) {
        $invalid[] = 'label';
    }
    if (!isset($type) || !is_numeric($type)) {
        $invalid[] = 'type';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'admin', 'updateprop', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if (!xarSecAuthAction(0, 'DynamicData::Field', "$name:$type:$prop_id", ACCESS_EDIT)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    // Get database setup - note that both xarDBGetConn() and xarDBGetTables()
    // return arrays but we handle them differently.  For xarDBGetConn()
    // we currently just want the first item, which is the official
    // database handle.  For xarDBGetTables() we want to keep the entire
    // tables array together for easy reference later on
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    // It's good practice to name the table and column definitions you
    // are getting - $table and $column don't cut it in more complex
    // modules
    $dynamicprop = $xartable['dynamic_properties'];

    $sql = "UPDATE $dynamicprop
            SET xar_prop_label = '" . xarVarPrepForStore($label) . "',
                xar_prop_type = " . xarVarPrepForStore($type);
    if (isset($default) && is_string($default)) {
        $sql .= ", xar_prop_default = '" . xarVarPrepForStore($default) . "'";
    }
// TODO: verify that the data source exists
    if (isset($source) && is_string($source)) {
        $sql .= ", xar_prop_source = '" . xarVarPrepForStore($source) . "'";
    }
    if (isset($validation) && is_string($validation)) {
        $sql .= ", xar_prop_validation = '" . xarVarPrepForStore($validation) . "'";
    }
// TODO: evaluate if we allow update those too
    if (isset($modid) && is_numeric($modid)) {
        $sql .= ", xar_prop_moduleid = " . xarVarPrepForStore($modid);
    }
    if (isset($itemtype) && is_numeric($itemtype)) {
        $sql .= ", xar_prop_itemtype = " . xarVarPrepForStore($itemtype);
    }
    if (isset($name) && is_string($name)) {
        $sql .= ", xar_prop_name = '" . xarVarPrepForStore($name) . "'";
    }
    if (isset($status) && is_numeric($status)) {
        $sql .= ", xar_prop_status = " . xarVarPrepForStore($status);
    }

    $sql .= " WHERE xar_prop_id = " . xarVarPrepForStore($prop_id);

    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so raise an
    // appropriate exception
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $sql);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    return true;
}

/**
 * delete a property field
 *
 * @author the DynamicData module development team
 * @param $args['prop_id'] property id of the item field to delete
// TODO: do we want those for security check ? Yes, but the original values...
 * @param $args['modid'] module id of the item field to delete
 * @param $args['itemtype'] item type of the item field to delete
 * @param $args['name'] name of the field to delete
 * @param $args['label'] label of the field to delete
 * @param $args['type'] type of the field to delete
 * @param $args['default'] default of the field to delete
 * @param $args['source'] data source of the field to delete
 * @param $args['validation'] validation of the field to delete
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_deleteprop($args)
{
    extract($args);

    // Required arguments
    $invalid = array();
    if (!isset($prop_id) || !is_numeric($prop_id)) {
        $invalid[] = 'property id';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'admin', 'deleteprop', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
// TODO: check based on other arguments too
    if (!xarSecAuthAction(0, 'DynamicData::Field', "::$prop_id", ACCESS_DELETE)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    // It's good practice to name the table and column definitions you
    // are getting - $table and $column don't cut it in more complex
    // modules
    $dynamicprop = $xartable['dynamic_properties'];

    $sql = "DELETE FROM $dynamicprop
            WHERE xar_prop_id = " . xarVarPrepForStore($prop_id);

    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so raise an
    // appropriate exception
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $sql);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

// TODO: don't delete if the data source is not in dynamic_data
    // delete all data too !
    $dynamicdata = $xartable['dynamic_data'];

    $sql = "DELETE FROM $dynamicdata
            WHERE xar_dd_propid = " . xarVarPrepForStore($prop_id);

    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so raise an
    // appropriate exception
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $sql);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    return true;
}

// ----------------------------------------------------------------------
// Hook functions (admin API)
// ----------------------------------------------------------------------

/**
 * create fields for an item - hook for ('item','create','API')
 * Needs $extrainfo['dd_*'] from arguments, or 'dd_*' from input
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_createhook($args)
{
    // we rely on the updatehook to do the real work here
    $args['dd_function'] = 'createhook';

    return xarModAPIFunc('dynamicdata','admin','updatehook',$args);
}


/**
 * update fields for an item - hook for ('item','update','API')
 * Needs $extrainfo['dd_*'] from arguments, or 'dd_*' from input
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_updatehook($args)
{
    extract($args);

    if (!isset($dd_function) || $dd_function != 'createhook') {
        $dd_function = 'updatehook';
    }

    if (!isset($objectid) || !is_numeric($objectid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'object id', 'admin', $dd_function, 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }
    if (!isset($extrainfo) || !is_array($extrainfo)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'extrainfo', 'admin', $dd_function, 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($extrainfo['module'])) {
        $modname = xarModGetName();
    } else {
        $modname = $extrainfo['module'];
    }

    $modid = xarModGetIDFromName($modname);
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'admin', $dd_function, 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    if (!empty($extrainfo['itemtype'])) {
        $itemtype = $extrainfo['itemtype'];
    } else {
        $itemtype = null;
    }

    if (!empty($extrainfo['itemid'])) {
        $itemid = $extrainfo['itemid'];
    } else {
        $itemid = $objectid;
    }
    if (empty($itemid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'item id', 'admin', $dd_function, 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    if (!xarModAPILoad('dynamicdata', 'user'))
    {
        $msg = xarML('Unable to load #(1) #(2) API',
                    'dynamicdata','user');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNABLE_TO_LOAD',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }
    $fields = xarModAPIFunc('dynamicdata','user','getprop',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype));
    if (!isset($fields) || $fields == false) {
        // nothing to see here, so we move on
        return $extrainfo;
    }

    // this fills $invalid with an array of errors, or fills $fields with the values
    $invalid = xarModAPIFunc('dynamicdata','admin','checkinput',
                             array('fields' => &$fields, // pass by reference !
                                   'dd_function' => $dd_function,
                                   'extrainfo' => $extrainfo));

    if (count($invalid) > 0) {
        $msg = join(' + ',$invalid);
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    if (!xarModAPIFunc('dynamicdata', 'admin', ($dd_function == 'createhook') ? 'create' : 'update',
                      array('modid'    => $modid,
                            'itemtype' => $itemtype,
                            'itemid'   => $itemid,
                            'fields'   => $fields))) {
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    // update the extrainfo array
    foreach ($fields as $name => $field) {
        $id = $field['id'];
        if (isset($fields[$name]['value'])) {
            $extrainfo['dd_'.$id] = $fields[$name]['value'];
        } elseif (isset($extrainfo['dd_'.$id])) {
            unset($extrainfo['dd_'.$id]);
        }
    }

    // Return the extra info
    return $extrainfo;
}


/**
 * delete fields for an item - hook for ('item','delete','API')
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_deletehook($args)
{
    extract($args);

    if (!isset($objectid) || !is_numeric($objectid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'object id', 'admin', 'createhook', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }
    if (!isset($extrainfo) || !is_array($extrainfo)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'extrainfo', 'admin', 'createhook', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($extrainfo['module'])) {
        $modname = xarModGetName();
    } else {
        $modname = $extrainfo['module'];
    }

    $modid = xarModGetIDFromName($modname);
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'admin', 'createhook', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    if (!empty($extrainfo['itemtype'])) {
        $itemtype = $extrainfo['itemtype'];
    } else {
        $itemtype = null;
    }

    if (!empty($extrainfo['itemid'])) {
        $itemid = $extrainfo['itemid'];
    } else {
        $itemid = $objectid;
    }
    if (empty($itemid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'admin', 'deletehook', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    if (!xarModAPIFunc('dynamicdata', 'admin', 'delete',
                      array('modid'    => $modid,
                            'itemtype' => $itemtype,
                            'itemid'   => $itemid))) {
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    // Return the extra info
    return $extrainfo;
}

/**
 * update configuration for a module - hook for ('module','updateconfig','API')
 * Needs $extrainfo['dd_*'] from arguments, or 'dd_*' from input
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_updateconfighook($args)
{
    if (!isset($args['extrainfo'])) {
        $args['extrainfo'] = array();
    }
    // Return the extra info
    return $args['extrainfo'];

    /*
     * currently NOT used (we're going through the 'normal' updateconfig for now)
     */

}

/**
 * delete all dynamicdata fields for a module - hook for ('module','remove','API')
 *
 * @param $args['objectid'] ID of the object (must be the module name here !!)
 * @param $args['extrainfo'] extra information
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_removehook($args)
{
    extract($args);

    if (!isset($extrainfo)) {
        $extrainfo = array();
    }

    // When called via hooks, we should get the real module name from objectid
    // here, because the current module is probably going to be 'modules' !!!
    if (!isset($objectid) || !is_string($objectid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'object ID (= module name)', 'admin', 'removehook', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    $modid = xarModGetIDFromName($objectid);
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module ID', 'admin', 'removehook', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    if (!xarSecAuthAction(0, "DynamicData::Item", "$modid::", ACCESS_DELETE)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    // Get database setup
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $dynamicprop = $xartable['dynamic_properties'];

    $sql = "SELECT xar_prop_id
            FROM $dynamicprop
            WHERE xar_prop_moduleid = " . xarVarPrepForStore($modid);

    $result = $dbconn->Execute($sql);

    if ($dbconn->ErrorNo() != 0) {
        $msg = xarML('Database error for #(1) function #(2)() in module #(3)',
                    'admin', 'removehook', 'dynamicdata');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }
    $ids = array();
    while (!$result->EOF) {
        list($id) = $result->fields;
        $result->MoveNext();
        $ids[] = $id;
    }
    $result->Close;

    if (count($ids) == 0) {
        return $extrainfo;
    }

    $dynamicdata = $xartable['dynamic_data'];

// TODO: don't delete if the data source is not in dynamic_data
    // Delete the item fields
    $sql = "DELETE FROM $dynamicdata
            WHERE xar_dd_propid IN (" . join(', ',$ids) . ")";
    $dbconn->Execute($sql);

    if ($dbconn->ErrorNo() != 0) {
        $msg = xarML('Database error for #(1) function #(2)() in module #(3)',
                    'admin', 'removehook', 'dynamicdata');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    // Delete the properties
    $sql = "DELETE FROM $dynamicprop
            WHERE xar_prop_id IN (" . join(', ',$ids) . ")";
    $dbconn->Execute($sql);

    if ($dbconn->ErrorNo() != 0) {
        $msg = xarML('Database error for #(1) function #(2)() in module #(3)',
                    'admin', 'removehook', 'dynamicdata');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    // Return the extra info
    return $extrainfo;
}

// ----------------------------------------------------------------------
// Input validation (to be extended with xarVarValidate stuff)
// ----------------------------------------------------------------------

/**
 * check input from dynamic data (needs $extrainfo['dd_*'] from arguments, or 'dd_*' from input)
 *
 * @param &$fields fields array (pass by reference here !)
 * @param $dd_function optional name of the calling function
 * @param $extrainfo optional extra information (from hooks)
 * @returns array
 * @return array of invalid fields
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_checkinput($args)
{
// don't use extract here - we want to pass the updated fields back
//    extract($args);

    $invalid = array();
    if (empty($args['fields'])) {
        $invalid[] = xarML('Invalid #(1) for dynamic field #(2) in function #(3)() of module #(4)',
                           'fields argument', '*', $dd_function, 'dynamicdata');
        return $invalid;
    }

    // pass by reference
    $fields = &$args['fields'];

    if (empty($args['dd_function'])) {
        $dd_function = 'checkinput';
    } else {
        $dd_function = $args['dd_function'];
    }

    if (empty($args['extrainfo'])) {
        $extrainfo = array();
    } else {
        $extrainfo = $args['extrainfo'];
    }

// TODO: replace with something else
    $proptypes = xarModAPIFunc('dynamicdata','user','getproptypes');

    foreach ($fields as $name => $field) {
        // $values still uses property id instead of label, for create/update/delete in database
        $id = $field['id'];
        if (empty($proptypes[$field['type']]['name'])) {
            $fields[$name]['invalid'] = 'property type '.xarVarPrepForDisplay($field['type']);
            $invalid[] = xarML('Invalid #(1) for dynamic field #(2) in function #(3)() of module #(4)',
                               $fields[$name]['invalid'], xarVarPrepForDisplay($name), $dd_function, 'dynamicdata');
            continue;
        }

    // TODO: allow field label (sanitized !) here too ?
        if (isset($extrainfo['dd_'.$id])) {
            $value = $extrainfo['dd_'.$id];
        } else {
            $value = xarVarCleanFromInput('dd_'.$id);
        }

        $fields[$name]['invalid'] = '';
        $fields[$name]['value'] = null;

// TODO: add some real property validation here !!!
        $typename = $proptypes[$field['type']]['name'];
        switch ($typename) {
            case 'text':
            case 'textbox':
                if (!empty($value)) {
            // TODO: check size etc.
                }
                $fields[$name]['value'] = $value;
                break;
            case 'textarea':
            case 'textarea_small':
            case 'textarea_medium':
            case 'textarea_large':
                if (!empty($value)) {
            // TODO: check size etc.
                }
                $fields[$name]['value'] = $value;
                break;
        // TEST ONLY
            case 'webpage':
                if (!isset($options) || !is_array($options)) {
                    $options = array();
                    $basedir = $field['validation'];
                    $filetype = 'html?';
                    $files = xarModAPIFunc('dynamicdata','admin','browse',
                                           array('basedir' => $basedir,
                                                 'filetype' => $filetype));
                    natsort($files);
                    array_unshift($files,'');
                    foreach ($files as $file) {
                        $options[] = array('id' => $file,
                                           'name' => $file);
                    }
                    unset($files);
                }
                // fall through to the next one
            case 'status':
                if (!isset($options) || !is_array($options)) {
                    $options = array(
                                     array('id' => 0, 'name' => xarML('Submitted')),
                                     array('id' => 1, 'name' => xarML('Rejected')),
                                     array('id' => 2, 'name' => xarML('Approved')),
                                     array('id' => 3, 'name' => xarML('Front Page')),
                               );
                }
                if (empty($value)) {
                    $value = 0;
                }
                // fall through to the next one
            case 'select':
            case 'dropdown':
            case 'listbox':
            // TODO: handle multiple select
                if (!isset($selected)) {
                    if (!empty($value)) {
                        $selected = $value;
                    } else {
                        $selected = '';
                    }
                }
            // TODO: get options from somewhere :)
                if (!isset($options) || !is_array($options)) {
                    $options = array();
                }
                $found = 0;
                foreach ($options as $option) {
                    if ($option['id'] == $selected) {
                        $found = 1;
                        $fields[$name]['value'] = $option['id'];
                        break;
                    }
                }
                if (empty($found)) {
                    $fields[$name]['invalid'] = 'selection';
                }
                break;
            case 'file':
            case 'fileupload':
// TODO: replace with function from uploads module
                if (!empty($value)) {
                // FIXME : xarVarCleanFromInput() with magic_quotes_gpc On clashes with
                //         the tmp_name assigned by PHP on Windows !!!
                    global $HTTP_POST_FILES;
                    $file = $HTTP_POST_FILES['dd_'.$id];
                    // is_uploaded_file() : PHP 4 >= 4.0.3
                    if (is_uploaded_file($file['tmp_name']) && $file['size'] < 1000000) {
                        $fields[$name]['value'] = join('', @file($file['tmp_name']));
                    } else {
                        $fields[$name]['invalid'] = 'file upload';
                    }
                } else {
                    $fields[$name]['value'] = '';
                }
                break;
            case 'url':
                if (!empty($value)) {
            // TODO: add some URL validation routine !
                    if (preg_match('/[<>"]/',$value)) {
                        $fields[$name]['value'] = '';
                        $fields[$name]['invalid'] = 'image URL';
                    } else {
                        $fields[$name]['value'] = $value;
                    }
                } else {
                    $fields[$name]['value'] = '';
                }
                break;
            case 'image':
                if (!empty($value)) {
            // TODO: add some image validation routine !
                    if (preg_match('/[<>"]/',$value)) {
                        $fields[$name]['value'] = '';
                        $fields[$name]['invalid'] = 'image URL';
                    } else {
                        $fields[$name]['value'] = $value;
                    }
                } else {
                    $fields[$name]['value'] = '';
                }
                break;
            case 'static':
                // TODO: check if we can leave this "as is"
                //    $fields[$name]['value'] = $value;
                break;
            case 'hidden':
                // TODO: check if we can leave this "as is"
                //    $fields[$name]['value'] = $value;
                break;
            case 'username':
                // default user is the current one
                if (empty($value)) {
                    $value = xarUserGetVar('uid');
                }
                // check that the user exists
                if (is_numeric($value)) {
                    $user = xarUserGetVar('uname', $value);
                }
                if (!is_numeric($value) || empty($user)) {
                    $fields[$name]['invalid'] = 'user';
                } else {
                    $fields[$name]['value'] = $value;
                }
                break;
            case 'date':
            case 'calendar':
                // default time is now
                if (empty($value)) {
                    $fields[$name]['value'] = time();
                } elseif (is_numeric($value)) {
                    $fields[$name]['value'] = $value;
                } elseif (is_array($value) && !empty($value['year'])) {
                    if (!isset($value['sec'])) {
                        $value['sec'] = 0;
                    }
                    $fields[$name]['value'] = mktime($value['hour'],$value['min'],$value['sec'],
                                                      $value['mon'],$value['mday'],$value['year']);
                } else {
                    $fields[$name]['invalid'] = 'date';
                }
                break;


            case 'checkbox':
            // TODO: allow different values here, and verify $checked ?
                if (empty($value)) {
                    $fields[$name]['value'] = 0;
                } else {
                    $fields[$name]['value'] = 1;
                }
                break;
            case 'integerbox':
                if (empty($value)) {
                    $fields[$name]['value'] = 0;
                } elseif (is_numeric($value)) {
                    $fields[$name]['value'] = intval($value);
                } else {
                    $fields[$name]['invalid'] = 'integer';
                }
                break;
            case 'integerlist':
            // TODO: handle multiple select
                if (!isset($selected)) {
                    if (!empty($value)) {
                        $selected = $value;
                    } else {
                        $selected = '';
                    }
                }
            // TODO: get options from somewhere :)
                if (!isset($options) || !is_array($options)) {
                    $options = array();
                // TODO: specify how to give a range of numbers
                    if (isset($min) && isset($max)) {
                        for ($i = $min; $i <= $max; $i++) {
                            $options[] = array('id' => $i, 'name' => $i);
                        }
                    }
                }
                $found = 0;
                foreach ($options as $option) {
                    if ($option['id'] == $selected) {
                        $found = 1;
                        $fields[$name]['value'] = $option['id'];
                        break;
                    }
                }
                if (empty($found)) {
                    $fields[$name]['invalid'] = 'integer selection';
                }
            case 'floatbox':
                if (empty($value)) {
                    $fields[$name]['value'] = 0;
                } elseif (is_numeric($value)) {
                    $fields[$name]['value'] = floatval($value);
                } else {
                    $fields[$name]['invalid'] = 'float';
                }
                break;
            case 'module':
                if (empty($value)) {
                    $fields[$name]['value'] = 0;
                } elseif (is_numeric($value)) {
                    $modinfo = xarModGetInfo($value);
                    if (empty($modinfo['name'])) {
                        $fields[$name]['invalid'] = 'module id';
                    } else {
                        $fields[$name]['value'] = $value;
                    }
                } else {
                    $modid = xarModGetIDFromName($value);
                    if (empty($modid)) {
                        $fields[$name]['invalid'] = 'module name';
                    } else {
                        $fields[$name]['value'] = $value; // TODO: keep as module name here ?
                    }
                }
                break;
            case 'itemtype':
                if (empty($value)) {
                    $fields[$name]['value'] = 0;
                } elseif (is_numeric($value)) {
                    $fields[$name]['value'] = intval($value);
                } else {
                    $fields[$name]['invalid'] = 'item type';
                }
                break;
            case 'itemid':
                if (empty($value)) {
                    // this one is passed separately !
                    $value = xarVarCleanFromInput('itemid');
                    $fields[$name]['value'] = $value;
                } elseif (is_numeric($value)) {
                    $fields[$name]['value'] = intval($value);
                } else {
                    $fields[$name]['invalid'] = 'item id';
                }
                break;
            case 'fieldtype':
                if (!empty($proptypes[$value]['name'])) {
                    $fields[$name]['value'] = $value;
                } else {
                    $fields[$name]['invalid'] = 'property type';
                }
                break;
            case 'datasource':
                $sources = xarModAPIFunc('dynamicdata','user','getsources');
                $found = 0;
                foreach ($sources as $source) {
                    if ($source == $value) {
                        $fields[$name]['value'] = $value;
                        $found = 1;
                        break;
                    }
                }
                if (empty($found)) {
                    $fields[$name]['invalid'] = 'data source';
                }
                break;
            case 'object':
                $objects = xarModAPIFunc('dynamicdata','user','getobjects');
                if (!empty($objects[$value])) {
                    $fields[$name]['value'] = $value;
                } else {
                    $fields[$name]['invalid'] = 'object';
                }
                break;
            case 'fieldstatus':
                if (!isset($options) || !is_array($options)) {
                    $options = array(
                                     array('id' => 0, 'name' => xarML('Disabled')),
                                     array('id' => 1, 'name' => xarML('Active')),
                                     array('id' => 2, 'name' => xarML('Display Only')),
                               );
                }
                if (!isset($value)) {
                    $value = 1;
                }
                $found = 0;
                foreach ($options as $option) {
                    if ($option['id'] == $value) {
                        $found = 1;
                        $fields[$name]['value'] = $option['id'];
                        break;
                    }
                }
                if (empty($found)) {
                    $fields[$name]['invalid'] = 'field status';
                }
                break;

// TODO: add categories, hitcount, ratings, ...

            default:
                $fields[$name]['invalid'] = 'property type '.xarVarPrepForDisplay($typename);
                break;
        }
        if (!empty($fields[$name]['invalid'])) {
            $invalid[] = xarML('Invalid #(1) for dynamic field #(2) in function #(3)() of module #(4)',
                               $fields[$name]['invalid'], xarVarPrepForDisplay($name), $dd_function, 'dynamicdata');
        }
    }

    return $invalid;
}

// ----------------------------------------------------------------------
// BL admin tags (input, form & list)
// ----------------------------------------------------------------------

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * Handle <xar:data-input ...> form field tags
 * Format : <xar:data-input field="$field" /> with $definition an array
 *                                             containing the type, label, value, ...
 *       or <xar:data-input label="thisname" type="thattype" value="$val" ... />
 * 
 * @param $args array containing the input field definition or the type, label, value, ...
 * @returns string
 * @return the PHP code needed to invoke showinput() in the BL template
 */
function dynamicdata_adminapi_handleInputTag($args)
{
    $out = "xarModAPILoad('dynamicdata','admin');
echo xarModAPIFunc('dynamicdata',
                   'admin',
                   'showinput',\n";
    if (isset($args['definition'])) {
        $out .= '                   '.$args['definition']."\n";
        $out .= '                  );';
    } elseif (isset($args['field'])) {
        $out .= '                   '.$args['field']."\n";
        $out .= '                  );';
    } else {
        $out .= "                   array(\n";
        foreach ($args as $key => $val) {
            if (is_numeric($val) || substr($val,0,1) == '$') {
                $out .= "                         '$key' => $val,\n";
            } else {
                $out .= "                         '$key' => '$val',\n";
            }
        }
        $out .= "                         ));";
    }
    return $out;
}

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * show some predefined form input field in a template
 * 
 * @param $args array containing the definition of the field (type, name, value, ...)
 * @returns string
 * @return string containing the HTML (or other) text to output in the BL template
 */
function dynamicdata_adminapi_showinput($args)
{
    extract($args);
    if (empty($name) && !empty($label)) {
        $name = strtolower($label);
    }
    if (empty($name)) {
        return xarML('Missing \'name\' or \'label\' attribute in tag parameters or field definition');
    }
    if (!isset($type)) {
        $type = 1;
    }
    if (!isset($value)) {
        $value = '';
    }
    if (!isset($id)) {
        $id = '';
    } else {
        $id = ' id="'.$id.'"';
    }
    if (!isset($tabindex)) {
        $tabindex = '';
    } else {
        $tabindex = ' tabindex="'.$tabindex.'"';
    }

    if (!xarModAPILoad('dynamicdata','user')) return;

// TODO: replace with something else
    $proptypes = xarModAPIFunc('dynamicdata','user','getproptypes');
    if (is_numeric($type)) {
        if (!empty($proptypes[$type]['name'])) {
            $typename = $proptypes[$type]['name'];
        } else {
            return xarML('Unknown property type #(1)',$type);
        }
    } else {
        $typename = $type;
    }

    $output = '';
    switch ($typename) {
        case 'text':
        case 'textbox':
            if (empty($size)) {
                $size = 50;
            }
            $value = xarVarPrepForDisplay($value);
            $output .= '<input type="text" name="'.$name.'" value="'.$value.'" size="'.$size.'"'.$id.$tabindex.' />';
            break;
        case 'textarea':
        case 'textarea_small':
        case 'textarea_medium':
        case 'textarea_large':
            if (empty($wrap)) {
                $wrap = 'soft';
            }
            if (empty($cols)) {
                $cols = 50;
            }
            if (empty($rows)) {
                if ($typename == 'textarea_small') {
                    $rows = 2;
                } elseif ($typename == 'textarea_large') {
                    $rows = 20;
                } else {
                    $rows = 8;
                }
            }
            $value = xarVarPrepForDisplay($value);
            $output .= '<textarea name="'.$name.'" wrap="'.$wrap.'" rows="'.$rows.'" cols="'.$cols.'"'.$id.$tabindex.'>'.$value.'</textarea>';
            break;
    // TEST ONLY
        case 'webpage':
            if (!isset($options) || !is_array($options)) {
                $options = array();
                $basedir = 'd:/backup/mikespub/pictures';
                $filetype = 'html?';
                $files = xarModAPIFunc('dynamicdata','admin','browse',
                                       array('basedir' => $basedir,
                                             'filetype' => $filetype));
                natsort($files);
                array_unshift($files,'');
                foreach ($files as $file) {
                    $options[] = array('id' => $file,
                                       'name' => $file);
                }
                unset($files);
            }
            // fall through to the next one
        case 'status':
            if (!isset($options) || !is_array($options)) {
                $options = array(
                                 array('id' => 0, 'name' => xarML('Submitted')),
                                 array('id' => 1, 'name' => xarML('Rejected')),
                                 array('id' => 2, 'name' => xarML('Approved')),
                                 array('id' => 3, 'name' => xarML('Front Page')),
                           );
            }
            if (empty($value)) {
                $value = 0;
            }
            // fall through to the next one
        case 'select':
        case 'dropdown':
        case 'listbox':
            if (!isset($multiple)) {
                $multiple = '';
            } else {
                $multiple = ' multiple';
            }
            $output .= '<select name="'.$name.'"'.$id.$tabindex.$multiple.'>';
            if (!isset($selected)) {
                if (!empty($value)) {
                    $selected = $value;
                } else {
                    $selected = '';
                }
            }
            if (!isset($options) || !is_array($options)) {
                $options = array();
            }
            foreach ($options as $option) {
                $output .= '<option value="'.$option['id'].'"';
                if ($option['id'] == $selected) {
                    $output .= ' selected';
                }
                $output .= '>'.$option['name'].'</option>';
            }
            $output .= '</select>';
            break;
        case 'file':
        case 'fileupload':
            if (empty($maxsize)) {
                $maxsize = 1000000;
            }
            $output .= '<input type="hidden" name="MAX_FILE_SIZE" value="'.$maxsize.'" />';
            if (empty($size)) {
                $size = 40;
            }
            $output .= '<input type="file" name="'.$name.'" size="'.$size.'"'.$id.$tabindex.' />';
            break;
        case 'url':
            if (empty($size)) {
                $size = 50;
            }
            $value = xarVarPrepForDisplay($value);
            $output .= '<input type="text" name="'.$name.'" value="'.$value.'" size="'.$size.'"'.$id.$tabindex.' />';
            if (!empty($value)) {
                $output .= ' [ <a href="'.$value.'" target="preview">'.xarML('check').'</a> ]';
            }
            break;
        case 'image':
            if (empty($size)) {
                $size = 50;
            }
            $value = xarVarPrepForDisplay($value);
            $output .= '<input type="text" name="'.$name.'" value="'.$value.'" size="'.$size.'"'.$id.$tabindex.' />';
            if (!empty($value)) {
                $output .= ' [ <a href="'.$value.'" target="preview">'.xarML('show').'</a> ]';
            }
            $output .= '<br />// TODO: add image picker ?';
            break;
        case 'static':
            $value = xarVarPrepHTMLDisplay($value);
            $output .= $value;
            break;
        case 'hidden':
            $value = xarVarPrepForDisplay($value);
            $output .= '<input type="hidden" name="'.$name.'" value="'.$value.'"'.$id.$tabindex.' />';
            break;
        case 'username':
            if (empty($value)) {
                $value = xarUserGetVar('uid');
            }
            $user = xarUserGetVar('name', $value);
            if (empty($user)) {
                $user = xarUserGetVar('uname', $value);
            }
            $output .= $user;
            if ($value > 1) {
                $output .= ' [ <a href="'.xarModURL('users','user','display',
                                                    array('uid' => $value))
                           . '" target="preview">'.xarML('profile').'</a> ]';
            }
            break;
        case 'date':
        case 'calendar':
            if (empty($value)) {
                $value = time();
            }
        // TODO: adapt to local/user time !
            $output .= strftime('%a, %d %B %Y %H:%M:%S %Z', $value);
            $output .= '<br />';
            $localtime = localtime($value,1);
            $output .= xarML('Date') . ' <select name="'.$name.'[year]"'.$id.$tabindex.'>';
            if (empty($minyear)) {
                $minyear = $localtime['tm_year'] + 1900 - 2;
            }
            if (empty($maxyear)) {
                $maxyear = $localtime['tm_year'] + 1900 + 2;
            }
            for ($i = $minyear; $i <= $maxyear; $i++) {
                if ($i == $localtime['tm_year'] + 1900) {
                    $output .= '<option selected>' . $i;
                } else {
                    $output .= '<option>' . $i;
                }
            }
            $output .= '</select> - <select name="'.$name.'[mon]">';
            for ($i = 1; $i <= 12; $i++) {
                if ($i == $localtime['tm_mon'] + 1) {
                    $output .= '<option selected>' . $i;
                } else {
                    $output .= '<option>' . $i;
                }
            }
            $output .= '</select> - <select name="'.$name.'[mday]">';
            for ($i = 1; $i <= 31; $i++) {
                if ($i == $localtime['tm_mday']) {
                    $output .= '<option selected>' . $i;
                } else {
                    $output .= '<option>' . $i;
                }
            }
            $output .= '</select> ';
            $output .= xarML('Time') . ' <select name="'.$name.'[hour]">';
            for ($i = 0; $i < 24; $i++) {
                if ($i == $localtime['tm_hour']) {
                    $output .= '<option selected>' . sprintf("%02d",$i);
                } else {
                    $output .= '<option>' . sprintf("%02d",$i);
                }
            }
            $output .= '</select> : <select name="'.$name.'[min]">';
            for ($i = 0; $i < 60; $i++) {
                if ($i == $localtime['tm_min']) {
                    $output .= '<option selected>' . sprintf("%02d",$i);
                } else {
                    $output .= '<option>' . sprintf("%02d",$i);
                }
            }
            $output .= '</select> : <select name="'.$name.'[sec]">';
            for ($i = 0; $i < 60; $i++) {
                if ($i == $localtime['tm_sec']) {
                    $output .= '<option selected>' . sprintf("%02d",$i);
                } else {
                    $output .= '<option>' . sprintf("%02d",$i);
                }
            }
            $output .= '</select> ';
            break;
        case 'checkbox':
        // TODO: allow different values here, and verify $checked ?
            $output .= '<input type="checkbox" name="'.$name.'" value="1"'.$id.$tabindex;
            if (!empty($value)) {
                $output .= ' checked';
            }
            $output .= ' />';
            break;
        case 'integerbox':
            if (empty($size)) {
                $size = 10;
            }
            $value = xarVarPrepForDisplay($value);
            $output .= '<input type="text" name="'.$name.'" value="'.$value.'" size="'.$size.'"'.$id.$tabindex.' />';
            break;
        case 'integerlist':
            if (!isset($selected)) {
                if (!empty($value)) {
                    $selected = $value;
                } else {
                    $selected = '';
                }
            }
            if (!isset($options) || !is_array($options)) {
                $options = array();
            // TODO: specify how to give a range of numbers
                if (isset($min) && isset($max)) {
                    for ($i = $min; $i <= $max; $i++) {
                        $options[] = array('id' => $i, 'name' => $i);
                    }
                }
            }
            $output .= '<select name="'.$name.'"'.$id.$tabindex.'>';
            foreach ($options as $option) {
                $output .= '<option value="'.$option['id'].'"';
                if ($option['id'] == $selected) {
                    $output .= ' selected';
                }
                $output .= '>'.$option['name'].'</option>';
            }
            $output .= '</select>';
            break;
        case 'floatbox':
            if (empty($size)) {
                $size = 10;
            }
            $value = xarVarPrepForDisplay($value);
            $output .= '<input type="text" name="'.$name.'" value="'.$value.'" size="'.$size.'"'.$id.$tabindex.' />';
            break;
        case 'module':
        // TODO: evaluate if we want some other output here
            //$output .= $value;
            $modlist = xarModGetList();
            $output .= '<select name="'.$name.'"'.$id.$tabindex.'>';
            foreach ($modlist as $modinfo) {
                if ($value == $modinfo['regid']) {
                    $output .= '<option value="'.$modinfo['regid'].'" selected>'.$modinfo['name'].'</option>';
                } else {
                    $output .= '<option value="'.$modinfo['regid'].'">'.$modinfo['name'].'</option>';
                }
            }
            $output .= '</select>';
            break;
        case 'itemtype':
        // TODO: evaluate if we want some other output here
            //$output .= $value;
            if (empty($size)) {
                $size = 10;
            }
            $value = xarVarPrepForDisplay($value);
            $output .= '<input type="text" name="'.$name.'" value="'.$value.'" size="'.$size.'"'.$id.$tabindex.' />';
            break;
        case 'itemid':
        // TODO: evaluate if we want some other output here
            $value = xarVarPrepForDisplay($value);
            $output .= $value;
            break;
        case 'fieldtype':
            $output .= '<select name="'.$name.'"'.$id.$tabindex.'>';
            foreach ($proptypes as $propid => $proptype) {
                $output .= '<option value="'.$propid.'"';
                if ($propid == $value) {
                    $output .= ' selected';
                }
                $output .= '>'.$proptype['label'].'</option>';
            }
            $output .= '</select>';
            break;
        case 'datasource':
            $output .= '<select name="'.$name.'"'.$id.$tabindex.'>';
            $sources = xarModAPIFunc('dynamicdata','user','getsources');
            foreach ($sources as $source) {
                if ($source == $value) {
                    $output .= '<option selected>'.$source.'</option>';
                } else {
                    $output .= '<option>'.$source.'</option>';
                }
            }
            $output .= '</select>';
            break;
        case 'object':
            $output .= '<select name="'.$name.'"'.$id.$tabindex.'>';
            $objects = xarModAPIFunc('dynamicdata','user','getobjects');
            foreach ($objects as $objectid => $object) {
                if ($objectid == $value) {
                    $output .= '<option value="'.$objectid.'" selected>';
                } else {
                    $output .= '<option value="'.$objectid.'">';
                }
                $output .= $object['fields']['name']['value'] . '</option>';
            }
            $output .= '</select>';
            break;
        case 'fieldstatus':
            $output .= '<select name="'.$name.'"'.$id.$tabindex.'>';
            if (!isset($options) || !is_array($options)) {
                $options = array(
                                 array('id' => 0, 'name' => xarML('Disabled')),
                                 array('id' => 1, 'name' => xarML('Active')),
                                 array('id' => 2, 'name' => xarML('Display Only')),
                           );
            }
            foreach ($options as $option) {
                $output .= '<option value="'.$option['id'].'"';
                if ($option['id'] == $value) {
                    $output .= ' selected';
                }
                $output .= '>'.$option['name'].'</option>';
            }
            $output .= '</select>';
            break;

    // input for some common hook/utility modules
        case 'categories':
            $output .= '// TODO: get categories select lists for this item';
            break;
        case 'comments':
            $output .= xarML('Not available on input');
            break;
        case 'numcomments':
            // via comments_userapi_get_count()
            $output .= $value;
            break;
        case 'hitcount':
            if (!empty($value)) {
                $output .= $value;
/* value retrieved in getall now
            } elseif (empty($modname) || empty($itemid)) {
                $output .= xarML('Please provide "modname" and "itemid" as parameters in the data-input tag');
            } elseif (xarModAPILoad('hitcount','user')) {
                $value = xarModAPIFunc('hitcount','user','get',
                                       array('modname' => $modname,
                                             'itemid' => $itemid));
                $output .= $value;
*/
            } else {
                $output .= xarML('The hitcount module is currently unavailable');
            }
            break;
        case 'ratings':
            if (!empty($value)) {
                $output .= $value;
/* value retrieved in getall now
            } elseif (empty($modname) || empty($itemid)) {
                $output .= xarML('Please provide "modname" and "itemid" as parameters in the data-input tag');
            } elseif (xarModAPILoad('ratings','user')) {
                $value = xarModAPIFunc('ratings','user','get',
                                          array('modname' => $modname,
                                                'itemid' => $itemid,
                                                'objectid' => $itemid));
                $output .= $value;
*/
            } else {
                $output .= xarML('The ratings module is currently unavailable');
            }
            break;

        default:
            $output .= xarML('Unknown type #(1)',xarVarPrepForDisplay($typename));
            break;
    }
    return $output;
}

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * Handle <xar:data-form ...> form tags
 * Format : <xar:data-form module="123" itemtype="0" itemid="555" fieldlist="$fieldlist" static="yes" ... />
 *       or <xar:data-form fields="$fields" ... />
 * 
 * @param $args array containing the item for which you want to show a form, or fields
 * @returns string
 * @return the PHP code needed to invoke showform() in the BL template
 */
function dynamicdata_adminapi_handleFormTag($args)
{
    $out = "xarModAPILoad('dynamicdata','admin');
echo xarModAPIFunc('dynamicdata',
                   'admin',
                   'showform',\n";
    if (isset($args['definition'])) {
        $out .= '                   '.$args['definition']."\n";
        $out .= '                  );';
    } else {
        $out .= "                   array(\n";
        foreach ($args as $key => $val) {
            if (is_numeric($val) || substr($val,0,1) == '$') {
                $out .= "                         '$key' => $val,\n";
            } else {
                $out .= "                         '$key' => '$val',\n";
            }
        }
        $out .= "                         ));";
    }
    return $out;
}

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * show an input form in a template
 * 
 * @param $args array containing the item or fields to show
 * @returns string
 * @return string containing the HTML (or other) text to output in the BL template
 */
function dynamicdata_adminapi_showform($args)
{
    extract($args);

    // optional layout for the template
    if (empty($layout)) {
        $layout = 'default';
    }
    // or optional template, if you want e.g. to handle individual fields
    // differently for a specific module / item type
    if (empty($template)) {
        $template = '';
    }

    // we got everything via template parameters
    if (isset($fields) && is_array($fields) && count($fields) > 0) {
        return xarTplModule('dynamicdata','admin','showform',
                            array('fields' => $fields,
                                  'layout' => $layout),
                            $template);
    }

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($module)) {
        $modname = xarModGetName();
    } else {
        $modname = $module;
    }

    if (is_numeric($modname)) {
        $modid = $modname;
        $modinfo = xarModGetInfo($modid);
        $modname = $modinfo['name'];
    } else {
        $modid = xarModGetIDFromName($modname);
    }
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'admin', 'showform', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    if (empty($itemtype) || !is_numeric($itemtype)) {
        $itemtype = null;
    }

    // try getting the item id via input variables if necessary
    if (!isset($itemid) || !is_numeric($itemid)) {
        $itemid = xarVarCleanFromInput('itemid');
    }

    // check the optional field list
    if (!empty($fieldlist)) {
        // support comma-separated field list
        if (is_string($fieldlist)) {
            $myfieldlist = explode(',',$fieldlist);
        // and array of fields
        } elseif (is_array($fieldlist)) {
            $myfieldlist = $fieldlist;
        }
    } else {
        $myfieldlist = null;
    }

    // include the static properties (= module tables) too ?
    if (empty($static)) {
        $static = false;
    }

    if (!xarModAPILoad('dynamicdata','user')) return;

    if (empty($itemid)) {
        // throw an exception if you can't add an item here
        if (!xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:", ACCESS_ADD)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
            return;
        }
        // we're probably dealing with a new item (no itemid yet), so
        // retrieve the properties only
        $fields = xarModAPIFunc('dynamicdata','user','getprop',
                                array('modid' => $modid,
                                      'itemtype' => $itemtype,
                                      'fieldlist' => $myfieldlist,
                                      'static' => $static));
        if (!isset($fields) || $fields == false || count($fields) == 0) {
            return '';
        }

        // prefill the values with defaults (if any)
        foreach (array_keys($fields) as $name) {
            $fields[$name]['value'] = $fields[$name]['default'];
        }

    } else {
        // throw an exception if you can't edit this
        if (!xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:$itemid", ACCESS_EDIT)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
            return;
        }
        // we're dealing with a real item, so retrieve the property values
        $fields = xarModAPIFunc('dynamicdata','user','getitem',
                                array('modid' => $modid,
                                      'itemtype' => $itemtype,
                                      'itemid' => $itemid,
                                      'fieldlist' => $myfieldlist,
                                      'static' => $static));
        if (!isset($fields) || $fields == false || count($fields) == 0) {
            return '';
        }
    }

    // if we are in preview mode, we need to check for any preview values
    if (!isset($preview)) {
        $preview = xarVarCleanFromInput('preview');
    }
    if (!empty($preview)) {
        foreach ($fields as $name => $field) {
            $id = $field['id'];
            $value = xarVarCleanFromInput('dd_'.$id);
            if (isset($value)) {
                $fields[$name]['value'] = $value;
            }
        }
    }

    return xarTplModule('dynamicdata','admin','showform',
                        array('fields' => $fields,
                              'layout' => $layout),
                        $template);
}

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * Handle <xar:data-list ...> list tags
 * Format : <xar:data-list module="123" itemtype="0" itemids="$idlist" fieldlist="$fieldlist" static="yes" .../>
 *       or <xar:data-list items="$items" labels="$labels" ... />
 * 
 * @param $args array containing the items that you want to list, or fields
 * @returns string
 * @return the PHP code needed to invoke showlist() in the BL template
 */
function dynamicdata_adminapi_handleListTag($args)
{
    $out = "xarModAPILoad('dynamicdata','admin');
echo xarModAPIFunc('dynamicdata',
                   'admin',
                   'showlist',\n";
    if (isset($args['definition'])) {
        $out .= '                   '.$args['definition']."\n";
        $out .= '                  );';
    } else {
        $out .= "                   array(\n";
        foreach ($args as $key => $val) {
            if (is_numeric($val) || substr($val,0,1) == '$') {
                $out .= "                         '$key' => $val,\n";
            } else {
                $out .= "                         '$key' => '$val',\n";
            }
        }
        $out .= "                         ));";
    }
    return $out;
}

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * list some items in a template
 * 
 * @param $args array containing the items or fields to show
 * @returns string
 * @return string containing the HTML (or other) text to output in the BL template
 */
function dynamicdata_adminapi_showlist($args)
{
    extract($args);

    // optional layout for the template
    if (empty($layout)) {
        $layout = 'default';
    }
    // or optional template, if you want e.g. to handle individual fields
    // differently for a specific module / item type
    if (empty($template)) {
        $template = '';
    }

    // we got everything via template parameters
    if (isset($items) && is_array($items)) {
        return xarTplModule('dynamicdata','admin','showlist',
                            array('items' => $items,
                                  'labels' => $labels,
                                  'layout' => $layout),
                            $template);
    }

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($module)) {
        $modname = xarModGetName();
    } else {
        $modname = $module;
    }

    if (is_numeric($modname)) {
        $modid = $modname;
        $modinfo = xarModGetInfo($modid);
        $modname = $modinfo['name'];
    } else {
        $modid = xarModGetIDFromName($modname);
    }
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'admin', 'showlist', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    if (empty($itemtype) || !is_numeric($itemtype)) {
        $itemtype = null;
    }

// TODO: what kind of security checks do we want/need here ?
    // don't bother if you can't edit anything anyway
    if (!xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:", ACCESS_EDIT)) {
         return '';
    }

    // try getting the item id list via input variables if necessary
    if (!isset($itemids)) {
        $itemids = xarVarCleanFromInput('itemids');
    }

    // try getting the sort via input variables if necessary
    if (!isset($sort)) {
        $sort = xarVarCleanFromInput('sort');
    }

    // try getting the numitems via input variables if necessary
    if (!isset($numitems)) {
        $numitems = xarVarCleanFromInput('numitems');
    }

    // try getting the startnum via input variables if necessary
    if (!isset($startnum)) {
        $startnum = xarVarCleanFromInput('startnum');
    }

    // don't try getting the where clause via input variables, obviously !
    if (empty($where)) {
        $where = '';
    }

    // check the optional field list
    if (!empty($fieldlist)) {
        // support comma-separated field list
        if (is_string($fieldlist)) {
            $myfieldlist = explode(',',$fieldlist);
        // and array of fields
        } elseif (is_array($fieldlist)) {
            $myfieldlist = $fieldlist;
        }
        $status = null;
    } else {
        $myfieldlist = null;
        // get active properties only (+ not the display only ones)
        $status = 1;
    }

    // include the static properties (= module tables) too ?
    if (empty($static)) {
        $static = false;
    }

    // check the URL parameter for the item id used by the module (e.g. exid, aid, ...)
    if (empty($param)) {
        $param = '';
    }

    if (!xarModAPILoad('dynamicdata','user')) return;

    // retrieve the properties for this module / itemtype
    $fields = xarModAPIFunc('dynamicdata','user','getprop',
                            array('modid' => $modid,
                                  'itemtype' => $itemtype,
                                  'fieldlist' => $myfieldlist,
                                  'status' => $status,
                                  'static' => $static));

    if (!isset($fields) || count($fields) == 0) {
        return xarML('No fields found matching your specification');
    }

    // create the label list + (try to) find the field containing the item id (if any)
    $labels = array();

    foreach ($fields as $name => $field) {
        $labels[$name] = array('label' => $field['label']);

        // TODO: let the module tell us at installation ? (or specify in the template)
        if (empty($param) && $field['type'] == 21) { // Item ID
            // take a wild guess at the parameter name this module expects
            if (!empty($field['source']) && preg_match('/_([^_]+)$/',$field['source'],$matches)) {
                $param = $matches[1];
            }
        }
    }
    if (empty($param)) {
        $param = 'itemid';
    }

    $items = array();
    if (empty($itemids)) {
        $itemids = array();
    } elseif (!is_array($itemids)) {
        $itemids = explode(',',$itemids);
    }

    $items = xarModAPIFunc('dynamicdata','user','getitems',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype,
                                 'itemids' => $itemids,
                                 'sort' => $sort,
                                 'numitems' => $numitems,
                                 'startnum' => $startnum,
                                 'where' => $where,
                                 'fieldlist' => $myfieldlist,
                                 'status' => $status,
                                 'static' => $static));
    if (!isset($items)) return xarML('No items found');

    $nexturl = '';
    $prevurl = '';
    if (!empty($numitems) && (count($items) == $numitems || $startnum > 1)) {
        // Get current URL
        $currenturl = xarServerGetCurrentURL();
        if (empty($startnum)) {
            $startnum = 1;
        }

// TODO: count items
        if (preg_match('/startnum=\d+/',$currenturl)) {
            if (count($items) == $numitems) {
                $next = $startnum + $numitems;
                $nexturl = preg_replace('/startnum=\d+/',"startnum=$next",$currenturl);
            }
            if ($startnum > 1) {
                $prev = $startnum - $numitems;
                $prevurl = preg_replace('/startnum=\d+/',"startnum=$prev",$currenturl);
            }
        } elseif (preg_match('/\?/',$currenturl)) {
            if (count($items) == $numitems) {
                $next = $startnum + $numitems;
                $nexturl = $currenturl . '&startnum=' . $next;
            }
            if ($startnum > 1) {
                $prev = $startnum - $numitems;
                $prevurl = $currenturl . '&startnum=' . $prev;
            }
        } else {
            if (count($items) == $numitems) {
                $next = $startnum + $numitems;
                $nexturl = $currenturl . '?startnum=' . $next;
            }
            if ($startnum > 1) {
                $prev = $startnum - $numitems;
                $prevurl = $currenturl . '?startnum=' . $prev;
            }
        }

/*
        $count = xarModAPIFunc('dynamicdata','user','countitems',
                               array('modid' => $modid,
                                     'itemtype' => $itemtype,
                                     'itemids' => $itemids,
                                     'sort' => $sort,
                                     'numitems' => $numitems,
                                     'startnum' => $startnum,
                                     'where' => $where,
                                     'fieldlist' => $myfieldlist,
                                     'static' => $static));
*/
    }

    // override for viewing dynamic objects
    if ($modname == 'dynamicdata' && $itemtype == 0) {
        $viewtype = 'admin';
        $viewfunc = 'view';
    } else {
        $viewtype = 'user';
        $viewfunc = 'display';
    }

    foreach ($items as $itemid => $item) {
    // TODO: improve this + SECURITY !!!
        $options = array();
        if (xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:$itemid", ACCESS_READ)) {
            $options[] = array('title' => xarML('View'),
                               'link'  => xarModURL($modname,$viewtype,$viewfunc,
                                          array($param => $itemid,
                                                'itemtype' => $itemtype)),
                               'join'  => '');
        }
        if (xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:$itemid", ACCESS_EDIT)) {
            $options[] = array('title' => xarML('Edit'),
                               'link'  => xarModURL($modname,'admin','modify',
                                          array($param => $itemid,
                                                'itemtype' => $itemtype)),
                               'join'  => '|');
        }
        if (xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:$itemid", ACCESS_DELETE)) {
            $options[] = array('title' => xarML('Delete'),
                               'link'  => xarModURL($modname,'admin','delete',
                                          array($param => $itemid,
                                                'itemtype' => $itemtype)),
                               'join'  => '|');
        }
        $items[$itemid]['options'] = $options;
    }

    // TODO: improve this + SECURITY !!!
    if (xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:", ACCESS_ADD)) {
        $newlink = xarModURL($modname,'admin','new',
                             array('itemtype' => $itemtype));
    } else {
        $newlink = '';
    }

    return xarTplModule('dynamicdata','admin','showlist',
                        array('items' => $items,
                              'labels' => $labels,
                              'newlink' => $newlink,
                              'nexturl' => $nexturl,
                              'prevurl' => $prevurl,
                              'layout' => $layout),
                        $template);
}

// ----------------------------------------------------------------------
// Utility functions
// ----------------------------------------------------------------------

function dynamicdata_adminapi_browse($args)
{
    // Argument check - make sure that all required arguments are present
    // and in the right format, if not then set an appropriate error
    // message and return
    if (empty($args['basedir']) || empty($args['filetype'])) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'base directory', 'admin', 'browse', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Security check - we require ADMIN rights here for now...
    if (!xarSecAuthAction(0, 'DynamicData::', '::', ACCESS_ADMIN)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    // Get arguments from argument array
    extract($args);

    $todo = array();
    $basedir = realpath($basedir);
    $filelist = array();
    array_push($todo, $basedir);
    while (count($todo) > 0) {
        $curdir = array_shift($todo);
        if ($dir = @opendir($curdir)) {
            while(($file = @readdir($dir)) !== false) {
                $curfile = $curdir . '/' . $file;
                if (preg_match("/\.$filetype$/",$file) && is_file($curfile)) {
                    // ugly fix for Windows boxes
                    $tmpdir = strtr($basedir,array('\\' => '\\\\'));
                    $curfile = preg_replace("#$tmpdir/#",'',$curfile);
                    $filelist[] = $curfile;
                } elseif ($file != '.' && $file != '..' && is_dir($curfile)) {
                    array_push($todo, $curfile);
                }
            }
            closedir($dir);
        }
    }
    return $filelist;
}

/**
 * utility function pass individual menu items to the main menu
 *
 * @author the Example module development team
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function dynamicdata_adminapi_getmenulinks()
{

    if (xarSecAuthAction(0, 'DynamicData::', '::', ACCESS_ADMIN)) {

        $menulinks[] = Array('url'   => xarModURL('dynamicdata',
                                                   'admin',
                                                   'view'),
                              'title' => xarML('View module objects using dynamic data'),
                              'label' => xarML('View Objects'));
    }

    if (xarSecAuthAction(0, 'DynamicData::', '::', ACCESS_ADMIN)) {
        $menulinks[] = Array('url'   => xarModURL('dynamicdata',
                                                  'admin',
                                                  'modifyconfig'),
                              'title' => xarML('Configure the default property types'),
                              'label' => xarML('Property Types'));
    }

    if (empty($menulinks)){
        $menulinks = '';
    }

    return $menulinks;
}

//TODO: function to create new types?
//TODO: make sure the constants in the CORE match the types (XARUSER_DUD_TYPE_CORE and friends)

?>
