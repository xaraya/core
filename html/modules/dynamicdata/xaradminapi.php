<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Jim McDonald
// Purpose of file:  DynamicData administration API
// ----------------------------------------------------------------------

// ----------------------------------------------------------------------
// Item data APIs
// ----------------------------------------------------------------------

/**
 * create new dynamicdata fields for an item
 *
 * @author the DynamicData module development team
 * @param $args['itemid'] item id of the original item
 * @param $args['values'] array of prop_id => value
 * @param $args['modid'] module id for the original item
 * @param $args['itemtype'] item type of the original item
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_create($args)
{
    extract($args);

    $invalid = array();
    if (!isset($itemid) || !is_numeric($itemid)) {
        $invalid[] = 'item id';
    }
    if (!isset($values) || !is_array($values)) {
        $invalid[] = 'values';
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
    if (!xarSecAuthAction(0, 'DynamicData::Item', "::$itemid", ACCESS_ADD)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $dynamicdata = $xartable['dynamic_data'];

    foreach ($values as $prop_id => $value) {
        // invalid prop_id or undefined value (empty is OK, though !)
        if (empty($prop_id) || !is_numeric($prop_id) || !isset($value)) {
            continue;
        }

        $nextId = $dbconn->GenId($dynamicdata);

        $sql = "INSERT INTO $dynamicdata (
                  xar_dd_id,
                  xar_dd_propid,
                  xar_dd_itemid,
                  xar_dd_value)
            VALUES (
              $nextId,
              " . xarVarPrepForStore($prop_id) . ",
              " . xarVarPrepForStore($itemid) . ",
              '" . xarVarPrepForStore($value) . "')";

        $dbconn->Execute($sql);

        // Check for an error with the database code, and if so raise an
        // appropriate exception
        if ($dbconn->ErrorNo() != 0) {
            $msg = xarMLByKey('DATABASE_ERROR', $sql);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                           new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return;
        }

        //$id = $dbconn->PO_Insert_ID($dynamicdata, 'xar_dd_id');
    }

    return true;
}

/**
 * delete dynamicdata fields for an item
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
    if (!xarSecAuthAction(0, 'DynamicData::Item', "::$itemid", ACCESS_DELETE)) {
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
    $ids = array_keys($fields);

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
 * update dynamicdata fields for an item
 *
 * @author the DynamicData module development team
 * @param $args['itemid'] item id of the original item
 * @param $args['values'] array of prop_id => value
 * @param $args['modid'] module id for the original item
 * @param $args['itemtype'] item type of the original item
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_update($args)
{
    extract($args);

    $invalid = array();
    if (!isset($itemid) || !is_numeric($itemid)) {
        $invalid[] = 'item id';
    }
    if (!isset($modid) || !is_numeric($modid)) {
        $invalid[] = 'module id';
    }
    if (!isset($values) || !is_array($values)) {
        $invalid[] = 'values';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'admin', 'create', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $itemtype = 0;
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if (!xarSecAuthAction(0, 'DynamicData::Item', "::$itemid", ACCESS_ADD)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

// TODO: be a bit more efficient in how we update fields here :-)
    if (!xarModAPIFunc('dynamicdata', 'admin', 'delete',
                      array('modid' => $modid,
                            'itemtype' => $itemtype,
                            'itemid' => $itemid))) {
        return;
    }

    if (!xarModAPIFunc('dynamicdata', 'admin', 'create',
                      array('modid' => $modid,
                            'itemtype' => $itemtype,
                            'itemid' => $itemid,
                            'values'  => $values))) {
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
                    'module name', 'admin', 'createhook', 'dynamicdata');
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
        $fields = array();
    }

    $values = array();
    foreach ($fields as $id => $field) {
// TODO: allow field label (sanitized !) here too ?
        if (isset($extrainfo['dd_'.$id])) {
             $values[$id] = $extrainfo['dd_'.$id];
        } else {
             $values[$id] = xarVarCleanFromInput('dd_'.$id);
        }
    }

    if (!xarModAPIFunc('dynamicdata', 'admin', 'create',
                      array('modid' => $modid,
                            'itemtype' => $itemtype,
                            'itemid' => $itemid,
                            'values'  => $values))) {
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    // update the extrainfo array
    foreach ($fields as $id => $field) {
        if (isset($values[$id])) {
            $extrainfo['dd_'.$id] = $values[$id];
        }
    }

    // Return the extra info
    return $extrainfo;
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
                    'module name', 'admin', 'updatehook', 'dynamicdata');
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
        $fields = array();
    }

    $values = array();
    foreach ($fields as $id => $field) {
// TODO: allow field label (sanitized !) here too ?
        if (isset($extrainfo['dd_'.$id])) {
             $values[$id] = $extrainfo['dd_'.$id];
        } else {
             $values[$id] = xarVarCleanFromInput('dd_'.$id);
        }
    }

    if (!xarModAPIFunc('dynamicdata', 'admin', 'update',
                      array('modid' => $modid,
                            'itemtype' => $itemtype,
                            'itemid' => $itemid,
                            'values'  => $values))) {
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    // update the extrainfo array
    foreach ($fields as $id => $field) {
        if (isset($values[$id])) {
            $extrainfo['dd_'.$id] = $values[$id];
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
                      array('modid' => $modid,
                            'itemtype' => $itemtype,
                            'itemid' => $itemid))) {
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
// Property field APIs
// ----------------------------------------------------------------------

/**
 * create a new property field
 *
 * @author the DynamicData module development team
 * @param $args['modid'] module id of the item field to create
 * @param $args['itemtype'] item type of the item field to create
 * @param $args['label'] name of the field to create
 * @param $args['type'] type of the field to create
 * @param $args['default'] default of the field to create
 * @param $args['validation'] validation of the field to create
 * @returns int
 * @return dynamicdata prop ID on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_createprop($args)
{
    extract($args);

    // Required arguments
    $invalid = array();
    if (!isset($modid) || !is_numeric($modid)) {
        $invalid[] = 'module id';
    }
    if (!isset($label) || !is_string($label)) {
        $invalid[] = 'label';
    }
    if (!isset($type) || !is_numeric($type)) {
        $invalid[] = 'type';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'admin', 'createprop', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Optional arguments
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $itemtype = 0;
    }
    if (!isset($default) || !is_string($default)) {
        $default = '';
    }
    if (!isset($validation) || !is_string($validation)) {
        $validation = '';
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if (!xarSecAuthAction(0, 'DynamicData::Field', "$label:$type:", ACCESS_ADD)) {
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

    // Get next ID in table - this is required prior to any insert that
    // uses a unique ID, and ensures that the ID generation is carried
    // out in a database-portable fashion
    $nextId = $dbconn->GenId($dynamicprop);

    // Add item - the formatting here is not mandatory, but it does make
    // the SQL statement relatively easy to read.  Also, separating out
    // the sql statement from the Execute() command allows for simpler
    // debug operation if it is ever needed
    $sql = "INSERT INTO $dynamicprop (
              xar_prop_id,
              xar_prop_moduleid,
              xar_prop_itemtype,
              xar_prop_label,
              xar_prop_dtype,
              xar_prop_default,
              xar_prop_validation)
            VALUES (
              $nextId,
              " . xarVarPrepForStore($modid) . ",
              " . xarVarPrepForStore($itemtype) . ",
              '" . xarVarPrepForStore($label) . "',
              " . xarVarPrepForStore($type) . ",
              '" . xarVarPrepForStore($default) . "',
              '" . xarVarPrepForStore($validation) . "')";
    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so raise an
    // appropriate exception
    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $sql);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    // Get the ID of the item that we inserted.  It is possible, depending
    // on your database, that this is different from $nextId as obtained
    // above, so it is better to be safe than sorry in this situation
    $prop_id = $dbconn->PO_Insert_ID($dynamicprop, 'xar_prop_id');

    // Return the id of the newly created item to the calling process
    return $prop_id;
}

/**
 * update a property field
 *
 * @author the DynamicData module development team
 * @param $args['prop_id'] property id of the item field to update
 * @param $args['modid'] module id of the item field to update (optional)
 * @param $args['itemtype'] item type of the item field to update (optional)
 * @param $args['label'] name of the field to update
 * @param $args['type'] type of the field to update
 * @param $args['default'] default of the field to update (optional)
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
    if (!xarSecAuthAction(0, 'DynamicData::Field', "$label:$type:$prop_id", ACCESS_EDIT)) {
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
                xar_prop_dtype = " . xarVarPrepForStore($type);
    if (isset($default) && is_string($default)) {
        $sql .= ", xar_prop_default = '" . xarVarPrepForStore($default) . "'";
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
 * @param $args['label'] name of the field to delete
 * @param $args['type'] type of the field to delete
 * @param $args['default'] default of the field to delete
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

//TODO: function to get a list of defined types
//TODO: function to create new types?
//TODO: make sure the constants in the CORE match the types (XARUSER_DUD_TYPE_CORE and friends)
//TODO: integrate with xarModGetVar, xarModSetVar

?>
