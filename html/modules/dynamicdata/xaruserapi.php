<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: mikespub
// Purpose of file:  Dynamic Data user API
// ----------------------------------------------------------------------

/**
 * get all dynamic data fields for an item
 * (identified by module + item type + item id)
 *
 * @author the DynamicData module development team
 * @param $args['module'] module name of the item fields to get, or
 * @param $args['modid'] module id of the item fields to get
 * @param $args['itemtype'] item type of the item fields to get
 * @param $args['itemid'] item id of the item fields to get
 * @returns array
 * @return array of fields, or false on failure
 * @raise BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getall($args)
{
    extract($args);

    if (empty($modid) && !empty($module)) {
        $modid = xarModGetIDFromName($module);
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $invalid = array();
    if (!isset($modid) || !is_numeric($modid)) {
        $invalid[] = 'module id';
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (!isset($itemid) || !is_numeric($itemid)) {
        $invalid[] = 'item id';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'user', 'getall', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (!xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:$itemid", ACCESS_OVERVIEW)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    $fields = xarModAPIFunc('dynamicdata','user','getprop',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype));
    if (empty($fields) || count($fields) == 0) {
        return array();
    }

    $ids = array_keys($fields);

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $dynamicdata = $xartable['dynamic_data'];
    $dynamicprop = $xartable['dynamic_properties'];

    $sql = "SELECT xar_dd_propid,
                   xar_dd_value
             FROM $dynamicdata
            WHERE xar_dd_propid IN (" . join(', ',$ids) . ")
              AND xar_dd_itemid = " . xarVarPrepForStore($itemid);
    $result = $dbconn->Execute($sql);

    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $sql);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    while (!$result->EOF) {
        list($id, $value) = $result->fields;
        if (isset($value)) {
            $fields[$id]['value'] = $value;
        }
        $result->MoveNext();
    }

    $result->Close();

    foreach ($fields as $id => $field) {
        if (xarSecAuthAction(0, 'DynamicData::Field', $field['label'].':'.$field['type'].':'.$id, ACCESS_READ)) {
            if (!isset($field['value'])) {
                $fields[$id]['value'] = $fields[$id]['default'];
            }
        } else {
            unset($fields[$id]);
        }
    }

    return $fields;
}

/**
 * get a specific item field
 *
 * @author the DynamicData module development team
 * @param $args['module'] module name of the item field to get, or
 * @param $args['modid'] module id of the item field to get
 * @param $args['itemtype'] item type of the item field to get
 * @param $args['itemid'] item id of the item field to get
 * @param $args['prop_id'] property id of the field to get, or
 * @param $args['name'] name of the field to get
 * @returns mixed
 * @return value of the field, or false on failure
 * @raise BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_get($args)
{
    extract($args);

    if (empty($modid) && !empty($module)) {
        $modid = xarModGetIDFromName($module);
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $invalid = array();
    if (!isset($modid) || !is_numeric($modid)) {
        $invalid[] = 'module id';
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (!isset($itemid) || !is_numeric($itemid)) {
        $invalid[] = 'item id';
    }
    if ((!isset($name) && !isset($prop_id)) ||
        (isset($name) && !is_string($name)) ||
        (isset($prop_id) && !is_numeric($prop_id))) {
        $invalid[] = 'field name or property id';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'user', 'get', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $dynamicdata = $xartable['dynamic_data'];
    $dynamicprop = $xartable['dynamic_properties'];

    $sql = "SELECT xar_prop_label,
                   xar_prop_dtype,
                   xar_prop_id,
                   xar_prop_default,
                   xar_dd_value
            FROM $dynamicdata, $dynamicprop
            WHERE xar_prop_id = xar_dd_propid
              AND xar_prop_moduleid = " . xarVarPrepForStore($modid);
    if (!empty($itemtype)) {
        $sql .= " AND xar_prop_itemtype = " . xarVarPrepForStore($itemtype);
    }
    $sql .= " AND xar_dd_itemid = " . xarVarPrepForStore($itemid);
    if (!empty($prop_id)) {
        $sql .= " AND xar_prop_id = " . xarVarPrepForStore($prop_id);
    } else {
        $sql .= " AND xar_prop_label = '" . xarVarPrepForStore($name) . "'";
    }

    $result = $dbconn->Execute($sql);

    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $sql);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    if ($result->EOF) {
        $result->Close();
        return;
    }
    list($label, $type, $id, $default, $value) = $result->fields;
    $result->Close();

    if (!xarSecAuthAction(0, 'DynamicData::Field', "$label:$type:$id", ACCESS_READ)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }
    if (!isset($value)) {
        $value = $default;
    }

    return $value;
}

/**
 * get field properties for a specific module + item type
 *
 * @author the DynamicData module development team
 * @param $args['module'] module name of the item fields, or
 * @param $args['modid'] module id of the item field to get
 * @param $args['itemtype'] item type of the item field to get
 * @returns mixed
 * @return value of the field, or false on failure
 * @raise BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getprop($args)
{
    static $propertybag = array();

    extract($args);

    if (empty($modid) && !empty($module)) {
        $modid = xarModGetIDFromName($module);
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $invalid = array();
    if (!isset($modid) || !is_numeric($modid)) {
        $invalid[] = 'module id';
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'user', 'getprop', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (isset($propertybag["$modid:$itemtype"])) {
        return $propertybag["$modid:$itemtype"];
    }

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $dynamicprop = $xartable['dynamic_properties'];

    $sql = "SELECT xar_prop_label,
                   xar_prop_dtype,
                   xar_prop_id,
                   xar_prop_default,
                   xar_prop_validation
            FROM $dynamicprop
            WHERE xar_prop_moduleid = " . xarVarPrepForStore($modid) . "
              AND xar_prop_itemtype = " . xarVarPrepForStore($itemtype);

    $result = $dbconn->Execute($sql);

    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $sql);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    $fields = array();

    while (!$result->EOF) {
        list($label, $type, $id, $default, $validation) = $result->fields;
        if (xarSecAuthAction(0, 'DynamicData::Field', "$label:$type:$id", ACCESS_READ)) {
            $fields[$id] = array('label' => $label,
                                 'type' => $type,
                                 'id' => $id,
                                 'default' => $default,
                                 'validation' => $validation);
        }
        $result->MoveNext();
    }

    $result->Close();

    $propertybag["$modid:$itemtype"] = $fields;
    return $fields;
}

/**
 * get the list of modules + itemtypes for which properties are defined
 *
 * @author the DynamicData module development team
 * @returns array
 * @return array of modid + itemtype + number of properties
 * @raise DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getmodules($args)
{
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $dynamicprop = $xartable['dynamic_properties'];

    $query = "SELECT xar_prop_moduleid,
                     xar_prop_itemtype,
                     COUNT(xar_prop_id)
              FROM $dynamicprop
              GROUP BY xar_prop_moduleid, xar_prop_itemtype";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    $modules = array();

    while (!$result->EOF) {
        list($modid, $itemtype, $count) = $result->fields;
        if (xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:", ACCESS_OVERVIEW)) {
            $modules[] = array('modid' => $modid,
                               'itemtype' => $itemtype,
                               'numitems' => $count);
        }
        $result->MoveNext();
    }

    $result->Close();

    return $modules;
}

/**
 * get the list of defined property types from somewhere...
 *
 * @author the DynamicData module development team
 * @returns array
 * @return array of property types
 * @raise DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getproptypes($args)
{
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $proptypes = array();

// TODO: replace with something else
    // Let's get them from articles for now...
    if (!xarModAPILoad('articles','user')) return;

    $formats = xarModAPIFunc('articles','user','getpubfieldformats');
    $formatnums = xarModAPIFunc('articles','user','getfieldformatnums');
    foreach ($formats as $name => $label) {
        $id = $formatnums[$name];
        $proptypes[] = array('id' => $id,
                             'name' => $name,
                             'label' => $label,
                             'format' => $id
                            );
    }

// TODO: yes :)
/*
    $dynamicproptypes = $xartable['dynamic_property_types'];

    $query = "SELECT ...
              FROM $dynamicproptypes";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    while (!$result->EOF) {
        list(...) = $result->fields;
        if (xarSecAuthAction(0, '...', "...", ACCESS_OVERVIEW)) {
            $proptypes[] = array(...);
        }
        $result->MoveNext();
    }

    $result->Close();
*/

    return $proptypes;
}

