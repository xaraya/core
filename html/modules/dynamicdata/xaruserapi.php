<?php
/**
 * File: $Id$
 *
 * Dynamic Data User API
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
// Generic item get() APIs
// ----------------------------------------------------------------------

/**
 * get all data fields (dynamic or static) for an item
 * (identified by module + item type + item id)
 *
 * @author the DynamicData module development team
 * @param $args['module'] module name of the item fields to get, or
 * @param $args['modid'] module id of the item fields to get
 * @param $args['itemtype'] item type of the item fields to get
 * @param $args['itemid'] item id of the item fields to get
 * @param $args['fieldlist'] array of field labels to retrieve (default is all)
 * @param $args['status'] limit to property fields of a certain status (e.g. active)
 * @param $args['static'] include the static properties (= module tables) too (default no)
 * @param $args['getobject'] flag indicating if you want to get the whole object back
 * @returns array
 * @return array of (name => value), or false on failure
 * @raise BAD_PARAM, NO_PERMISSION
 */
function &dynamicdata_userapi_getitem($args)
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

	if(!xarSecurityCheck('ViewDynamicDataItems',1,'Item',"$modid:$itemtype:$itemid")) return;

    // check the optional field list
    if (empty($fieldlist)) {
        $fieldlist = null;
    }

    // limit to property fields of a certain status (e.g. active)
    if (!isset($status)) {
        $status = null;
    }

    // include the static properties (= module tables) too ?
    if (empty($static)) {
        $static = false;
    }

    $object = new Dynamic_Object(array('moduleid'  => $modid,
                                       'itemtype'  => $itemtype,
                                       'itemid'    => $itemid,
                                       'fieldlist' => $fieldlist,
                                       'status'    => $status));
    if (!isset($object) || empty($object->objectid)) return;
    $object->getItem();

    if (!empty($getobject)) {
        return $object;
    }

    if (count($object->fieldlist) > 0) {
        $fieldlist = $object->fieldlist;
    } else {
        $fieldlist = array_keys($object->properties);
    }
    $fields = array();
    foreach ($fieldlist as $name) {
        $property = $object->properties[$name];
		if(xarSecurityCheck('ReadDynamicDataField',0,'Field',$property->name.':'.$property->type.':'.$property->id)) {
            $fields[$name] = $property->value;
        }
    }

    return $fields;
}

/*
 * This function is being phased out...
 */
function dynamicdata_userapi_getall($args)
{
    return dynamicdata_userapi_getitem($args);
}

/**
 * get all dynamic data fields for a list of items
 * (identified by module + item type, and item ids or other search criteria)
 *
 * @author the DynamicData module development team
 * @param $args['module'] module name of the item fields to get, or
 * @param $args['modid'] module id of the item fields to get
 * @param $args['itemtype'] item type of the item fields to get
 * @param $args['itemids'] array of item ids to return
 * @param $args['fieldlist'] array of field labels to retrieve (default is all)
 * @param $args['status'] limit to property fields of a certain status (e.g. active)
 * @param $args['static'] include the static properties (= module tables) too (default no)
 * @param $args['sort'] sort field(s)
 * @param $args['numitems'] number of items to retrieve
 * @param $args['startnum'] start number
 * @param $args['where'] WHERE clause to be used as part of the selection
 * @param $args['getobject'] flag indicating if you want to get the whole object back
 * @returns array
 * @return array of (itemid => array of (name => value)), or false on failure
 * @raise BAD_PARAM, NO_PERMISSION
 */
