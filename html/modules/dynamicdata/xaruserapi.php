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
 * @param $args['fieldlist'] array of field labels to retrieve (default is all)
 * @param $args['static'] include the static properties (= module tables) too (default no)
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
    $modinfo = xarModGetInfo($modid);

    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $invalid = array();
    if (!isset($modid) || !is_numeric($modid) || empty($modinfo['name'])) {
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

    // check the optional field list
    if (empty($fieldlist)) {
        $fieldlist = null;
    }

    // include the static properties (= module tables) too ?
    if (empty($static)) {
        $static = false;
    }

    // get all properties for this module / itemtype,
    // or only the properties mentioned in $fieldlist (in the right order, PHP willing)
    $fields = xarModAPIFunc('dynamicdata','user','getprop',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype,
                                 'fieldlist' => $fieldlist,
                                 'static' => $static));
    if (empty($fields) || count($fields) == 0) {
        return array();
    }

    // different processing depending on the data source
    $ids = array();
    $tables = array();
    $hooks = array();
    $functions = array();
    foreach ($fields as $label => $field) {
        // normal dynamic data field
        if ($field['source'] == 'dynamic_data') {
            // we still use the property ids here, because they're faster/more consistent
            $ids[] = $field['id'];

        // data managed by a hook/utility module
        } elseif ($field['source'] == 'hook module') {
            // check if this is a known module, based on the name of the property type
            $proptypes = xarModAPIFunc('dynamicdata','user','getproptypes');
            if (!empty($proptypes[$field['type']]['name'])) {
                $hooks[$proptypes[$field['type']]['name']] = $label;
            }

        // data managed by some user function (specified in validation for now)
        } elseif ($field['source'] == 'user function') {
            $functions[$field['validation']] = $label;

        // data field coming from another table
        } elseif (preg_match('/^(\w+)\.(\w+)$/', $field['source'], $matches)) {
            $table = $matches[1];
            $fieldname = $matches[2];
            $tables[$table][$fieldname] = $label;

        } else {
    // TODO: retrieve from other data sources than (known) tables as well
        }
    }

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    // retrieve properties from the dynamic_data table
    if (count($ids) > 0) {
        $dynamicdata = $xartable['dynamic_data'];
        $dynamicprop = $xartable['dynamic_properties'];

        $query = "SELECT xar_prop_label,
                         xar_dd_value
                    FROM $dynamicdata
               LEFT JOIN $dynamicprop
                      ON xar_dd_propid = xar_prop_id
                   WHERE xar_dd_propid IN (" . join(', ',$ids) . ")
                     AND xar_dd_itemid = " . xarVarPrepForStore($itemid);
        $result = $dbconn->Execute($query);

        if (!isset($result)) return;

        while (!$result->EOF) {
            list($label, $value) = $result->fields;
            if (isset($value)) {
                $fields[$label]['value'] = $value;
            }
            $result->MoveNext();
        }

        $result->Close();
    }

    $systemPrefix = xarDBGetSystemTablePrefix();
    $metaTable = $systemPrefix . '_tables';

    // retrieve properties from some known table field
