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
        $modid = pnModGetIDFromName($module);
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
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'user', 'getall', 'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (!pnSecAuthAction(0, 'DynamicData::Items', $modid.':'.$itemtype.':'.$itemid, ACCESS_OVERVIEW)) {
        $msg = pnML('Not authorized to access #(1) fields',
                    'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $dynamicdata = $pntable['dynamic_data'];
    $dynamicprop = $pntable['dynamic_properties'];

    $fields = pnModAPIFunc('dynamicdata','user','getprop',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype));
    $ids = array_keys($fields);

    $sql = "SELECT pn_dd_propid,
                   pn_dd_value
             FROM $dynamicdata
            WHERE pn_dd_propid IN (" . join(', ',$ids) . ")
              AND pn_dd_itemid = " . pnVarPrepForStore($itemid);
    $result = $dbconn->Execute($sql);

    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $sql);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    while (!$result->EOF) {
        list($id, $value) = $result->fields;
        if (pnSecAuthAction(0, 'DynamicData::Items', "$modid:$itemtype:$id", ACCESS_READ)) {
            if (!isset($value)) {
                $value = $default;
            }
            $fields[$id]['value'] = $value;
        }
        $result->MoveNext();
    }

    $result->Close();

    foreach ($fields as $id => $field) {
        if (!isset($field['value'])) {
            $fields[$id]['value'] = $fields[$id]['default'];
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
        $modid = pnModGetIDFromName($module);
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
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'user', 'get', 'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $dynamicdata = $pntable['dynamic_data'];
    $dynamicprop = $pntable['dynamic_properties'];

    $sql = "SELECT pn_prop_label,
                   pn_prop_dtype,
                   pn_prop_id,
                   pn_prop_default,
                   pn_dd_value
            FROM $dynamicdata, $dynamicprop
            WHERE pn_prop_id = pn_dd_propid
              AND pn_prop_moduleid = " . pnVarPrepForStore($modid);
    if (!empty($itemtype)) {
        $sql .= " AND pn_prop_itemtype = " . pnVarPrepForStore($itemtype);
    }
    $sql .= " AND pn_dd_itemid = " . pnVarPrepForStore($itemid);
    if (!empty($prop_id)) {
        $sql .= " AND pn_prop_id = " . pnVarPrepForStore($prop_id);
    } else {
        $sql .= " AND pn_prop_label = '" . pnVarPrepForStore($name) . "'";
    }

    $result = $dbconn->Execute($sql);

    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $sql);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    if ($result->EOF) {
        $result->Close();
        return;
    }
    list($label, $type, $id, $default, $value) = $result->fields;
    $result->Close();

    if (!pnSecAuthAction(0, 'DynamicData::Fields', "$label:$type:$id", ACCESS_READ)) {
        $msg = pnML('Not authorized to access #(1) fields',
                    'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
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
    extract($args);

    if (empty($modid) && !empty($module)) {
        $modid = pnModGetIDFromName($module);
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
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'user', 'getprop', 'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $dynamicprop = $pntable['dynamic_properties'];

    $sql = "SELECT pn_prop_label,
                   pn_prop_dtype,
                   pn_prop_id,
                   pn_prop_default,
                   pn_prop_validation
            FROM $dynamicprop
            WHERE pn_prop_moduleid = " . pnVarPrepForStore($modid);
    if (!empty($itemtype)) {
        $sql .= " AND pn_prop_itemtype = " . pnVarPrepForStore($itemtype);
    }

    $result = $dbconn->Execute($sql);

    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $sql);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    $fields = array();

    while (!$result->EOF) {
        list($label, $type, $id, $default, $validation) = $result->fields;
        if (pnSecAuthAction(0, 'DynamicData::Fields', "$label:$type:$id", ACCESS_READ)) {
            $fields[$id] = array('label' => $label,
                                 'type' => $type,
                                 'id' => $id,
                                 'default' => $default,
                                 'validation' => $validation);
        }
        $result->MoveNext();
    }

    $result->Close();

    return $fields;
}

// TODO...

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
    // Get database setup - note that both pnDBGetConn() and pnDBGetTables()
    // return arrays but we handle them differently.  For pnDBGetConn() we
    // currently just want the first item, which is the official database
    // handle.  For pnDBGetTables() we want to keep the entire tables array
    // together for easy reference later on
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    // It's good practice to name the table and column definitions you are
    // getting - $table and $column don't cut it in more complex modules
    $exampletable = $pntable['example'];

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
        // $msg = pnML('Database error #(1) in query #(2) for #(3) function ' .
        //             '#(4)() in module #(5)',
        //          $dbconn->ErrorMsg(), $sql, 'user', 'countitems', 'DynamicData');
        // Don't use that for release versions, though...
        /*
        $msg = pnML('Database error for #(1) function #(2)() in module #(3)',
                    'user', 'countitems', 'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
        */
        // This is the API compliant way to raise a db error exception
        $msg = pnMLByKey('DATABASE_ERROR', $sql);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
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