function &dynamicdata_userapi_getitems($args)
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
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'user', 'getitems', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

	if(!xarSecurityCheck('ViewDynamicDataItems',1,'Item',"$modid:$itemtype:All")) return;

    if (empty($itemids)) {
        $itemids = array();
    } elseif (!is_array($itemids)) {
        $itemids = explode(',',$itemids);
    }

    foreach ($itemids as $itemid) {
		if(!xarSecurityCheck('ViewDynamicDataItems',1,'Item',"$modid:$itemtype:$itemid")) return;
    }

    // check the optional field list
    if (empty($fieldlist)) {
        $fieldlist = null;
    }

    // limit to property fields of a certain status (e.g. active)
    if (!isset($status)) {
        $status = null;
    }

    // include the static properties (= module tables) too ?
    if (empty($static)) {
        $static = false;
    }

    if (empty($startnum) || !is_numeric($startnum)) {
        $startnum = 1;
    }
    if (empty($numitems) || !is_numeric($numitems)) {
        $numitems = 0;
    }

    if (empty($sort)) {
        $sort = null;
    }
    if (empty($where)) {
        $where = null;
    }

    $object = new Dynamic_Object_List(array('moduleid'  => $modid,
                                           'itemtype'  => $itemtype,
                                           'itemids' => $itemids,
                                           'sort' => $sort,
                                           'numitems' => $numitems,
                                           'startnum' => $startnum,
                                           'where' => $where,
                                           'fieldlist' => $fieldlist,
                                           'status' => $status));
    if (!isset($object)) return;
    // $items[$itemid]['fields'][$name]['value'] --> $items[$itemid][$name] now

    if (!empty($getobject)) {
        $object->getItems();
        return $object;
    } else {
        return $object->getItems();
    }
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
 * @param $args['name'] name of the field to get
 * @returns mixed
 * @return value of the field, or false on failure
 * @raise BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_userapi_getfield($args)
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
    if (!isset($name) || !is_string($name)) {
        $invalid[] = 'field name';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'user', 'get', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    $object = new Dynamic_Object(array('moduleid'  => $modid,
                                       'itemtype'  => $itemtype,
                                       'itemid'    => $itemid,
                                       'fieldlist' => array($name)));
    if (!isset($object)) return;
    $object->getItem();

    if (!isset($object->properties[$name])) return;
    $property = $object->properties[$name];

	if(!xarSecurityCheck('ReadDynamicDataField',1,'Field',$property->name.':'.$property->type.':'.$property->id)) return;
    if (!isset($property->value)) {
        $value = $property->default;
    } else {
        $value = $property->value;
    }

    return $value;
}

/*
 * This function is going to be phased out...
 */
function dynamicdata_userapi_get($args)
{
    return dynamicdata_userapi_getfield($args);
}


// ----------------------------------------------------------------------
// get*() properties, data sources, static fields, relationships, ...
// ----------------------------------------------------------------------

/**
 * get field properties for a specific module + item type
 *
 * @author the DynamicData module development team
 * @param $args['objectid'] object id of the properties to get
 * @param $args['module'] module name of the item fields, or
 * @param $args['modid'] module id of the item field to get
 * @param $args['itemtype'] item type of the item field to get
 * @param $args['fieldlist'] array of field labels to retrieve (default is all)
 * @param $args['status'] limit to property fields of a certain status (e.g. active)
 * @param $args['static'] include the static properties (= module tables) too (default no)
 * @returns mixed
 * @return value of the field, or false on failure
 * @raise BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getprop($args)
{
    static $propertybag = array();

    extract($args);

    if (!empty($objectid)) {
        $object = xarModAPIFunc('dynamicdata','user','getobjectinfo',
                                array('objectid' => $objectid));
        if (!empty($object)) {
            $modid = $object['moduleid'];
            $itemtype = $object['itemtype'];
        }
    } else {
        $objectid = null;
    }

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

    // limit to property fields of a certain status (e.g. active)
    if (!isset($status)) {
        $status = null;
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
        if (!empty($fieldlist)) {
            $myfields = array();
            foreach ($fieldlist as $name) {
                if (isset($propertybag["$modid:$itemtype"][$name])) {
                    $myfields[$name] = $propertybag["$modid:$itemtype"][$name];
                }
            }
            return $myfields;
        } elseif (isset($status)) {
            $myfields = array();
            foreach ($propertybag["$modid:$itemtype"] as $name => $field) {
                if ($field['status'] == $status) {
                    $myfields[$name] = $propertybag["$modid:$itemtype"][$name];
                }
            }
            return $myfields;
        } else {
            return $propertybag["$modid:$itemtype"];
        }
    }

    $fields = Dynamic_Property_Master::getProperties(array('objectid' => $objectid,
                                                           'moduleid' => $modid,
                                                           'itemtype' => $itemtype));

    if (!empty($static)) {
        // get the list of static properties for this module
        $staticlist = xarModAPIFunc('dynamicdata','util','getstatic',
                                    array('modid' => $modid,
                                          'itemtype' => $itemtype));
// TODO: watch out for conflicting property ids ?
        $fields = array_merge($staticlist,$fields);
    }

    if (empty($static)) {
        $propertybag["$modid:$itemtype"] = $fields;
    }
    if (!empty($fieldlist)) {
        $myfields = array();
        // this should return the fields in the right order, normally
        foreach ($fieldlist as $name) {
            if (isset($fields[$name])) {
                $myfields[$name] = $fields[$name];
            }
        }
        return $myfields;
    } elseif (isset($status)) {
        $myfields = array();
        foreach ($fields as $name => $field) {
            if ($field['status'] == $status) {
                $myfields[$name] = $field;
            }
        }
        return $myfields;
    } else {
        return $fields;
    }
}

/**
 * get the list of defined dynamic objects
 *
 * @author the DynamicData module development team
 * @returns array
 * @return array of object definitions
 * @raise DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getobjects($args = array())
{
    return Dynamic_Object_Master::getObjects();
}

/**
 * get information about a defined dynamic object
 *
 * @author the DynamicData module development team
 * @param $args['objectid'] id of the object you're looking for, or
 * @param $args['moduleid'] module id of the item field to get
 * @param $args['itemtype'] item type of the item field to get
 * @returns array
 * @return array of object definitions
 * @raise DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getobjectinfo($args)
{
    if (empty($args['moduleid']) && !empty($args['modid'])) {
       $args['moduleid'] = $args['modid'];
    }
    return Dynamic_Object_Master::getObjectInfo($args);
}

/**
 * get a dynamic object
 *
 * @author the DynamicData module development team
 * @param $args['objectid'] id of the object you're looking for, or
 * @param $args['moduleid'] module id of the item field to get
 * @param $args['itemtype'] item type of the item field to get
 * @returns object
 * @return a particular Dynamic Object
 */