// TODO: create UNION (or equivalent) to retrieve all relevant table fields at once
    foreach ($tables as $table => $fieldlist) {
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

        if (empty($field)) {
            continue;
        }
        $query = "SELECT " . join(', ', array_keys($fieldlist)) . "
                    FROM $table
                   WHERE $field = " . xarVarPrepForStore($itemid);

        $result = $dbconn->Execute($query);

        if (!isset($result)) return;

        if ($result->EOF) {
            continue;
        }
        $values = $result->fields;
        $result->Close();

        if (count($values) != count($fieldlist)) {
            continue;
        }
        foreach ($fieldlist as $field => $label) {
            $fields[$label]['value'] = array_shift($values);
        }
    }

    // retrieve properties via a hook module
    foreach ($hooks as $hook => $label) {
        if (xarModIsAvailable($hook) && xarModAPILoad($hook,'user')) {
        // TODO: find some more consistent way to do this !
            $fields[$label]['value'] = xarModAPIFunc($hook,'user','get',
                                                  array('modname' => $modinfo['name'],
                                                        'modid' => $modid,
                                                        'itemtype' => $itemtype,
                                                        'itemid' => $itemid,
                                                        'objectid' => $itemid));
        }
    }

    // retrieve properties via some user function
    foreach ($functions as $function => $label) {
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
            // try to load the module API
            if (!xarModAPILoad($fmod,$ftype)) {
                continue;
            }
            // try to invoke the function with some common parameters
        // TODO: standardize this, or allow the admin to specify the arguments
            $value = xarModAPIFunc($fmod,$ftype,$ffunc,
                                   array('modname' => $modinfo['name'],
                                         'modid' => $modid,
                                         'itemtype' => $itemtype,
                                         'itemid' => $itemid,
                                         'objectid' => $itemid));
            // see if we got something interesting in return
            if (isset($value)) {
                $fields[$label]['value'] = $value;
            }
        } else {
            // try to load the module GUI
            if (!xarModLoad($fmod,$ftype)) {
                continue;
            }
            // try to invoke the function with some common parameters
        // TODO: standardize this, or allow the admin to specify the arguments
            $value = xarModFunc($fmod,$ftype,$ffunc,
                                array('modname' => $modinfo['name'],
                                      'modid' => $modid,
                                      'itemtype' => $itemtype,
                                      'itemid' => $itemid,
                                      'objectid' => $itemid));
            // see if we got something interesting in return
            if (isset($value)) {
                $fields[$label]['value'] = $value;
            }
        }
    }

// TODO: retrieve from other data sources as well

    foreach ($fields as $label => $field) {
        if (xarSecAuthAction(0, 'DynamicData::Field', $field['label'].':'.$field['type'].':'.$field['id'], ACCESS_READ)) {
            if (!isset($field['value'])) {
                $fields[$label]['value'] = $fields[$label]['default'];
            }
        } else {
            unset($fields[$label]);
        }
    }

    return $fields;
}

/**
 * get a specific item field
// TODO: update this with all the new stuff
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

// TODO: retrieve from other data sources as well
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
 * @param $args['fieldlist'] array of field labels to retrieve (default is all)
 * @param $args['static'] include the static properties (= module tables) too (default no)
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

    // check the optional field list
    if (empty($fieldlist)) {
        $fieldlist = null;
    }

    // include the static properties (= module tables) too ?
    if (empty($static)) {
        $static = false;
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

    if (empty($static) && isset($propertybag["$modid:$itemtype"])) {
        if (empty($fieldlist)) {
            return $propertybag["$modid:$itemtype"];
        } else {
            $myfields = array();
            foreach ($fieldlist as $label) {
                if (isset($propertybag["$modid:$itemtype"][$label])) {
                    $myfields[$label] = $propertybag["$modid:$itemtype"][$label];
                }
            }
            return $myfields;
        }
    }

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $dynamicprop = $xartable['dynamic_properties'];

    $sql = "SELECT xar_prop_label,
                   xar_prop_dtype,
                   xar_prop_id,
                   xar_prop_default,
                   xar_prop_source,
                   xar_prop_validation
            FROM $dynamicprop
            WHERE xar_prop_moduleid = " . xarVarPrepForStore($modid) . "
              AND xar_prop_itemtype = " . xarVarPrepForStore($itemtype) . "
            ORDER BY xar_prop_id ASC";

    $result = $dbconn->Execute($sql);

    if ($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $sql);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    $fields = array();

    while (!$result->EOF) {
        list($label, $type, $id, $default, $source, $validation) = $result->fields;
        if (xarSecAuthAction(0, 'DynamicData::Field', "$label:$type:$id", ACCESS_READ)) {
            $fields[$label] = array('label' => $label,
                                    'type' => $type,
                                    'id' => $id,
                                    'default' => $default,
                                    'source' => $source,
                                    'validation' => $validation);
        }
        $result->MoveNext();
    }

    $result->Close();

    if (!empty($static)) {
        // get the list of static properties for this module
        $staticlist = xarModAPIFunc('dynamicdata','user','getstatic',
                                    array('modid' => $modid,
                                          'itemtype' => $itemtype));
// TODO: watch out for conflicting property ids ?
        $fields = array_merge($staticlist,$fields);
    }

    if (empty($static)) {
        $propertybag["$modid:$itemtype"] = $fields;
    }
    if (empty($fieldlist)) {
            return $fields;
    } else {
        $myfields = array();
        // this should return the fields in the right order, normally
        foreach ($fieldlist as $label) {
            if (isset($fields[$label])) {
                $myfields[$label] = $fields[$label];
            }
        }
        return $myfields;
    }
}

/**
 * get the list of modules + itemtypes for which dynamic properties are defined
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
              GROUP BY xar_prop_moduleid, xar_prop_itemtype
              ORDER BY xar_prop_moduleid ASC, xar_prop_itemtype ASC";

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
 * get possible data sources (// TODO: for a module ?)
 *
 * @author the DynamicData module development team
 * @param $args['module'] module name of the item fields, or (// TODO: for a module ?)
 * @param $args['modid'] module id of the item field to get (// TODO: for a module ?)
 * @param $args['itemtype'] item type of the item field to get (// TODO: for a module ?)
 * @returns mixed
 * @return list of possible data sources, or false on failure
 * @raise BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getsources($args)
{
    extract($args);

/*
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
*/

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

    $result = $dbconn->Execute($query);

    if (!isset($result)) return;

    $sources = array();

    // default data source is dynamic data
    $sources[] = 'dynamic_data';

