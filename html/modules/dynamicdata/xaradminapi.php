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

require_once 'modules/dynamicdata/class/objects.php';

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
 * @raise BAD_PARAM, NO_PERMISSION
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

// TODO: test this
    $myobject = new Dynamic_Object(array('moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'itemid'   => $itemid));
    if (empty($myobject)) return;

    if (count($values) == 0) {
        foreach ($fields as $field) {
            if (isset($field['value'])) {
                $values[$field['name']] = $field['value'];
            }
        }
    }
    $itemid = $myobject->createItem($values);

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
 * @raise BAD_PARAM, NO_PERMISSION
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

// TODO: test this
    $myobject = new Dynamic_Object(array('moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'itemid'   => $itemid));
    if (empty($myobject)) return;

    $myobject->getItem();

    $itemid = $myobject->deleteItem();

    return $itemid;
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
 * @raise BAD_PARAM, NO_PERMISSION
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

// TODO: test this
    $myobject = new Dynamic_Object(array('moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'itemid'   => $itemid));
    if (empty($myobject)) return;

    $myobject->getItem();

    if (count($values) == 0) {
        foreach ($fields as $field) {
            if (isset($field['value'])) {
                $values[$field['name']] = $field['value'];
            }
        }
    }
    $itemid = $myobject->updateItem($values);

    return $itemid;
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
 * @raise BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_createobject($args)
{
    $objectid = Dynamic_Object_Master::createObject($args);
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
 * @raise BAD_PARAM, NO_PERMISSION
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

    $values = array();
    // the acceptable arguments correspond to the property names !
    foreach ($fields as $name => $field) {
        if (isset($args[$name])) {
            $values[$name] = $args[$name];
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
                            array('modid'    => xarModGetIDFromName('dynamicdata'), //$moduleid,
                                  'itemtype' => 1, //$itemtype,
                                  'itemid'   => $itemid,
                                  'values'   => $values));
    if (!isset($propid)) return;

    return $propid;
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
 * @raise BAD_PARAM, NO_PERMISSION
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

    $result = $dbconn->Execute($sql);

    if (!$result) return;

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
 * @raise BAD_PARAM, NO_PERMISSION
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

    $result = $dbconn->Execute($sql);

    if (!$result) return;

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

    $myobject = new Dynamic_Object(array('moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'itemid'   => $itemid));
    if (!isset($myobject)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'object', 'admin', $dd_function, 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    $myobject->getItem();
    $isvalid = $myobject->checkInput();
    if (!$isvalid) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'input', 'admin', $dd_function, 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    if ($dd_function == 'createhook') {
        $itemid = $myobject->createItem();
    } else {
        $itemid = $myobject->updateItem();
    }

    if (empty($itemid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'create/update', 'admin', $dd_function, 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
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
// Input validation (replaced by validation in Dynamic_Property)
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

    // replaced by validation in Dynamic_Property

// TODO: test this replacement :)
    $invalid = array();
    foreach ($args['fields'] as $field) {
        $property = & Dynamic_Property_Master::getProperty($field);
        if (!$property->checkInput()) {
            $invalid[$property->name] = $property->invalid;
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
 * Format : <xar:data-input name="thisname" type="thattype" value="$val" ... />
 *       or <xar:data-input field="$field" /> with $field an array containing the type, name, value, ...
 *       or <xar:data-input property="$property" /> with $property a Dynamic Property object
 *
 * @param $args array containing the input field definition or the type, name, value, ...
 * @returns string
 * @return the PHP code needed to invoke showinput() in the BL template
 */
function dynamicdata_adminapi_handleInputTag($args)
{
    // we just invoke the showInput() method of the Dynamic Property here
    if (!empty($args['property'])) {
        if (count($args) > 1) {
            $parts = array();
            foreach ($args as $key => $val) {
                if ($key == 'property' || $key == 'hidden') continue;
                if (is_numeric($val) || substr($val,0,1) == '$') {
                    $parts[] = "'$key' => ".$val;
                } else {
                    $parts[] = "'$key' => '".$val."'";
                }
            }
            if (!empty($args['hidden'])) {
                return 'echo '.$args['property'].'->showHidden(array('.join(', ',$parts).')); ';
            } else {
                return 'echo '.$args['property'].'->showInput(array('.join(', ',$parts).')); ';
            }
        } else {
            return 'echo '.$args['property'].'->showInput(); ';
        }
    }

    // we'll call a function to do it for us
    $out = "echo xarModAPIFunc('dynamicdata',
                   'admin',
                   'showinput',\n";
    if (isset($args['field'])) {
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
    $property = & Dynamic_Property_Master::getProperty($args);
    if (!empty($args['hidden'])) {
        return $property->showHidden($args);
    } else {
        return $property->showInput($args);
    }

    // TODO: input for some common hook/utility modules
}

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * Handle <xar:data-form ...> form tags
 * Format : <xar:data-form module="123" itemtype="0" itemid="555" fieldlist="$fieldlist" static="yes" ... />
 *       or <xar:data-form fields="$fields" ... />
 *       or <xar:data-form object="$object" ... />
 *
 * @param $args array containing the item for which you want to show a form, or fields
 * @returns string
 * @return the PHP code needed to invoke showform() in the BL template
 */
function dynamicdata_adminapi_handleFormTag($args)
{
    if (!empty($args['object'])) {
        if (count($args) > 1) {
            $parts = array();
            foreach ($args as $key => $val) {
                if ($key == 'object') continue;
                if (is_numeric($val) || substr($val,0,1) == '$') {
                    $parts[] = "'$key' => ".$val;
                } else {
                    $parts[] = "'$key' => '".$val."'";
                }
            }
            return 'echo '.$args['object'].'->showForm(array('.join(', ',$parts).')); ';
        } else {
            return 'echo '.$args['object'].'->showForm(); ';
        }
    }

    $out = "echo xarModAPIFunc('dynamicdata',
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

    // throw an exception if you can't edit this
    if (!xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:$itemid", ACCESS_EDIT)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    $object = new Dynamic_Object(array('moduleid'  => $modid,
                                       'itemtype'  => $itemtype,
                                       'itemid'    => $itemid,
                                       'fieldlist' => $myfieldlist));
    if (!empty($itemid)) {
        $object->getItem();
    }
    // if we are in preview mode, we need to check for any preview values
    //if (!isset($preview)) {
    //    $preview = xarVarCleanFromInput('preview');
    //}
    //if (!empty($preview)) {
    //    $object->checkInput();
    //}
    return $object->showForm(array('layout'   => $layout,
                                   'template' => $template));

}

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * Handle <xar:data-list ...> list tags
 * Format : <xar:data-list module="123" itemtype="0" itemids="$idlist" fieldlist="$fieldlist" static="yes" .../>
 *       or <xar:data-list items="$items" labels="$labels" ... />
 *       or <xar:data-list object="$object" ... />
 *
 * @param $args array containing the items that you want to list, or fields
 * @returns string
 * @return the PHP code needed to invoke showlist() in the BL template
 */
function dynamicdata_adminapi_handleListTag($args)
{
    // if we already have an object, we simply invoke its showList() method
    if (!empty($args['object'])) {
        if (count($args) > 1) {
            $parts = array();
            foreach ($args as $key => $val) {
                if ($key == 'object') continue;
                if (is_numeric($val) || substr($val,0,1) == '$') {
                    $parts[] = "'$key' => ".$val;
                } else {
                    $parts[] = "'$key' => '".$val."'";
                }
            }
            return 'echo '.$args['object'].'->showList(array('.join(', ',$parts).')); ';
        } else {
            return 'echo '.$args['object'].'->showList(); ';
        }
    }

    // if we don't have an object yet, we'll make one below
    $out = "echo xarModAPIFunc('dynamicdata',
                   'admin',
                   'showlist',\n";
    $out .= "                   array(\n";
    foreach ($args as $key => $val) {
        if (is_numeric($val) || substr($val,0,1) == '$') {
            $out .= "                         '$key' => $val,\n";
        } else {
            $out .= "                         '$key' => '$val',\n";
        }
    }
    $out .= "                         ));";
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

    $object = new Dynamic_Object_List(array('moduleid'  => $modid,
                                           'itemtype'  => $itemtype,
                                           'itemids' => $itemids,
                                           'sort' => $sort,
                                           'numitems' => $numitems,
                                           'startnum' => $startnum,
                                           'where' => $where,
                                           'fieldlist' => $myfieldlist,
                                           'status' => $status));
    $object->getItems();
    return $object->showList(array('layout'   => $layout,
                                   'template' => $template));
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
	if(!xarSecurityCheck('Admin')) return;

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

    $menulinks = array();

// Security Check
	if (xarSecurityCheck('Admin',0)) {

        $menulinks[] = Array('url'   => xarModURL('dynamicdata',
                                                   'admin',
                                                   'view'),
                              'title' => xarML('View module objects using dynamic data'),
                              'label' => xarML('View Objects'));
    }

// Security Check
	if (xarSecurityCheck('Admin',0)) {
        $menulinks[] = Array('url'   => xarModURL('dynamicdata',
                                                  'admin',
                                                  'modifyconfig'),
                              'title' => xarML('Configure the default property types'),
                              'label' => xarML('Property Types'));
    }

// Security Check
	if (xarSecurityCheck('Admin',0)) {
        $menulinks[] = Array('url'   => xarModURL('dynamicdata',
                                                  'util',
                                                  'main'),
                              'title' => xarML('Import/export and other utilities'),
                              'label' => xarML('Utilities'));
    }

    return $menulinks;
}

//TODO: function to create new types?
//TODO: make sure the constants in the CORE match the types (XARUSER_DUD_TYPE_CORE and friends)

?>
