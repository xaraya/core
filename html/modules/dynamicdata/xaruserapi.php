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

    $fields = xarModAPIFunc('dynamicdata','user','getprop',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype));
    if (empty($fields) || count($fields) == 0) {
        return array();
    }

    // different processing depending on the data source
    $ids = array();
    $tables = array();
    $hooks = array();
    foreach ($fields as $id => $field) {
        // normal dynamic data field
        if ($field['source'] == 'dynamic_data') {
            $ids[] = $id;
        // data field coming from another table
        } elseif (preg_match('/^(\w+)\.(\w+)$/', $field['source'], $matches)) {
            $table = $matches[1];
            $fieldname = $matches[2];
            $tables[$table][$fieldname] = $id;
        } elseif ($field['source'] == 'hook module') {
            // check if this is a known module, based on the name of the property type
            $proptypes = xarModAPIFunc('dynamicdata','user','getproptypes');
            if (!empty($proptypes[$field['type']]['name'])) {
                $hooks[$proptypes[$field['type']]['name']] = $id;
            }
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

        $query = "SELECT xar_dd_propid,
                         xar_dd_value
                    FROM $dynamicdata
                   WHERE xar_dd_propid IN (" . join(', ',$ids) . ")
                     AND xar_dd_itemid = " . xarVarPrepForStore($itemid);
        $result = $dbconn->Execute($query);

        if (!isset($result)) return;

        while (!$result->EOF) {
            list($id, $value) = $result->fields;
            if (isset($value)) {
                $fields[$id]['value'] = $value;
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
        foreach ($fieldlist as $field => $id) {
            $fields[$id]['value'] = array_shift($values);
        }
    }

    // retrieve properties via a hook module
    foreach ($hooks as $hook => $id) {
        if (xarModIsAvailable($hook) && xarModAPILoad($hook,'user')) {
        // TODO: find some more consistent way to do this !
            $fields[$id]['value'] = xarModAPIFunc($hook,'user','get',
                                                  array('modname' => $modinfo['name'],
                                                        'itemtype' => $itemtype,
                                                        'itemid' => $itemid,
                                                        'objectid' => $itemid));
        }
    }

// TODO: retrieve from other data sources as well

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
            $fields[$id] = array('label' => $label,
                                 'type' => $type,
                                 'id' => $id,
                                 'default' => $default,
                                 'source' => $source,
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
            $is_primary = 1;
            // not allowed to modify primary key !
            $proptype = 1; // Static Text
        } else {
            $is_primary = 0;
        }

        $static[$id] = array('label' => $label,
                             'type' => $proptype,
                             'id' => $id,
                             'default' => $default,
                             'source' => $table . '.' . $field,
                             'validation' => $validation,
                             'increment' => $increment,
                             'is_itemid' => $is_primary);
        $result->MoveNext();
    }

    $result->Close();

    $propertybag["$modid:$itemtype"] = $static;
    return $static;
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
// TODO: replace fileupload above with this one someday ?
/*
    if (xarModIsAvailable('uploads') && xarModAPILoad('uploads','user')) {
        $proptypes[103] = array(
                                'id'         => 103,
                                'name'       => 'uploads',
                                'label'      => 'Upload',
                                'format'     => '103',
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

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * Handle <xar:data-input ...> form field tags
 * Format : <xar:data-input definition="$definition" /> with $definition an array
 *                                             containing the type, name, value, ...
 *       or <xar:data-input name="thisname" type="thattype" value="$val" ... />
 * 
 * @param $args array containing the input field definition or the type, name, value, ...
 * @returns string
 * @return the PHP code needed to invoke showinput() in the BL template
 */
function dynamicdata_userapi_handleInputTag($args)
{
    $out = "xarModAPILoad('dynamicdata','user');
echo xarModAPIFunc('dynamicdata',
                   'user',
                   'showinput',\n";
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
 * show some predefined form input field in a template
 * 
 * @param $args array containing the definition of the field (type, name, value, ...)
 * @returns string
 * @return string containing the HTML (or other) text to output in the BL template
 */
function dynamicdata_userapi_showinput($args)
{
    extract($args);
    if (empty($name)) {
        return xarML('Missing \'name\' attribute in field tag or definition');
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
        case 'categories':
            $output .= '// TODO: get categories select lists for this item';
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
            $output .= 'Unknown type '.xarVarPrepForDisplay($typename);
            break;
    }
    return $output;
}

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * Handle <xar:data-output ...> form field tags
 * Format : <xar:data-output definition="$definition" /> with $definition an array
 *                                             containing the type, name, value, ...
 *       or <xar:data-output name="thisname" type="thattype" value="$val" ... />
 * 
 * @param $args array containing the input field definition or the type, name, value, ...
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
    if (empty($name)) {
        return xarML('Missing \'name\' attribute in field tag or definition');
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

        case 'categories':
            // TODO: show categories for this item
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
        default:
            $output .= 'Unknown type '.xarVarPrepForDisplay($typename);
            break;
    }
    return $output;
}

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * Handle <xar:data-form ...> form tags
 * Format : <xar:data-form module="123" itemtype="0" itemid="555" />
 *       or <xar:data-form fields="$fields" ... />
 * 
 * @param $args array containing the item for which you want to show a form, or fields
 * @returns string
 * @return the PHP code needed to invoke showform() in the BL template
 */
function dynamicdata_userapi_handleFormTag($args)
{
    $out = "xarModAPILoad('dynamicdata','user');
echo xarModAPIFunc('dynamicdata',
                   'user',
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
function dynamicdata_userapi_showform($args)
{
   // TODO: retrieve params or properties for this item and loop
}


/**
// TODO: move this to some common place in Xaraya (base module ?)
 * Handle <xar:data-display ...> display tags
 * Format : <xar:data-display module="123" itemtype="0" itemid="555" />
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
   // TODO: retrieve params or properties for this item and loop
}

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * Handle <xar:data-list ...> list tags
 * Format : <xar:data-list module="123" itemtype="0" itemids="$list" />
 *       or <xar:data-list fields="$fields" ... />
 * 
 * @param $args array containing the items that you want to display, or fields
 * @returns string
 * @return the PHP code needed to invoke showlist() in the BL template
 */
function dynamicdata_userapi_handleListTag($args)
{
    $out = "xarModAPILoad('dynamicdata','user');
echo xarModAPIFunc('dynamicdata',
                   'user',
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
function dynamicdata_userapi_showlist($args)
{
   // TODO: retrieve params or properties to retrieve and loop
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