// TODO: re-evaluate this once we're further along
    // hook modules manage their own data
    $sources[] = 'hook module';

    // hook modules manage their own data
    $sources[] = 'user function';

    // add the list of table + field
    while (!$result->EOF) {
        list($table, $field, $type, $size) = $result->fields;
    // TODO: what kind of security checks do we want/need here ?
        //if (xarSecAuthAction(0, 'DynamicData::Field', "$label:$type:$id", ACCESS_READ)) {
        //}
        $sources[] = "$table.$field";
        $result->MoveNext();
    }

    $result->Close();

    return $sources;
}

/**
 * (try to) get the "static" properties, corresponding to fields in dedicated
 * tables for this module + item type
// TODO: allow modules to specify their own properties
 *
 * @author the DynamicData module development team
 * @param $args['module'] module name of the item fields, or
 * @param $args['modid'] module id of the item field to get
 * @param $args['itemtype'] item type of the item field to get
 * @returns mixed
 * @return value of the field, or false on failure
 * @raise BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getstatic($args)
{
    static $propertybag = array();

    extract($args);

    if (empty($modid) && !empty($module)) {
        $modid = xarModGetIDFromName($module);
    }
    $modinfo = xarModGetInfo($modid);
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $invalid = array();
    if (!isset($modid) || !is_numeric($modid) || empty($modinfo['name'])) {
        $invalid[] = 'module id ' . xarVarPrepForDisplay($modid);
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'user', 'getstatic', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (isset($propertybag["$modid:$itemtype"])) {
        return $propertybag["$modid:$itemtype"];
    }

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

// TODO: support site tables as well
    $systemPrefix = xarDBGetSystemTablePrefix();
    $metaTable = $systemPrefix . '_tables';

// TODO: allow modules to define which tables they use too
    if ($modinfo['name'] == 'dynamicdata') {
        // let's cheat a little for DD, because otherwise it won't find any tables :)
        $modinfo['name'] = 'dynamic';
    }
    // try to get any table that starts with prefix_modulename
    $query = "SELECT xar_tableid,
                     xar_table,
                     xar_field,
                     xar_type,
                     xar_size,
                     xar_default,
                     xar_increment,
                     xar_primary_key
              FROM $metaTable 
              WHERE xar_table LIKE '" . xarVarPrepForStore($systemPrefix)
                                   . '_' . xarVarPrepForStore($modinfo['name']) . '%' . "'
              ORDER BY xar_tableid ASC";

    $result =& $dbconn->Execute($query);

    if (!isset($result)) return;

    $static = array();

    // add the list of table + field
    while (!$result->EOF) {
        list($id,$table, $field, $datatype, $size, $default,$increment,$primary_key) = $result->fields;
    // TODO: what kind of security checks do we want/need here ?
        //if (xarSecAuthAction(0, 'DynamicData::Field', "$label:$type:$id", ACCESS_READ)) {
        //}

        // assign some default label for now, by removing the first part (xar_)
// TODO: let modules define this
        $label = ucfirst(preg_replace('/^[^_]+_/','',$field));

        // (try to) assign some default property type for now
        // = obviously limited to basic data types in this case
        switch ($datatype) {
            case 'char':
            case 'varchar':
                $proptype = 2; // Text Box
                break;
            case 'integer':
                $proptype = 15; // Number Box
                break;
            case 'float':
                $proptype = 17; // Number Box (float)
                break;
            case 'boolean':
                $proptype = 14; // Checkbox
                break;
            case 'date':
            case 'datetime':
            case 'timestamp':
                $proptype = 8; // Calendar
                break;
            case 'text':
                $proptype = 4; // Medium Text Area
                break;
            case 'blob':       // caution, could be binary too !
                $proptype = 4; // Medium Text Area
                break;
            default:
                $proptype = 1; // Static Text
                break;
        }

        // assign some default validation for now
// TODO: improve this based on property type validations
        $validation = $datatype;
        $validation .= empty($size) ? '' : ' (' . $size . ')';

        // try to figure out if it's the item id
// TODO: let modules define this
        if (!empty($increment) || !empty($primary_key)) {
            // not allowed to modify primary key !
            $proptype = 21; // Item ID
        }

        $static[$label] = array('label' => $label,
                                'type' => $proptype,
                                'id' => $id,
                                'default' => $default,
                                'source' => $table . '.' . $field,
                                'validation' => $validation,
                                'increment' => $increment);
        $result->MoveNext();
    }

    $result->Close();

    $propertybag["$modid:$itemtype"] = $static;
    return $static;
}

/**
 * (try to) get the relationships between a particular module and others (e.g. hooks)
// TODO: allow other kinds of relationships than hooks
// TODO: allow modules to specify their own relationships
 *
 * @author the DynamicData module development team
 * @param $args['module'] module name of the item fields, or
 * @param $args['modid'] module id of the item field to get
 * @param $args['itemtype'] item type of the item field to get
 * @returns mixed
 * @return value of the field, or false on failure
 * @raise BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getrelations($args)
{
    static $propertybag = array();

    extract($args);

    if (empty($modid) && !empty($module)) {
        $modid = xarModGetIDFromName($module);
    }
    $modinfo = xarModGetInfo($modid);
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $invalid = array();
    if (!isset($modid) || !is_numeric($modid) || empty($modinfo['name'])) {
        $invalid[] = 'module id ' . xarVarPrepForDisplay($modid);
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'user', 'getstatic', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (isset($propertybag["$modid:$itemtype"])) {
        return $propertybag["$modid:$itemtype"];
    }

    // get the list of static properties for this module
    $static = xarModAPIFunc('dynamicdata','user','getstatic',
                            array('modid' => $modid,
                                  'itemtype' => $itemtype));

    // get the list of hook modules that are enabled for this module
// TODO: get all hooks types, not only item display hooks
//    $hooklist = xarModGetHookList($modinfo['name'],'item','display');
    $hooklist = array_merge(xarModGetHookList($modinfo['name'],'item','display'),
                            xarModGetHookList($modinfo['name'],'item','update'));
    $modlist = array();
    foreach ($hooklist as $hook) {
        $modlist[$hook['module']] = 1;
    }

    $relations = array();
    if (count($modlist) > 0) {
        // first look for the (possible) item id field in the current module
        $itemid = '???';
        foreach ($static as $field) {
            if ($field['type'] == 21) { // Item ID
                $itemid = $field['source'];
                break;
            }
        }
        // for each enabled hook module
        foreach ($modlist as $mod => $val) {
            // get the list of static properties for this hook module
            $modstatic = xarModAPIFunc('dynamicdata','user','getstatic',
                                       array('modid' => xarModGetIDFromName($mod)));
                                       // skip this for now
                                       //      'itemtype' => $itemtype));
        // TODO: automatically find the link(s) on module, item type, item id etc.
        //       or should hook modules tell us that ?
            $links = array();
            foreach ($modstatic as $field) {

        /* for hook modules, those should define the fields *relating to* other modules (not their own item ids etc.)
                // try predefined field types first
                if ($field['type'] == 19) { // Module
                    $links[] = array('from' => $field['source'], 'to' => $modid, 'type' => 'moduleid');
                } elseif ($field['type'] == 20) { // Item Type
                    $links[] = array('from' => $field['source'], 'to' => $itemtype, 'type' => 'itemtype');
                } elseif ($field['type'] == 21) { // Item ID
                    $links[] = array('from' => $field['source'], 'to' => $itemid, 'type' => 'itemid');
        */
                // try to guess based on field names *cough*
                // link on module name/id
                if (preg_match('/_module$/',$field['source'])) {
                    $links[] = array('from' => $field['source'], 'to' => $modinfo['name'], 'type' => 'modulename');
                } elseif (preg_match('/_moduleid$/',$field['source'])) {
                    $links[] = array('from' => $field['source'], 'to' => $modid, 'type' => 'moduleid');
                } elseif (preg_match('/_modid$/',$field['source'])) {
                    $links[] = array('from' => $field['source'], 'to' => $modid, 'type' => 'moduleid');

                // link on item type
                } elseif (preg_match('/_itemtype$/',$field['source'])) {
                    $links[] = array('from' => $field['source'], 'to' => $itemtype, 'type' => 'itemtype');

                // link on item id
                } elseif (preg_match('/_itemid$/',$field['source'])) {
                    $links[] = array('from' => $field['source'], 'to' => $itemid, 'type' => 'itemid');
                } elseif (preg_match('/_iid$/',$field['source'])) {
                    $links[] = array('from' => $field['source'], 'to' => $itemid, 'type' => 'itemid');
                }
            }
            $relations[] = array('module' => $mod,
                                 'fields' => $modstatic,
                                 'links'  => $links);
        }
    }
    return $relations;
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
    $proptypes[1] = array(
                          'id'         => 1,
                          'name'       => 'static',
                          'label'      => 'Static Text',
                          'format'     => '1',
                          'validation' => '',
                          // ...
                         );
    $proptypes[2] = array(
                          'id'         => 2,
                          'name'       => 'textbox',
                          'label'      => 'Text Box',
                          'format'     => '2',
                          'validation' => '',
                          // ...
                         );
    $proptypes[3] = array(
                          'id'         => 3,
                          'name'       => 'textarea_small',
                          'label'      => 'Small Text Area',
                          'format'     => '3',
                          'validation' => '',
                          // ...
                         );
    $proptypes[4] = array(
                          'id'         => 4,
                          'name'       => 'textarea_medium',
                          'label'      => 'Medium Text Area',
                          'format'     => '4',
                          'validation' => '',
                          // ...
                         );
    $proptypes[5] = array(
                          'id'         => 5,
                          'name'       => 'textarea_large',
                          'label'      => 'Large Text Area',
                          'format'     => '5',
                          'validation' => '',
                          // ...
                         );
    $proptypes[6] = array(
                          'id'         => 6,
                          'name'       => 'dropdown',
                          'label'      => 'Dropdown List',
                          'format'     => '6',
                          'validation' => '',
                          // ...
                         );
    $proptypes[7] = array(
                          'id'         => 7,
                          'name'       => 'username',
                          'label'      => 'Username',
                          'format'     => '7',
                          'validation' => '',
                          // ...
                         );
    $proptypes[8] = array(
                          'id'         => 8,
                          'name'       => 'calendar',
                          'label'      => 'Calendar',
                          'format'     => '8',
                          'validation' => '',
                          // ...
                         );
    $proptypes[9] = array(
                          'id'         => 9,
                          'name'       => 'fileupload',
                          'label'      => 'File Upload',
                          'format'     => '9',
                          'validation' => '',
                          // ...
                         );
    $proptypes[10] = array(
                          'id'         => 10,
                          'name'       => 'status',
                          'label'      => 'Status',
                          'format'     => '10',
                          'validation' => '',
                          // ...
                         );
    $proptypes[11] = array(
                          'id'         => 11,
                          'name'       => 'url',
                          'label'      => 'URL',
                          'format'     => '11',
                          'validation' => '',
                          // ...
                         );
    $proptypes[12] = array(
                          'id'         => 12,
                          'name'       => 'image',
                          'label'      => 'Image',
                          'format'     => '12',
                          'validation' => '',
                          // ...
                         );
    $proptypes[13] = array(
                          'id'         => 13,
                          'name'       => 'webpage',
                          'label'      => 'HTML Page',
                          'format'     => '13',
                          'validation' => '',
                          // ...
                         );
    $proptypes[14] = array(
                          'id'         => 14,
                          'name'       => 'checkbox',
                          'label'      => 'Checkbox',
                          'format'     => '14',
                          'validation' => '',
                          // ...
                         );
    $proptypes[15] = array(
                          'id'         => 15,
                          'name'       => 'integerbox',
                          'label'      => 'Number Box',
                          'format'     => '15',
                          'validation' => '',
                          // ...
                         );
    $proptypes[16] = array(
                          'id'         => 16,
                          'name'       => 'integerlist',
                          'label'      => 'Number List',
                          'format'     => '16',
                          'validation' => '',
                          // ...
                         );
    $proptypes[17] = array(
                          'id'         => 17,
                          'name'       => 'floatbox',
                          'label'      => 'Number Box (float)',
                          'format'     => '17',
                          'validation' => '',
                          // ...
                         );
    $proptypes[18] = array(
                          'id'         => 18,
                          'name'       => 'hidden',
                          'label'      => 'Hidden',
                          'format'     => '18',
                          'validation' => '',
                          // ...
                         );