function &dynamicdata_userapi_getobject($args)
{
    if (empty($args['moduleid']) && !empty($args['module'])) {
       $args['moduleid'] = xarModGetIDFromName($args['module']);
    }
    if (empty($args['moduleid']) && !empty($args['modid'])) {
       $args['moduleid'] = $args['modid'];
    }
    return new Dynamic_Object($args);
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
		if(xarSecurityCheck('ViewDynamicDataItems',0,'Item',"$modid:$itemtype:All")) {
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
    return Dynamic_DataStore_Master::getDataSources();
}

// ----------------------------------------------------------------------
// get*() property types
// ----------------------------------------------------------------------

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
    return Dynamic_Property_Master::getPropertyTypes();
}

// ----------------------------------------------------------------------
// BL user tags (output, display & view)
// ----------------------------------------------------------------------

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * Handle <xar:data-output ...> form field tags
 * Format : <xar:data-output name="thisname" type="thattype" value="$val" ... />
 *       or <xar:data-output field="$field" /> with $field an array containing the type, name, value, ...
 *       or <xar:data-output property="$property" /> with $property a Dynamic Property object
 *
 * @param $args array containing the input field definition or the type, name, value, ...
 * @returns string
 * @return the PHP code needed to invoke showoutput() in the BL template
 */
