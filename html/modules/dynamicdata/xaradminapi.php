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
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'admin', 'create', 'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if (!pnSecAuthAction(0, 'DynamicData::Item', "::$itemid", ACCESS_ADD)) {
        $msg = pnML('Not authorized to add #(1) items',
                    'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $dynamicdata = $pntable['dynamic_data'];

    foreach ($values as $prop_id => $value) {
        // invalid prop_id or undefined value (empty is OK, though !)
        if (empty($prop_id) || !is_numeric($prop_id) || !isset($value)) {
            continue;
        }

        $nextId = $dbconn->GenId($dynamicdata);

        $sql = "INSERT INTO $dynamicdata (
                  pn_dd_id,
                  pn_dd_propid,
                  pn_dd_itemid,
                  pn_dd_value)
            VALUES (
              $nextId,
              " . pnVarPrepForStore($prop_id) . ",
              " . pnVarPrepForStore($itemid) . ",
              '" . pnvarPrepForStore($value) . "')";

        $dbconn->Execute($sql);

        // Check for an error with the database code, and if so raise an
        // appropriate exception
        if ($dbconn->ErrorNo() != 0) {
            $msg = pnMLByKey('DATABASE_ERROR', $sql);
            pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                           new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return;
        }

        //$id = $dbconn->PO_Insert_ID($dynamicdata, 'pn_dd_id');
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
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'admin', 'delete', 'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $itemtype = 0;
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if (!pnSecAuthAction(0, 'DynamicData::Item', "::$itemid", ACCESS_DELETE)) {
        $msg = pnML('Not authorized to delete #(1) items',
                    'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    if (!pnModAPILoad('dynamicdata', 'user'))
    {
        $msg = pnML('Unable to load #(1) #(2) API',
                    'dynamicdata','user');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNABLE_TO_LOAD',
                       new SystemException($msg));
        return;
    }
    $fields = pnModAPIFunc('dynamicdata','user','getprop',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype));
    if (!isset($fields) || $fields == false) {
        return true;
    }
    $ids = array_keys($fields);

    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $dynamicdata = $pntable['dynamic_data'];

    $sql = "DELETE FROM $dynamicdata
            WHERE pn_dd_propid IN (" . join(', ',$ids) . ")
              AND pn_dd_itemid = " . pnVarPrepForStore($itemid);

    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so raise an
    // appropriate exception
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $sql);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
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
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'admin', 'create', 'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $itemtype = 0;
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if (!pnSecAuthAction(0, 'DynamicData::Item', "::$itemid", ACCESS_ADD)) {
        $msg = pnML('Not authorized to add #(1) items',
                    'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

// TODO: be a bit more efficient in how we update fields here :-)
    if (!pnModAPIFunc('dynamicdata', 'admin', 'delete',
                      array('modid' => $modid,
                            'itemtype' => $itemtype,
                            'itemid' => $itemid))) {
        return;
    }

    if (!pnModAPIFunc('dynamicdata', 'admin', 'create',
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
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'object id', 'admin', 'createhook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }
    if (!isset($extrainfo) || !is_array($extrainfo)) {
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'extrainfo', 'admin', 'createhook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($extrainfo['module'])) {
        $modname = pnModGetName();
    } else {
        $modname = $extrainfo['module'];
    }

    $modid = pnModGetIDFromName($modname);
    if (empty($modid)) {
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'admin', 'createhook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
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
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'admin', 'createhook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    if (!pnModAPILoad('dynamicdata', 'user'))
    {
        $msg = pnML('Unable to load #(1) #(2) API',
                    'dynamicdata','user');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNABLE_TO_LOAD',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }
    $fields = pnModAPIFunc('dynamicdata','user','getprop',
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
             $values[$id] = pnVarCleanFromInput('dd_'.$id);
        }
    }

    if (!pnModAPIFunc('dynamicdata', 'admin', 'create',
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
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'object id', 'admin', 'createhook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }
    if (!isset($extrainfo) || !is_array($extrainfo)) {
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'extrainfo', 'admin', 'createhook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($extrainfo['module'])) {
        $modname = pnModGetName();
    } else {
        $modname = $extrainfo['module'];
    }

    $modid = pnModGetIDFromName($modname);
    if (empty($modid)) {
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'admin', 'createhook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
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
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'admin', 'updatehook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    if (!pnModAPILoad('dynamicdata', 'user'))
    {
        $msg = pnML('Unable to load #(1) #(2) API',
                    'dynamicdata','user');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNABLE_TO_LOAD',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }
    $fields = pnModAPIFunc('dynamicdata','user','getprop',
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
             $values[$id] = pnVarCleanFromInput('dd_'.$id);
        }
    }

    if (!pnModAPIFunc('dynamicdata', 'admin', 'update',
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
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'object id', 'admin', 'createhook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }
    if (!isset($extrainfo) || !is_array($extrainfo)) {
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'extrainfo', 'admin', 'createhook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($extrainfo['module'])) {
        $modname = pnModGetName();
    } else {
        $modname = $extrainfo['module'];
    }

    $modid = pnModGetIDFromName($modname);
    if (empty($modid)) {
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'admin', 'createhook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
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
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'admin', 'deletehook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    if (!pnModAPIFunc('dynamicdata', 'admin', 'delete',
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
    // Return the extra info
    return $extrainfo;

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
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'object ID (= module name)', 'admin', 'removehook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    $modid = pnModGetIDFromName($objectid);
    if (empty($modid)) {
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module ID', 'admin', 'removehook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    if (!pnSecAuthAction(0, "DynamicData::Item", "$modid::", ACCESS_DELETE)) {
        $msg = pnML('Not authorized to delete #(1) for #(2)',
                    'category items',pnVarPrepForStore($modid));
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    // Get database setup
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $dynamicprop = $pntable['dynamic_properties'];

    $sql = "SELECT pn_prop_id
            FROM $dynamicprop
            WHERE pn_prop_moduleid = " . pnVarPrepForStore($modid);

    $result = $dbconn->Execute($sql);

    if ($dbconn->ErrorNo() != 0) {
        $msg = pnML('Database error for #(1) function #(2)() in module #(3)',
                    'admin', 'removehook', 'dynamicdata');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
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

    $dynamicdata = $pntable['dynamic_data'];

    // Delete the item fields
    $sql = "DELETE FROM $dynamicdata
            WHERE pn_dd_propid IN (" . join(', ',$ids) . ")";
    $dbconn->Execute($sql);

    if ($dbconn->ErrorNo() != 0) {
        $msg = pnML('Database error for #(1) function #(2)() in module #(3)',
                    'admin', 'removehook', 'dynamicdata');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    // Delete the properties
    $sql = "DELETE FROM $dynamicprop
            WHERE pn_prop_id IN (" . join(', ',$ids) . ")";
    $dbconn->Execute($sql);

    if ($dbconn->ErrorNo() != 0) {
        $msg = pnML('Database error for #(1) function #(2)() in module #(3)',
                    'admin', 'removehook', 'dynamicdata');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
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
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'admin', 'createprop', 'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
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
    if (!pnSecAuthAction(0, 'DynamicData::Field', "$label:$type:", ACCESS_ADD)) {
        $msg = pnML('Not authorized to add #(1) items',
                    'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    // Get database setup - note that both pnDBGetConn() and pnDBGetTables()
    // return arrays but we handle them differently.  For pnDBGetConn()
    // we currently just want the first item, which is the official
    // database handle.  For pnDBGetTables() we want to keep the entire
    // tables array together for easy reference later on
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    // It's good practice to name the table and column definitions you
    // are getting - $table and $column don't cut it in more complex
    // modules
    $dynamicprop = $pntable['dynamic_properties'];

    // Get next ID in table - this is required prior to any insert that
    // uses a unique ID, and ensures that the ID generation is carried
    // out in a database-portable fashion
    $nextId = $dbconn->GenId($dynamicprop);

    // Add item - the formatting here is not mandatory, but it does make
    // the SQL statement relatively easy to read.  Also, separating out
    // the sql statement from the Execute() command allows for simpler
    // debug operation if it is ever needed
    $sql = "INSERT INTO $dynamicprop (
              pn_prop_id,
              pn_prop_moduleid,
              pn_prop_itemtype,
              pn_prop_label,
              pn_prop_dtype,
              pn_prop_default,
              pn_prop_validation)
            VALUES (
              $nextId,
              " . pnVarPrepForStore($modid) . ",
              " . pnVarPrepForStore($itemtype) . ",
              '" . pnVarPrepForStore($label) . "',
              " . pnVarPrepForStore($type) . ",
              '" . pnVarPrepForStore($default) . "',
              '" . pnVarPrepForStore($validation) . "')";
    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so raise an
    // appropriate exception
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $sql);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    // Get the ID of the item that we inserted.  It is possible, depending
    // on your database, that this is different from $nextId as obtained
    // above, so it is better to be safe than sorry in this situation
    $prop_id = $dbconn->PO_Insert_ID($dynamicprop, 'pn_prop_id');

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
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'admin', 'updateprop', 'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if (!pnSecAuthAction(0, 'DynamicData::Field', "$label:$type:$prop_id", ACCESS_EDIT)) {
        $msg = pnML('Not authorized to update #(1) items',
                    'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    // Get database setup - note that both pnDBGetConn() and pnDBGetTables()
    // return arrays but we handle them differently.  For pnDBGetConn()
    // we currently just want the first item, which is the official
    // database handle.  For pnDBGetTables() we want to keep the entire
    // tables array together for easy reference later on
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    // It's good practice to name the table and column definitions you
    // are getting - $table and $column don't cut it in more complex
    // modules
    $dynamicprop = $pntable['dynamic_properties'];

    $sql = "UPDATE $dynamicprop
            SET pn_prop_label = '" . pnVarPrepForStore($label) . "',
                pn_prop_dtype = " . pnVarPrepForStore($type);
    if (isset($default) && is_string($default)) {
        $sql .= ", pn_prop_default = '" . pnVarPrepForStore($default) . "'";
    }
    if (isset($validation) && is_string($validation)) {
        $sql .= ", pn_prop_validation = '" . pnVarPrepForStore($validation) . "'";
    }
// TODO: evaluate if we allow update those too
    if (isset($modid) && is_numeric($modid)) {
        $sql .= ", pn_prop_moduleid = " . pnVarPrepForStore($modid);
    }
    if (isset($itemtype) && is_numeric($itemtype)) {
        $sql .= ", pn_prop_itemtype = " . pnVarPrepForStore($itemtype);
    }

    $sql .= " WHERE pn_prop_id = " . pnVarPrepForStore($prop_id);

    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so raise an
    // appropriate exception
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $sql);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
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
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'admin', 'deleteprop', 'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
// TODO: check based on other arguments too
    if (!pnSecAuthAction(0, 'DynamicData::Field', "::$prop_id", ACCESS_DELETE)) {
        $msg = pnML('Not authorized to update #(1) items',
                    'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }

    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    // It's good practice to name the table and column definitions you
    // are getting - $table and $column don't cut it in more complex
    // modules
    $dynamicprop = $pntable['dynamic_properties'];

    $sql = "DELETE FROM $dynamicprop
            WHERE pn_prop_id = " . pnVarPrepForStore($prop_id);

    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so raise an
    // appropriate exception
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $sql);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    // delete all data too !
    $dynamicdata = $pntable['dynamic_data'];

    $sql = "DELETE FROM $dynamicdata
            WHERE pn_dd_propid = " . pnVarPrepForStore($prop_id);

    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so raise an
    // appropriate exception
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $sql);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    return true;
}


?>