// handy for relationships, URLs etc.
    $proptypes[19] = array(
                          'id'         => 19,
                          'name'       => 'module',
                          'label'      => 'Module',
                          'format'     => '19',
                          'validation' => '',
                          // ...
                         );
    $proptypes[20] = array(
                          'id'         => 20,
                          'name'       => 'itemtype',
                          'label'      => 'Item Type',
                          'format'     => '20',
                          'validation' => '',
                          // ...
                         );
    $proptypes[21] = array(
                          'id'         => 21,
                          'name'       => 'itemid',
                          'label'      => 'Item ID',
                          'format'     => '21',
                          'validation' => '',
                          // ...
                         );

    // add some property types supported by utility modules
    if (xarModIsAvailable('categories') && xarModAPILoad('categories','user')) {
        $proptypes[100] = array(
                                'id'         => 100,
                                'name'       => 'categories',
                                'label'      => 'Categories',
                                'format'     => '100',
                                'validation' => '',
                                'source'     => 'hook module',
                                // ...
                              );
    }
    if (xarModIsAvailable('hitcount') && xarModAPILoad('hitcount','user')) {
        $proptypes[101] = array(
                                'id'         => 101,
                                'name'       => 'hitcount',
                                'label'      => 'Hit Count',
                                'format'     => '101',
                                'validation' => '',
                                'source'     => 'hook module',
                                // ...
                               );
    }
    if (xarModIsAvailable('ratings') && xarModAPILoad('ratings','user')) {
        $proptypes[102] = array(
                                'id'         => 102,
                                'name'       => 'ratings',
                                'label'      => 'Rating',
                                'format'     => '102',
                                'validation' => '',
                                'source'     => 'hook module',
                                // ...
                               );
    }
    if (xarModIsAvailable('comments') && xarModAPILoad('comments','user')) {
        $proptypes[103] = array(
                                'id'         => 103,
                                'name'       => 'comments',
                                'label'      => 'Comments',
                                'format'     => '103',
                                'validation' => '',
                                'source'     => 'hook module',
                                // ...
                               );
    }