function dynamicdata_userapi_handleOutputTag($args)
{
    if (!empty($args['property'])) {
        if (isset($args['value'])) {
            if (is_numeric($args['value']) || substr($args['value'],0,1) == '$') {
                return 'echo '.$args['property'].'->showOutput('.$args['value'].'); ';
            } else {
                return 'echo '.$args['property'].'->showOutput("'.$args['value'].'"); ';
            }
        } else {
            return 'echo '.$args['property'].'->showOutput(); ';
        }
    }

    $out = "echo xarModAPIFunc('dynamicdata',
                   'user',
                   'showoutput',\n";
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
 * show some predefined output field in a template
 *
 * @param $args array containing the definition of the field (type, name, value, ...)
 * @returns string
 * @return string containing the HTML (or other) text to output in the BL template
 */
function dynamicdata_userapi_showoutput($args)
{
    $property = & Dynamic_Property_Master::getProperty($args);
    return $property->showOutput($args['value']);

    // TODO: output from some common hook/utility modules
}

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * Handle <xar:data-display ...> display tags
 * Format : <xar:data-display module="123" itemtype="0" itemid="555" fieldlist="$fieldlist" static="yes" .../>
 *       or <xar:data-display fields="$fields" ... />
 *       or <xar:data-display object="$object" ... />
 *
 * @param $args array containing the item that you want to display, or fields
 * @returns string
 * @return the PHP code needed to invoke showdisplay() in the BL template
 */
function dynamicdata_userapi_handleDisplayTag($args)
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
            return 'echo '.$args['object'].'->showDisplay(array('.join(', ',$parts).')); ';
        } else {
            return 'echo '.$args['object'].'->showDisplay(); ';
        }
    }

    $out = "echo xarModAPIFunc('dynamicdata',
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
	if(!xarSecurityCheck('ReadDynamicDataItem',1,'Item',"$modid:$itemtype:$itemid")) return;

    // we got everything via template parameters
    if (isset($fields) && is_array($fields) && count($fields) > 0) {
        return xarTplModule('dynamicdata','user','showdisplay',
                            array('fields' => $fields,
                                  'layout' => $layout),
                            $template);
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

    $object = new Dynamic_Object(array('moduleid'  => $modid,
                                       'itemtype'  => $itemtype,
                                       'itemid'    => $itemid,
                                       'fieldlist' => $myfieldlist));
    // we're dealing with a real item, so retrieve the property values
    if (!empty($itemid)) {
        $object->getItem();
    }
    // if we are in preview mode, we need to check for any preview values
    //$preview = xarVarCleanFromInput('preview');
    //if (!empty($preview)) {
    //    $object->checkInput();
    //}

    return $object->showDisplay(array('layout'   => $layout,
                                      'template' => $template));
}

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * Handle <xar:data-getitem ...> getitem tags
 * Format : <xar:data-getitem name="$properties" module="123" itemtype="0" itemid="$id" fieldlist="$fieldlist" .../>
 *       or <xar:data-getitem name="$properties" object="$object" ... />
 *
 * @param $args array containing the module and item that you want to display, or fields
 * @returns string
 * @return the PHP code needed to invoke getitemtag() in the BL template and return an array of properties
 */
function dynamicdata_userapi_handleGetItemTag($args)
{
    // if we already have an object, we simply invoke its showView() method
    if (!empty($args['object'])) {
        if (count($args) > 1) {
            $parts = array();
            foreach ($args as $key => $val) {
                if ($key == 'object' || $key == 'name') continue;
                if (is_numeric($val) || substr($val,0,1) == '$') {
                    $parts[] = "'$key' => ".$val;
                } else {
                    $parts[] = "'$key' => '".$val."'";
                }
            }
            return $args['object'].'->getItem(array('.join(', ',$parts).')); ' .
                   $args['name'] . ' =& '.$args['object'].'->getProperties(); ';
        } else {
            return $args['object'].'->getItem(); ' .
                   $args['name'] . ' =& '.$args['object'].'->getProperties(); ';
        }
    }

    // if we don't have an object yet, we'll make one below
    $out = 'list('.$args['name']. ") = xarModAPIFunc('dynamicdata',
                   'user',
                   'getitemfordisplay',\n";
    // PHP >= 4.2.0 only
    //$out .= var_export($args);
    $out .= "                   array(\n";
    foreach ($args as $key => $val) {
        if ($key == 'name') continue;
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
 * return the properties for an item
 *
 * @param $args array containing the items or fields to show
 * @returns array
 * @return array containing a reference to the properties of the item
 */
function dynamicdata_userapi_getitemfordisplay($args)
{
    $args['getobject'] = 1;
    $object = & xarModAPIFunc('dynamicdata','user','getitem',$args);
    if (isset($object)) {
        $properties = & $object->getProperties();
    } else {
        $properties = array();
    }
    return array(& $properties);
}

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * Handle <xar:data-view ...> view tags
 * Format : <xar:data-view module="123" itemtype="0" itemids="$idlist" fieldlist="$fieldlist" static="yes" .../>
 *       or <xar:data-view items="$items" labels="$labels" ... />
 *       or <xar:data-view object="$object" ... />
 *
 * @param $args array containing the items that you want to display, or fields
 * @returns string
 * @return the PHP code needed to invoke showview() in the BL template
 */
function dynamicdata_userapi_handleViewTag($args)
{
    // if we already have an object, we simply invoke its showView() method
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
            return 'echo '.$args['object'].'->showView(array('.join(', ',$parts).')); ';
        } else {
            return 'echo '.$args['object'].'->showView(); ';
        }
    }

    // if we don't have an object yet, we'll make one below
    $out = "echo xarModAPIFunc('dynamicdata',
                   'user',
                   'showview',\n";
    // PHP >= 4.2.0 only
    //$out .= var_export($args);
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

    if (empty($modid)) {
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
    } else {
            $modinfo = xarModGetInfo($modid);
            $modname = $modinfo['name'];
    }
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'user', 'showview', 'dynamicdata');
        xarExceptionSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    if (empty($itemtype) || !is_numeric($itemtype)) {
        $itemtype = null;
    }