// ----------------------------------------------------------------------
// TODO: search API, some generic queries for statistics, etc.
//

/**
 * utility function to count the number of items held by this module
 *
 * @author the DynamicData module development team
 * @returns integer
 * @return number of items held by this module
 * @raise DATABASE_ERROR
 */
function dynamicdata_userapi_countitems()
{
    // Get database setup - note that both xarDBGetConn() and xarDBGetTables()
    // return arrays but we handle them differently.  For xarDBGetConn() we
    // currently just want the first item, which is the official database
    // handle.  For xarDBGetTables() we want to keep the entire tables array
    // together for easy reference later on
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    // It's good practice to name the table and column definitions you are
    // getting - $table and $column don't cut it in more complex modules
    $exampletable = $xartable['example'];

    // Get item - the formatting here is not mandatory, but it does make the
    // SQL statement relatively easy to read.  Also, separating out the sql
    // statement from the Execute() command allows for simpler debug operation
    // if it is ever needed
    $sql = "SELECT COUNT(1)
            FROM $exampletable";
    $result = $dbconn->Execute($sql);

    // Check for an error with the database code, and if so set an appropriate
    // error message and return
    if ($dbconn->ErrorNo() != 0) {
        // Hint : for debugging SQL queries, you can use $dbconn->ErrorMsg()
        // to retrieve the actual database error message, and use e.g. the
        // following message :
        // $msg = xarML('Database error #(1) in query #(2) for #(3) function ' .
        //             '#(4)() in module #(5)',
        //          $dbconn->ErrorMsg(), $sql, 'user', 'countitems', 'DynamicData');
        // Don't use that for release versions, though...
        /*
        $msg = xarML('Database error for #(1) function #(2)() in module #(3)',
                    'user', 'countitems', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
        */
        // This is the API compliant way to raise a db error exception
        $msg = xarMLByKey('DATABASE_ERROR', $sql);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    // Obtain the number of items
    list($numitems) = $result->fields;

    // All successful database queries produce a result set, and that result
    // set should be closed when it has been finished with
    $result->Close();

    // Return the number of items
    return $numitems;
}

?>
