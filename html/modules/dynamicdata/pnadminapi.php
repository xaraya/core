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

/**
 * create a new dynamicdata item
 *
 * @author the DynamicData module development team
 * @param $args['name'] name of the item
 * @param $args['number'] number of the item
 * @returns int
 * @return dynamicdata item ID on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_create($args)
{

    // Get arguments from argument array - all arguments to this function
    // should be obtained from the $args array, getting them from other
    // places such as the environment is not allowed, as that makes
    // assumptions that will not hold in future versions of PostNuke
    extract($args);

    // Argument check - make sure that all required arguments are present
    // and in the right format, if not then set an appropriate error
    // message and return
    // Note : since we have several arguments we want to check here, we'll
    // report all those that are invalid at the same time...
    $invalid = array();
    if (!isset($name) || !is_string($name)) {
        $invalid[] = 'name';
    }
    if (!isset($number) || !is_numeric($number)) {
        $invalid[] = 'number';
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
    if (!pnSecAuthAction(0, 'DynamicData::Item', "$name::", ACCESS_ADD)) {
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
    $dynamicdatatable = $pntable['dynamicdata'];

    // Get next ID in table - this is required prior to any insert that
    // uses a unique ID, and ensures that the ID generation is carried
    // out in a database-portable fashion
    $nextId = $dbconn->GenId($dynamicdatatable);

    // Add item - the formatting here is not mandatory, but it does make
    // the SQL statement relatively easy to read.  Also, separating out
    // the sql statement from the Execute() command allows for simpler
    // debug operation if it is ever needed
    $sql = "INSERT INTO $dynamicdatatable (
              pn_exid,
              pn_name,
              pn_number)
            VALUES (
              $nextId,
              '" . pnVarPrepForStore($name) . "',
              " . pnvarPrepForStore($number) . ")";
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
    $exid = $dbconn->PO_Insert_ID($dynamicdatatable, 'pn_exid');

    // Let any hooks know that we have created a new item.  As this is a
    // create hook we're passing 'exid' as the extra info, which is the
    // argument that all of the other functions use to reference this
    // item
// TODO: evaluate
//    pnModCallHooks('item', 'create', $exid, 'exid');
    $item = $args;
    $item['module'] = 'dynamicdata';
    $item['itemid'] = $exid;
    pnModCallHooks('item', 'create', $exid, $item);

    // Return the id of the newly created item to the calling process
    return $exid;
}

/**
 * delete a dynamicdata item
 *
 * @author the DynamicData module development team
 * @param $args['exid'] ID of the item
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_delete($args)
{
    // Get arguments from argument array - all arguments to this function
    // should be obtained from the $args array, getting them from other
    // places such as the environment is not allowed, as that makes
    // assumptions that will not hold in future versions of PostNuke
    extract($args);

    // Argument check - make sure that all required arguments are present and
    // in the right format, if not then set an appropriate error message
    // and return
    if (!isset($exid) || !is_numeric($exid)) {
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'item ID', 'admin', 'delete', 'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Load API.  Note that this is loading the user API in addition to
    // the administration API, that is because the user API contains
    // the function to obtain item information which is the first thing
    // that we need to do.  If the API fails to load the raised exception is thrown back to PostNuke
    if (!pnModAPILoad('dynamicdata', 'user')) return; // throw back

    // The user API function is called.  This takes the item ID which
    // we obtained from the input and gets us the information on the
    // appropriate item.  If the item does not exist we post an appropriate
    // message and return
    $item = pnModAPIFunc('dynamicdata',
            'user',
            'get',
            array('exid' => $exid));

    // Check for exceptions
    if (!isset($item) && pnExceptionMajor() != PN_NO_EXCEPTION) return; // throw back

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing.
    // However, in this case we had to wait until we could obtain the item
    // name to complete the instance information so this is the first
    // chance we get to do the check
    if (!pnSecAuthAction(0, 'DynamicData::Item', "$item[name]::$exid", ACCESS_DELETE)) {
        $msg = pnML('Not authorized to delete #(1) item #(2)',
                    'DynamicData', pnVarPrepForStore($exid));
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
    $dynamicdatatable = $pntable['dynamicdata'];

    // Delete the item - the formatting here is not mandatory, but it does
    // make the SQL statement relatively easy to read.  Also, separating
    // out the sql statement from the Execute() command allows for simpler
    // debug operation if it is ever needed
    $sql = "DELETE FROM $dynamicdatatable
            WHERE pn_exid = " . pnVarPrepForStore($exid);
    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so raise an
    // appropriate exception
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $sql);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    // Let any hooks know that we have deleted an item.  As this is a
    // delete hook we're not passing any extra info
//    pnModCallHooks('item', 'delete', $exid, '');
    $item['module'] = 'dynamicdata';
    $item['itemid'] = $exid;
    pnModCallHooks('item', 'delete', $exid, $item);

    // Let the calling process know that we have finished successfully
    return true;
}

/**
 * update a dynamicdata item
 *
 * @author the DynamicData module development team
 * @param $args['exid'] the ID of the item
 * @param $args['name'] the new name of the item
 * @param $args['number'] the new number of the item
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_update($args)
{
    // Get arguments from argument array - all arguments to this function
    // should be obtained from the $args array, getting them from other
    // places such as the environment is not allowed, as that makes
    // assumptions that will not hold in future versions of PostNuke
    extract($args);

    // Argument check - make sure that all required arguments are present
    // and in the right format, if not then set an appropriate error
    // message and return
    // Note : since we have several arguments we want to check here, we'll
    // report all those that are invalid at the same time...
    $invalid = array();
    if (!isset($exid) || !is_numeric($exid)) {
        $invalid[] = 'item ID';
    }
    if (!isset($name) || !is_string($name)) {
        $invalid[] = 'name';
    }
    if (!isset($number) || !is_numeric($number)) {
        $invalid[] = 'number';
    }
    if (count($invalid) > 0) {
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'admin', 'update', 'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Load API.  Note that this is loading the user API in addition to
    // the administration API, that is because the user API contains
    // the function to obtain item information which is the first thing
    // that we need to do.  If the API fails to load the raised exception is thrown back to PostNuke
    if (!pnModAPILoad('dynamicdata', 'user')) return; // throw back

    // The user API function is called.  This takes the item ID which
    // we obtained from the input and gets us the information on the
    // appropriate item.  If the item does not exist we post an appropriate
    // message and return
    $item = pnModAPIFunc('dynamicdata',
            'user',
            'get',
            array('exid' => $exid));

    // Check for exceptions
    if (!isset($item) && pnExceptionMajor() != PN_NO_EXCEPTION) return; // throw back

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing.
    // However, in this case we had to wait until we could obtain the item
    // name to complete the instance information so this is the first
    // chance we get to do the check

    // Note that at this stage we have two sets of item information, the
    // pre-modification and the post-modification.  We need to check against
    // both of these to ensure that whoever is doing the modification has
    // suitable permissions to edit the item otherwise people can potentially
    // edit areas to which they do not have suitable access
    if (!pnSecAuthAction(0, 'DynamicData::Item', "$item[name]::$exid", ACCESS_EDIT)) {
        $msg = pnML('Not authorized to edit #(1) item #(2)',
                    'DynamicData', pnVarPrepForStore($exid));
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return;
    }
    if (!pnSecAuthAction(0, 'DynamicData::Item', "$name::$exid", ACCESS_EDIT)) {
        $msg = pnML('Not authorized to edit #(1) item #(2)',
                    'DynamicData', pnVarPrepForStore($exid));
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
    $dynamicdatatable = $pntable['dynamicdata'];

    // Update the item - the formatting here is not mandatory, but it does
    // make the SQL statement relatively easy to read.  Also, separating
    // out the sql statement from the Execute() command allows for simpler
    // debug operation if it is ever needed
    $sql = "UPDATE $dynamicdatatable
            SET pn_name = '" . pnVarPrepForStore($name) . "',
                pn_number = " . pnVarPrepForStore($number) . "
            WHERE pn_exid = " . pnVarPrepForStore($exid);
    $dbconn->Execute($sql);

    // Check for an error with the database code, and if so set an
    // appropriate error message and return
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $sql);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    // Let any hooks know that we have updated an item.  As this is an
    // update hook we're passing the updated $item array as the extra info
    $item['module'] = 'dynamicdata';
    $item['itemid'] = $exid;
    $item['name'] = $name;
    $item['number'] = $number;
    pnModCallHooks('item', 'update', $exid, $item);

    // Let the calling process know that we have finished successfully
    return true;
}

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

    $invalid = array();
    if (!isset($modid) || !is_numeric($modid)) {
        $invalid[] = 'module id';
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $itemtype = 0;
    }
    if (!isset($label) || !is_string($label)) {
        $invalid[] = 'label';
    }
    if (!isset($type) || !is_numeric($type)) {
        $invalid[] = 'type';
    }
    if (!isset($default) || !is_string($default)) {
        $default = '';
    }
    if (!isset($validation) || !is_string($validation)) {
        $validation = '';
    }
    if (count($invalid) > 0) {
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'admin', 'createprop', 'DynamicData');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if (!pnSecAuthAction(0, 'DynamicData::Fields', "$label:$type:", ACCESS_ADD)) {
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

// TODO...

// ----------------------------------------------------------------------
// Hook functions (admin API)
// ----------------------------------------------------------------------

/**
 * create field for an item - hook for ('item','create','API')
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

    if (!isset($extrainfo)) {
        $extrainfo = array();
    }

    // see if we have anything to do here (might be empty => return)
    if (empty($extrainfo['dd_*']) || count($extrainfo['dd_*']) == 0) {
        // try to get cids from input
        $cids = pnVarCleanFromInput('dd_*');
        if (empty($cids) || !is_array($cids)) {
            $extrainfo['dd_*'] = array();
            // no dynamicdata to link here
            return $extrainfo;
        } else {
            $extrainfo['dd_*'] = $cids;
        }
    }
    // get all valid cids
    $seencid = array();
    foreach ($extrainfo['dd_*'] as $cid) {
        if (empty($cid) || !is_numeric($cid)) {
            continue;
        }
        $seencid[$cid] = 1;
    }
    if (count($seencid) == 0) {
        // no dynamicdata to link here
        return $extrainfo;
    }
    $cids = array_keys($seencid);

    if (!isset($objectid) || !is_numeric($objectid)) {
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'object ID', 'admin', 'createhook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return false;
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
        return false;
    }

    if (!pnModAPIFunc('dynamicdata', 'admin', 'linkcat',
                      array('dd_*'  => $cids,
                            'iids'  => array($objectid),
                            'modid' => $modid))) {
        return false;
    }

    // Return the extra info
    return $extrainfo;
}

/**
 * update field for an item - hook for ('item','update','API')
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

    if (!isset($extrainfo)) {
        $extrainfo = array();
    }

    if (!isset($objectid) || !is_numeric($objectid)) {
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'object ID', 'admin', 'createhook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return false;
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
        return false;
    }

    // see what we have to do here (might be empty => we need to unlink)
    if (empty($extrainfo['dd_*'])) {
        // try to get cids from input
        $cids = pnVarCleanFromInput('dd_*');
        if (empty($cids) || !is_array($cids)) {
            $extrainfo['dd_*'] = array();
        } else {
            $extrainfo['dd_*'] = $cids;
        }
    }
    // get all valid cids for this item
    // Note : an item may *not* belong to the same cid twice
    $seencid = array();
    foreach ($extrainfo['dd_*'] as $cid) {
        if (empty($cid) || !is_numeric($cid)) {
            continue;
        }
        $seencid[$cid] = 1;
    }
    $cids = array_keys($seencid);

    if (count($cids) == 0) {
        if (!pnModAPIFunc('dynamicdata', 'admin', 'unlink',
                          array('iid' => $objectid,
                                'modid' => $modid))) {
            return false;
        }
    } elseif (!pnModAPIFunc('dynamicdata', 'admin', 'linkcat',
                            array('dd_*'  => $cids,
                                  'iids'  => array($objectid),
                                  'modid' => $modid,
                                  'clean_first' => true))) {
        return false;
    }

    // Return the extra info
    return $extrainfo;
}

/**
 * delete field for an item - hook for ('item','delete','API')
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

    if (!isset($extrainfo)) {
        $extrainfo = array();
    }

    if (!isset($objectid) || !is_numeric($objectid)) {
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'object ID', 'admin', 'deletehook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return false;
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
                    'module name', 'admin', 'deletehook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return false;
    }

    if (!pnModAPIFunc('dynamicdata', 'admin', 'unlink',
                      array('iid' => $objectid,
                            'modid' => $modid))) {
        return false;
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
    extract($args);

    if (!isset($extrainfo)) {
        $extrainfo = array();
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
                    'module name', 'admin', 'updateconfighook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return false;
    }

    // see what we have to do here (might be empty => we need to delete)
    if (empty($extrainfo['number_of_dynamicdata'])) {
        // try to get number of dynamicdata from input
        $numcats = (int) pnVarCleanFromInput('number_of_dynamicdata');
    } else {
        $numcats = $extrainfo['number_of_dynamicdata'];
    }
    if (empty($numcats) || !is_numeric($numcats)) {
        $numcats = 0;
    }
    if (!empty($extrainfo['itemtype'])) {
        pnModSetVar($modname,'number_of_dynamicdata.'.$extrainfo['itemtype'],$numcats);
    } else {
        pnModSetVar($modname,'number_of_dynamicdata',$numcats);
    }

    if (empty($extrainfo['dd_*']) || !is_array($extrainfo['dd_*'])) {
        // try to get cids from input
        $cids = pnVarCleanFromInput('dd_*');
        if (empty($cids) || !is_array($cids)) {
            $cids = array();
        }
    } else {
        $cids = $extrainfo['dd_*'];
    }
    // get all valid master cids for this module
    // Note : a module might have the same master cid twice (just in case...)
    $mastercid = array();
    foreach ($cids as $cid) {
        if (empty($cid) || !is_numeric($cid)) {
            continue;
        }
        $mastercids[] = $cid;
    }
    if (count($mastercids) > $numcats) {
        $mastercids = array_slice($mastercids,0,$numcats);
    }

    if ($numcats == 0 || count($mastercids) == 0) {
        if (!empty($extrainfo['itemtype'])) {
            pnModDelVar($modname,'mastercids.'.$extrainfo['itemtype']);
        } else {
            pnModDelVar($modname,'mastercids');
        }
    } else {
        if (!empty($extrainfo['itemtype'])) {
            pnModSetVar($modname,'mastercids.'.$extrainfo['itemtype'],
                        join(';',$mastercids));
        } else {
            pnModSetVar($modname,'mastercids',join(';',$mastercids));
        }
    }

    // Return the extra info
    return $extrainfo;
}

/**
 * delete all category links for a module - hook for ('module','remove','API')
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
        return false;
    }

    $modid = pnModGetIDFromName($objectid);
    if (empty($modid)) {
        $msg = pnML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module ID', 'admin', 'removehook', 'dynamicdata');
        pnExceptionSet(PN_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return false;
    }

    if (!pnSecAuthAction(0, "dynamicdata::item", ":$modid:", ACCESS_DELETE)) {
        $msg = pnML('Not authorized to delete #(1) for #(2)',
                    'category items',pnVarPrepForStore($modid));
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new SystemException($msg));
        return false;
    }

    // Get database setup
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();
    $dynamicdatafieldtable = $pntable['dynamicdata_field'];
    $dynamicdatafieldcolumn = &$pntable['dynamicdata_field_column'];

    // Delete the link
    $sql = "DELETE FROM $dynamicdatafieldtable
            WHERE $dynamicdatafieldcolumn[modid] = " . pnVarPrepForStore($modid);
    $dbconn->Execute($sql);

    if ($dbconn->ErrorNo() != 0) {
        $msg = pnML('Database error for #(1) function #(2)() in module #(3)',
                    'admin', 'removehook', 'dynamicdata');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return false;
    }

    // Return the extra info
    return $extrainfo;
}

?>
