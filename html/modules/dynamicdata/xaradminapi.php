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

// TODO: verify that the data source is in dynamic_data
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
    $ids = array();
    foreach ($fields as $field) {
        $ids[] = $field['id'];
    }

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $dynamicdata = $xartable['dynamic_data'];

// TODO: don't delete if the data source is not in dynamic_data
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

// TODO: don't delete if the data source is not in dynamic_data

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
                    'module name', 'admin', $dd_function, 'dynamicdata');
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

// TODO: replace with something else
    $proptypes = xarModAPIFunc('dynamicdata','user','getproptypes');

    $values = array();
    $invalid = array();
    foreach ($fields as $label => $field) {
        // $values still uses property id instead of label, for create/update/delete in database
        $id = $field['id'];
        if (empty($proptypes[$field['type']]['name'])) {
            $invalid[] = xarML('Invalid #(1) for dynamic field #(2) in function #(3)() of module #(4)',
                               'property', $id, $dd_function, 'dynamicdata');
            continue;
        }

    // TODO: allow field label (sanitized !) here too ?
        if (isset($extrainfo['dd_'.$id])) {
            $value = $extrainfo['dd_'.$id];
        } else {
            $value = xarVarCleanFromInput('dd_'.$id);
        }

// TODO: add some real property validation here !!!
        $typename = $proptypes[$field['type']]['name'];
        switch ($typename) {
            case 'text':
            case 'textbox':
                if (!empty($value)) {
            // TODO: check size etc.
                }
                $values[$id] = $value;
                break;
            case 'textarea':
            case 'textarea_small':
            case 'textarea_medium':
            case 'textarea_large':
                if (!empty($value)) {
            // TODO: check size etc.
                }
                $values[$id] = $value;
                break;
        // TEST ONLY
            case 'webpage':
                if (!isset($options) || !is_array($options)) {
                    $options = array();
                // Load admin API for HTML file browser
                    if (!xarModAPILoad('articles', 'admin'))  return 'Unable to load articles admin API';
                    //$basedir = '/home/mikespub/www/pictures';
                    $basedir = $field['validation'];
                    $filetype = 'html?';
                    $files = xarModAPIFunc('articles','admin','browse',
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
                        $values[$id] = $option['id'];
                    }
                }
                if (empty($found)) {
                    $invalid[] = xarML('Invalid #(1) for dynamic field #(2) in function #(3)() of module #(4)',
                                       'selection', $id, $dd_function, 'dynamicdata');
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
                        $values[$id] = join('', @file($file['tmp_name']));
                    } else {
                        $invalid[] = xarML('Invalid #(1) for dynamic field #(2) in function #(3)() of module #(4)',
                                           'file upload', $id, $dd_function, 'dynamicdata');
                    }
                } else {
                    $values[$id] = '';
                }
                break;
            case 'url':
                if (!empty($value)) {
            // TODO: add some URL validation routine !
                }
                $values[$id] = $value;
                break;
            case 'image':
                if (!empty($value)) {
            // TODO: add some image validation routine !
                }
                $values[$id] = $value;
                break;
            case 'static':
                // TODO: check if we can leave this "as is"
                //    $values[$id] = $value;
                break;
            case 'hidden':
                // TODO: check if we can leave this "as is"
                //    $values[$id] = $value;
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
                    $invalid[] = xarML('Invalid #(1) for dynamic field #(2) in function #(3)() of module #(4)',
                                       'user', $id, $dd_function, 'dynamicdata');
                } else {
                    $values[$id] = $value;
                }
                break;
            case 'date':
            case 'calendar':
                // default time is now
                if (empty($value)) {
                    $values[$id] = time();
                } elseif (is_numeric($value)) {
                    $values[$id] = $value;
                } elseif (is_array($value) && !empty($value['year'])) {
                    if (!isset($value['sec'])) {
                        $value['sec'] = 0;
                    }
                    $values[$id] = mktime($value['hour'],$value['min'],$value['sec'],
                                          $value['mon'],$value['mday'],$value['year']);
                } else {
                    $invalid[] = xarML('Invalid #(1) for dynamic field #(2) in function #(3)() of module #(4)',
                                       'date', $id, $dd_function, 'dynamicdata');
                }
                break;
            case 'fieldtype':
                if (!empty($proptypes[$value]['name'])) {
                    $values[$id] = $value;
                } else {
                    $invalid[] = xarML('Invalid #(1) for dynamic field #(2) in function #(3)() of module #(4)',
                                       'property type', $id, $dd_function, 'dynamicdata');
                }
                break;
            default:
                $values[$id] = $value;
                break;
        }
    }
    if (count($invalid) > 0) {
        $msg = join(' + ',$invalid);
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    if (!xarModAPIFunc('dynamicdata', 'admin', ($dd_function == 'createhook') ? 'create' : 'update',
                      array('modid' => $modid,
                            'itemtype' => $itemtype,
                            'itemid' => $itemid,
                            'values'  => $values))) {
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    // update the extrainfo array
    foreach ($fields as $label => $field) {
        $id = $field['id'];
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
 * @param $args['source'] data source for the field (dynamic_data table or other)
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
// TODO: verify that the data source exists
    if (!isset($source) || !is_string($source)) {
        $source = 'dynamic_data';
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
              xar_prop_source,
              xar_prop_validation)
            VALUES (
              $nextId,
              " . xarVarPrepForStore($modid) . ",
              " . xarVarPrepForStore($itemtype) . ",
              '" . xarVarPrepForStore($label) . "',
              " . xarVarPrepForStore($type) . ",
              '" . xarVarPrepForStore($default) . "',
              '" . xarVarPrepForStore($source) . "',
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
 * @param $args['source'] data source of the field to update (optional)
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
        $name = $label;
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
            $output .= '<textarea name="'.$name.'" wrap="'.$wrap.'" rows="'.$rows.'" cols="'.$cols.'"'.$id.$tabindex.'>'.$value.'</textarea>';
            break;
    // TEST ONLY
        case 'webpage':
            if (!isset($options) || !is_array($options)) {
                $options = array();
            // Load admin API for HTML file browser
                if (!xarModAPILoad('articles', 'admin'))  return 'Unable to load articles admin API';
                //$basedir = '/home/mikespub/www/pictures';
                $basedir = 'd:/backup/mikespub/pictures';
                $filetype = 'html?';
                $files = xarModAPIFunc('articles','admin','browse',
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
            $output .= '<input type="text" name="'.$name.'" value="'.$value.'" size="'.$size.'"'.$id.$tabindex.' />';
            if (!empty($value)) {
                $output .= ' [ <a href="'.$value.'" target="preview">'.xarML('check').'</a> ]';
            }
            break;
        case 'image':
            if (empty($size)) {
                $size = 50;
            }
            $output .= '<input type="text" name="'.$name.'" value="'.$value.'" size="'.$size.'"'.$id.$tabindex.' />';
            if (!empty($value)) {
                $output .= ' [ <a href="'.$value.'" target="preview">'.xarML('show').'</a> ]';
            }
            $output .= '<br />// TODO: add image picker ?';
            break;
        case 'static':
            $output .= $value;
            break;
        case 'hidden':
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
            $output .= '<input type="text" name="'.$name.'" value="'.$value.'" size="'.$size.'"'.$id.$tabindex.' />';
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
        case 'module':
        // TODO: evaluate if we want some other output here
            $output .= $value;
            break;
        case 'itemtype':
        // TODO: evaluate if we want some other output here
            $output .= $value;
            break;
        case 'itemid':
        // TODO: evaluate if we want some other output here
            $output .= $value;
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
    if (isset($fields) && is_array($fields)) {
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

    $modid = xarModGetIDFromName($modname);
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
        foreach (array_keys($fields) as $label) {
            $fields[$label]['value'] = $fields[$label]['default'];
        }

    } else {
        // throw an exception if you can't edit this
        if (!xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:$itemid", ACCESS_EDIT)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
            return;
        }
        // we're dealing with a real item, so retrieve the property values
        $fields = xarModAPIFunc('dynamicdata','user','getall',
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
    $preview = xarVarCleanFromInput('preview');
    if (!empty($preview)) {
        foreach ($fields as $label => $field) {
            $id = $field['id'];
            $value = xarVarCleanFromInput('dd_'.$id);
            if (isset($value)) {
                $fields[$label]['value'] = $value;
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

    $modid = xarModGetIDFromName($modname);
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

    // try getting the item id list via input variables if necessary
    if (!isset($itemids)) {
        $itemids = xarVarCleanFromInput('itemids');
    }

// TODO: what kind of security checks do we want/need here ?
    // don't bother if you can't edit anything anyway
    if (!xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:", ACCESS_EDIT)) {
         return '';
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

    // retrieve the properties for this module / itemtype
    $fields = xarModAPIFunc('dynamicdata','user','getprop',
                            array('modid' => $modid,
                                  'itemtype' => $itemtype,
                                  'fieldlist' => $myfieldlist,
                                  'static' => $static));
    // create the label list + (try to) find the field containing the item id (if any)
    $labels = array();
    $itemidfield = '';
    foreach ($fields as $label => $field) {
        $labels[$label] = array('label' => $label);
        if ($field['type'] == 21) { // Item ID
            $itemidfield = $label;
            // take a wild guess at the parameter name this module expects
        // TODO: let the module tell us at installation ?
            $param = 'itemid';
            if (!empty($field['source']) && preg_match('/_([^_]+)$/',$field['source'],$matches)) {
                $param = $matches[1];
            }
        }
    }

    $items = array();
    if (empty($itemids)) {
        // TODO: we'll need to retrieve a bunch of itemids based on
        // some primary key of the module here -> use getstatic and relationships

    } else {
        if (!is_array($itemids)) {
            $itemids = explode(',',$itemids);
        }
        // TODO: get rid of the brute-force approach :-)
        foreach ($itemids as $itemid) {
            // skip items you can't edit anyway
            if (!xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:$itemid", ACCESS_EDIT)) {
                continue;
            }
            $fields = xarModAPIFunc('dynamicdata','user','getall',
                                    array('modid' => $modid,
                                          'itemtype' => $itemtype,
                                          'itemid' => $itemid,
                                          'fieldlist' => $myfieldlist,
                                          'static' => $static));
            if (!isset($fields) || $fields == false || count($fields) == 0) {
                continue;
            }
        // TODO: improve this + SECURITY !!!
            $options = array();
            if (!empty($itemidfield) && isset($fields[$itemidfield])) {
                $options[] = array('title' => xarML('View'),
                                   'link'  => xarModURL($modname,'user','display',
                                              array($param => $fields[$itemidfield]['value'],
                                                    'itemtype' => $itemtype)),
                                   'join'  => '');
                $options[] = array('title' => xarML('Edit'),
                                   'link'  => xarModURL($modname,'admin','modify',
                                              array($param => $fields[$itemidfield]['value'],
                                                    'itemtype' => $itemtype)),
                                   'join'  => '|');
                $options[] = array('title' => xarML('Delete'),
                                   'link'  => xarModURL($modname,'admin','delete',
                                              array($param => $fields[$itemidfield]['value'],
                                                    'itemtype' => $itemtype)),
                                   'join'  => '|');
            }
            $items[$itemid] = array('itemid' => $itemid,
                                    'fields' => $fields,
                                    'options' => $options);

        }
    }
    // TODO: improve this + SECURITY !!!
    $newlink = xarModURL($modname,'admin','new',
                         array('itemtype' => $itemtype));

    return xarTplModule('dynamicdata','admin','showlist',
                        array('items' => $items,
                              'labels' => $labels,
                              'newlink' => $newlink,
                              'layout' => $layout),
                        $template);
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
                              'title' => xarML('View and edit dynamic properties of modules'),
                              'label' => xarML('View Modules'));
    }

    if (xarSecAuthAction(0, 'Articles::', '::', ACCESS_ADMIN)) {
        $menulinks[] = Array('url'   => xarModURL('dynamicdata',
                                                  'admin',
                                                  'modifyconfig'),
                              'title' => xarML('Configure the property types'),
                              'label' => xarML('Property Types'));
    }

    if (empty($menulinks)){
        $menulinks = '';
    }

    return $menulinks;
}

//TODO: function to get a list of defined types
//TODO: function to create new types?
//TODO: make sure the constants in the CORE match the types (XARUSER_DUD_TYPE_CORE and friends)
//TODO: integrate with xarModGetVar, xarModSetVar

?>