// TODO: what kind of security checks do we want/need here ?
	if(!xarSecurityCheck('ViewDynamicDataItems',1,'Item',"$modid:$itemtype:All")) return;

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

    $object = new Dynamic_Object_List(array('moduleid'  => $modid,
                                           'itemtype'  => $itemtype,
                                           'itemids' => $itemids,
                                           'sort' => $sort,
                                           'numitems' => $numitems,
                                           'startnum' => $startnum,
                                           'where' => $where,
                                           'fieldlist' => $myfieldlist,
                                           'status' => $status));
    if (!isset($object)) return;

    $object->getItems();

    // label to use for the display link (if you don't use linkfield)
    if (empty($linklabel)) {
        $linklabel = '';
    }
    // function to use in the display link
    if (empty($linkfunc)) {
        $linkfunc = '';
    }
    // URL parameter for the item id in the display link (e.g. exid, aid, uid, ...)
    if (empty($param)) {
        $param = '';
    }
    // field to add the display link to (otherwise it'll be in a separate column)
    if (empty($linkfield)) {
        $linkfield = '';
    }

    return $object->showView(array('layout'    => $layout,
                                   'template'  => $template,
                                   'linklabel' => $linklabel,
                                   'linkfunc'  => $linkfunc,
                                   'param'     => $param,
                                   'linkfield' => $linkfield));
}

/**
// TODO: move this to some common place in Xaraya (base module ?)
 * Handle <xar:data-getitems ...> getitems tags
 * Format : <xar:data-getitems name="$properties" value="$values" module="123" itemtype="0" itemids="$idlist" fieldlist="$fieldlist" .../>
 *       or <xar:data-getitems name="$properties" value="$values" object="$object" ... />
 *
 * @param $args array containing the items that you want to display, or fields
 * @returns string
 * @return the PHP code needed to invoke getitemstag() in the BL template and return an array of properties and items
 */