// trick : retrieve the number of comments via a user function here
    if (xarModIsAvailable('comments') && xarModAPILoad('comments','user')) {
        $proptypes[104] = array(
                                'id'         => 104,
                                'name'       => 'numcomments',
                                'label'      => '# of Comments',
                                'format'     => '104',
                                'validation' => 'comments_userapi_get_count',
                                'source'     => 'user function',
                                // ...
                               );
    }
// TODO: replace fileupload above with this one someday ?
/*
    if (xarModIsAvailable('uploads') && xarModAPILoad('uploads','user')) {
        $proptypes[105] = array(
                                'id'         => 105,
                                'name'       => 'uploads',
                                'label'      => 'Upload',
                                'format'     => '105',
                                'validation' => '',
                                'source'     => 'hook module',
                                // ...
                               );
    }
*/

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
// BL user tags (output, display & view)
// ----------------------------------------------------------------------

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * Handle <xar:data-output ...> form field tags
 * Format : <xar:data-output field="$field" /> with $field an array
 *                                             containing the type, label, value, ...
 *       or <xar:data-output label="thisname" type="thattype" value="$val" ... />
 * 
 * @param $args array containing the input field definition or the type, label, value, ...
 * @returns string
 * @return the PHP code needed to invoke showoutput() in the BL template
 */