function dynamicdata_userapi_handleGetItemsTag($args)
{
    // if we already have an object, we simply invoke its showView() method
    if (!empty($args['object'])) {
        if (count($args) > 1) {
            $parts = array();
            foreach ($args as $key => $val) {
                if ($key == 'object' || $key == 'name' || $key == 'value') continue;
                if (is_numeric($val) || substr($val,0,1) == '$') {
                    $parts[] = "'$key' => ".$val;
                } else {
                    $parts[] = "'$key' => '".$val."'";
                }
            }
            return $args['value'] . ' =& '.$args['object'].'->getItems(array('.join(', ',$parts).')); ' .
                   $args['name'] . ' =& '.$args['object'].'->getProperties(); ';
        } else {
            return $args['value'] . ' =& '.$args['object'].'->getItems(); ' .
                   $args['name'] . ' =& '.$args['object'].'->getProperties(); ';
        }
    }

    // if we don't have an object yet, we'll make one below
    $out = 'list('.$args['name'].','.$args['value'] . ") = xarModAPIFunc('dynamicdata',
                   'user',
                   'getitemsforview',\n";
    // PHP >= 4.2.0 only
    //$out .= var_export($args);
    $out .= "                   array(\n";
    foreach ($args as $key => $val) {
        if ($key == 'name' || $key == 'value') continue;
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
 * return the properties and items
 *
 * @param $args array containing the items or fields to show
 * @returns array
 * @return array containing a reference to the properties and a reference to the items
 */
function dynamicdata_userapi_getitemsforview($args)
{
    if (empty($args['fieldlist']) && empty($args['status'])) {
        // get the Active properties only (not those for Display Only)
        $args['status'] = 1;
    }
    $args['getobject'] = 1;
    $object = & xarModAPIFunc('dynamicdata','user','getitems',$args);
    if (!isset($object)) {
        return array(array(), array());
    }
    $properties = & $object->getProperties();
    $items = & $object->items;
    return array(& $properties, & $items);
}

/**
 * Handle <xar:data-label ...> label tag
 * Format : <xar:data-label object="$object" /> with $object some Dynamic Object
 *       or <xar:data-label property="$property" /> with $property some Dynamic Property
 *
 * @param $args array containing the object or property
 * @returns string
 * @return the PHP code needed to show the object or property label in the BL template
 */
function dynamicdata_userapi_handleLabelTag($args)
{
    if (!empty($args['object'])) {
        return 'echo xarVarPrepForDisplay('.$args['object'].'->label); ';
    } elseif (!empty($args['property'])) {
        return 'echo xarVarPrepForDisplay('.$args['property'].'->label); ';
    } else {
        return 'echo "I need an object or a property"; ';
    }
}

/**
 * Handle <xar:data-object ...> object tag
 * Format : <xar:data-object object="$object" property="$property" /> with $object some object and $property some property of this object
 *       or <xar:data-object object="$object" method="$method" arguments="$args" /> with $object some object and $method some method of this object
 *
 * @param $args array containing the object and property/method
 * @returns string
 * @return the PHP code needed to show the object property or call the object method in the BL template
 */
function dynamicdata_userapi_handleObjectTag($args)
{
    if (!empty($args['object'])) {
        if (!empty($args['property'])) {
            return 'echo '.$args['object'].'->'.$args['property'].'; ';
        } elseif (!empty($args['method'])) {
            if (!empty($args['arguments'])) {
                return 'echo '.$args['object'].'->'.$args['method'].'('.$args['arguments'].'); ';
            } else {
                return 'echo '.$args['object'].'->'.$args['method'].'(); ';
            }
        } else {
            return 'echo "I need a property or a method for this object"; ';
        }
    } else {
        return 'echo "I need an object"; ';
    }
}

// ----------------------------------------------------------------------
// TODO: search API, some generic queries for statistics, etc.
//

/**
 * utility function pass individual menu items to the main menu
 *
 * @author the DynamicData module development team
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function dynamicdata_userapi_getmenulinks()
{
    $menulinks = array();

	if(xarSecurityCheck('ViewDynamicDataItems')) {

        // get items from the objects table
        $objects = xarModAPIFunc('dynamicdata','user','getobjects');
        if (!isset($objects)) {
            return $menulinks;
        }
        $mymodid = xarModGetIDFromName('dynamicdata');
        foreach ($objects as $object) {
            $itemid = $object['objectid'];
            // skip the internal objects
            if ($itemid < 3) continue;
            $modid = $object['moduleid'];
            // don't show data "belonging" to other modules for now
            if ($modid != $mymodid) {
                continue;
            }
            // nice(r) URLs
            if ($modid == $mymodid) {
                $modid = null;
            }
            $itemtype = $object['itemtype'];
            if ($itemtype == 0) {
                $itemtype = null;
            }
            $label = $object['label'];
            $menulinks[] = Array('url'   => xarModURL('dynamicdata','user','view',
                                                      array('modid' => $modid,
                                                            'itemtype' => $itemtype)),
                                 'title' => xarML('View #(1)', $label),
                                 'label' => $label);
        }
    }

    return $menulinks;
}

/**
 * utility function to count the number of items held by this module
 *
 * @author the DynamicData module development team
 * @param $args the usual suspects :)
 * @returns integer
 * @return number of items held by this module
 */
function dynamicdata_userapi_countitems($args)
{
    $mylist = new Dynamic_Object_List($args);
    if (!isset($mylist)) return;

    return $mylist->countItems();
}

// ----------------------------------------------------------------------
// Short URL Support
// ----------------------------------------------------------------------

/**
 * return the path for a short URL to xarModURL for this module
 * @param $args the function and arguments passed to xarModURL
 * @returns string
 * @return path to be added to index.php for a short URL, or empty if failed
 */
function dynamicdata_userapi_encode_shorturl($args)
{
    static $objectcache = array();

    if (count($objectcache) == 0) {
        $objects = xarModAPIFunc('dynamicdata','user','getobjects');
        foreach ($objects as $object) {
            $objectcache[$object['moduleid'].':'.$object['itemtype']] = $object['name'];
        }
    }

    // Get arguments from argument array
    extract($args);

    // check if we have something to work with
    if (!isset($func)) {
        return;
    }

    // fill in default values
    if (empty($modid)) {
        $modid = xarModGetIDFromName('dynamicdata');
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    // make sure you don't pass the following variables as arguments too

    // default path is empty -> no short URL
    $path = '';
    // if we want to add some common arguments as URL parameters below
    $join = '?';
    // we can't rely on xarModGetName() here !
    $module = 'dynamicdata';

    // specify some short URLs relevant to your module
    if ($func == 'main') {
        $path = '/' . $module . '/';
    } elseif ($func == 'view') {
        if (!empty($objectcache[$modid.':'.$itemtype])) {
            $name = $objectcache[$modid.':'.$itemtype];
            $alias = xarModGetAlias($name);
            if ($module == $alias) {
                // OK, we can use a 'fake' module name here
                $path = '/' . $name . '/';
            } else {
                $path = '/' . $module . '/' . $name . '/';
            }
        } else {
            // we don't know this one...
        }
    } elseif ($func == 'display' && isset($itemid)) {
        if (!empty($objectcache[$modid.':'.$itemtype])) {
            $name = $objectcache[$modid.':'.$itemtype];
            $alias = xarModGetAlias($name);
            if ($module == $alias) {
                // OK, we can use a 'fake' module name here
                $path = '/' . $name . '/' . $itemid;
            } else {
                $path = '/' . $module . '/' . $name . '/' . $itemid;
            }
        } else {
            // we don't know this one...
        }
    }
    // anything else does not have a short URL equivalent

// TODO: add *any* extra args we didn't use yet here
    // add some other module arguments as standard URL parameters
    if (!empty($path)) {
        // search
        if (isset($q)) {
            $path .= $join . 'q=' . urlencode($q);
            $join = '&';
        }
        // sort
        if (isset($sort)) {
            $path .= $join . 'sort=' . $sort;
            $join = '&';
        }
        // pager
        if (isset($startnum) && $startnum != 1) {
            $path .= $join . 'startnum=' . $startnum;
            $join = '&';
        }
        // multi-page articles
        if (isset($page)) {
            $path .= $join . 'page=' . $page;
            $join = '&';
        }
    }

    return $path;
}

/**
 * extract function and arguments from short URLs for this module, and pass
 * them back to xarGetRequestInfo()
 * @param $params array containing the elements of PATH_INFO
 * @returns array
 * @return array containing func the function to be called and args the query
 *         string arguments, or empty if it failed
 */
function dynamicdata_userapi_decode_shorturl($params)
{
    static $objectcache = array();

    if (count($objectcache) == 0) {
        $objects = xarModAPIFunc('dynamicdata','user','getobjects');
        foreach ($objects as $object) {
            $objectcache[$object['name']] = array('modid'    => $object['moduleid'],
                                                  'itemtype' => $object['itemtype']);
        }
    }

    $args = array();

    $module = 'dynamicdata';

    // Check if we're dealing with an alias here
    if ($params[0] != $module) {
        $alias = xarModGetAlias($params[0]);
        // yup, looks like it
        if ($module == $alias) {
            if (isset($objectcache[$params[0]])) {
                $args['modid'] = $objectcache[$params[0]]['modid'];
                $args['itemtype'] = $objectcache[$params[0]]['itemtype'];
            } else {
                // we don't know this one...
                return;
            }
        } else {
            // we don't know this one...
            return;
        }
    }

    if (empty($params[1]) || preg_match('/^index/i',$params[1])) {
        if (count($args) > 0) {
            return array('view', $args);
        } else {
            return array('main', $args);
        }

    } elseif (preg_match('/^(\d+)/',$params[1],$matches)) {
        $itemid = $matches[1];
        $args['itemid'] = $itemid;
        return array('display', $args);

    } elseif (isset($objectcache[$params[1]])) {
        $args['modid'] = $objectcache[$params[1]]['modid'];
        $args['itemtype'] = $objectcache[$params[1]]['itemtype'];
        if (empty($params[2]) || preg_match('/^index/i',$params[2])) {
            return array('view', $args);
        } elseif (preg_match('/^(\d+)/',$params[2],$matches)) {
            $itemid = $matches[1];
            $args['itemid'] = $itemid;
            return array('display', $args);
        } else {
            // we don't know this one...
        }

    } else {
        // we don't know this one...
    }

    // default : return nothing -> no short URL

}

?>