function dynamicdata_userapi_handleOutputTag($args)
{
    $out = "xarModAPILoad('dynamicdata','user');
echo xarModAPIFunc('dynamicdata',
                   'user',
                   'showoutput',\n";
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
 * show some predefined output field in a template
 * 
 * @param $args array containing the definition of the field (type, name, value, ...)
 * @returns string
 * @return string containing the HTML (or other) text to output in the BL template
 */
function dynamicdata_userapi_showoutput($args)
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

// TODO: what kind of security checks do we want/need here ?
    //if (xarSecAuthAction(0, 'DynamicData::Field', "$label:$type:$id", ACCESS_READ)) {
    //}

    $output = '';
    switch ($typename) {
        case 'text':
        case 'textbox':
            $output .= xarVarPrepHTMLDisplay($value);
            break;
        case 'textarea':
        case 'textarea_small':
        case 'textarea_medium':
        case 'textarea_large':
            $output .= xarVarPrepHTMLDisplay($value);
            break;
    // TEST ONLY
        case 'webpage':
            //$basedir = '/home/mikespub/www/pictures';
            $basedir = 'd:/backup/mikespub/pictures';
            $filetype = 'html?';
            if (!empty($value) &&
                preg_match('/^[a-zA-Z0-9_\/\\\:.-]+$/',$value) &&
                preg_match("/$filetype$/",$value) &&
                file_exists($value) &&
                is_file($value)) {
                $output .= join('', file($value));
            }
                    $output .= xarVarPrepForDisplay($value);
            break;
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
        // TODO: support multiple selection
            $join = '';
            foreach ($options as $option) {
                if ($option['id'] == $selected) {
                    $output .= $join;
                    $output .= xarVarPrepForDisplay($option['name']);
                    $join = ' | ';
                }
            }
            break;
        case 'file':
        case 'fileupload':
        // TODO: link to download file ?
            break;
        case 'url':
        // TODO: use redirect function here ?
            if (!empty($value)) {
                $value = xarVarPrepHTMLDisplay($value);
        // TODO: add alt/title here ?
                $output .= '<a href="'.$value.'">'.$value.'</a>';
            }
            break;
        case 'image':
            if (!empty($value)) {
                $value = xarVarPrepHTMLDisplay($value);
        // TODO: add size/alt here ?
                $output .= '<img src="' . $value . '">';
            }
            break;
        case 'static':
            $output .= xarVarPrepForDisplay($value);
            break;
        case 'hidden':
            $output .= '';
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
                $output .= '<a href="'.xarModURL('users','user','display',
                                                    array('uid' => $value))
                           . '">'.xarVarPrepForDisplay($user).'</a>';
            } else {
                $output .= xarVarPrepForDisplay($user);
            }
            break;
        case 'date':
        case 'calendar':
            if (empty($value)) {
                $value = time();
            }
        // TODO: adapt to local/user time !
            $output .= strftime('%a, %d %B %Y %H:%M:%S %Z', $value);
            break;
        case 'fieldtype':
            if (!empty($value) && !empty($proptypes[$value]['label'])) {
                $output .= $proptypes[$value]['label'];
            }
            break;
        case 'checkbox':
        // TODO: allow different values here, and verify $checked ?
            if (empty($value)) {
                $output .= xarML('no');
            } else {
                $output .= xarML('yes');
            }
            break;
        case 'integerbox':
            $output .= $value;
            break;
        case 'integerlist':
            $output .= $value;
            break;
        case 'floatbox':
        // TODO: allow precision etc.
            if (isset($precision) && is_numeric($precision)) {
                $output .= sprintf("%.".$precision."f",$value);
            } else {
                $output .= $value;
            }
            break;

    // output from some common hook/utility modules
        case 'categories':
            $output .= '// TODO: show categories for this item';
            break;
        case 'comments':
            $output .= '// TODO: show comments for this item';
            break;
        case 'numcomments':
            // via comments_userapi_get_count()
            if (empty($value)) {
                $output .= xarML('no comments');
            } elseif ($value == 1) {
                $output .= xarML('one comment');
            } else {
                $output .= xarML('#(1) comments',$value);
            }
            break;
        case 'hitcount':
// TODO: this doesn't increase the display count yet
            if (!empty($value)) {
                $output .= xarML('(#(1) Reads)', $value);
/* value retrieved in getall now
            } elseif (empty($modname) || empty($itemid)) {
                $output .= xarML('Please provide "modname" and "itemid" as parameters in the data-input tag');
            } elseif (xarModAPILoad('hitcount','user')) {
                $value = xarModAPIFunc('hitcount','user','get',
                                       array('modname' => $modname,
                                             'itemid' => $itemid));
                $output .= xarML('(#(1) Reads', $value);
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
 * Handle <xar:data-display ...> display tags
 * Format : <xar:data-display module="123" itemtype="0" itemid="555" fieldlist="$fieldlist" static="yes" .../>
 *       or <xar:data-display fields="$fields" ... />
 * 
 * @param $args array containing the item that you want to display, or fields
 * @returns string
 * @return the PHP code needed to invoke showdisplay() in the BL template
 */
function dynamicdata_userapi_handleDisplayTag($args)
{
    $out = "xarModAPILoad('dynamicdata','user');
echo xarModAPIFunc('dynamicdata',
                   'user',
                   'showdisplay',\n";
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
 * display an item in a template
 * 
 * @param $args array containing the item or fields to show
 * @returns string
 * @return string containing the HTML (or other) text to output in the BL template
 */
function dynamicdata_userapi_showdisplay($args)
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
        return xarTplModule('dynamicdata','user','showdisplay',
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
                    'module name', 'user', 'showform', 'dynamicdata');
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

// TODO: what kind of security checks do we want/need here ?
    if (!xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:$itemid", ACCESS_READ)) {
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

    if (empty($itemid)) {
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
            $value = xarVarCleanFromInput('dd_'.$field['id']);
            if (isset($value)) {
                $fields[$label]['value'] = $value;
            }
        }
    }

    return xarTplModule('dynamicdata','user','showdisplay',
                        array('fields' => $fields,
                              'layout' => $layout),
                        $template);
}

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * Handle <xar:data-view ...> view tags
 * Format : <xar:data-view module="123" itemtype="0" itemids="$idlist" fieldlist="$fieldlist" static="yes" .../>
 *       or <xar:data-view items="$items" labels="$labels" ... />
 * 
 * @param $args array containing the items that you want to display, or fields
 * @returns string
 * @return the PHP code needed to invoke showview() in the BL template
 */
function dynamicdata_userapi_handleViewTag($args)
{
    $out = "xarModAPILoad('dynamicdata','user');
echo xarModAPIFunc('dynamicdata',
                   'user',
                   'showview',\n";
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
function dynamicdata_userapi_showview($args)
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
        return xarTplModule('dynamicdata','user','showview',
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
                    'module name', 'user', 'showform', 'dynamicdata');
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
    if (!xarSecAuthAction(0, 'DynamicData::Item', "$modid:$itemtype:", ACCESS_OVERVIEW)) {
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
            $fields = xarModAPIFunc('dynamicdata','user','getall',
                                    array('modid' => $modid,
                                          'itemtype' => $itemtype,
                                          'itemid' => $itemid,
                                          'fieldlist' => $myfieldlist,
                                          'static' => $static));
            if (!isset($fields) || $fields == false || count($fields) == 0) {
                continue;
            }
        // TODO: improve this + security
            $displaylink = '';
            if (!empty($itemidfield) && isset($fields[$itemidfield])) {
                $displaylink = xarModURL($modname,'user','display',
                                         array($param => $fields[$itemidfield]['value'],
                                               'itemtype' => $itemtype));
            }
            $items[] = array('fields' => $fields, 'displaylink' => $displaylink);
        }
    }

    return xarTplModule('dynamicdata','user','showview',
                        array('items' => $items,
                              'labels' => $labels,
                              'layout' => $layout),
                        $template);
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
