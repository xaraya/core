<?php
/**
 * File: $Id$
 *
 * Dynamic Data Utilities API
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
// Some import/export utility functions that don't need to be loaded each time
// ----------------------------------------------------------------------

/**
 * Export an object definition or an object item to XML
 */
function dynamicdata_utilapi_export($args)
{
    // restricted to DD Admins
// Security Check
	if(!securitycheck('Admin')) return;

    extract($args);

    // TODO: copy from GUI function :)
}

/**
 * Import an object definition or an object item from XML
 */
function dynamicdata_utilapi_import($args)
{
    // restricted to DD Admins
// Security Check
	if(!securitycheck('Admin')) return;

    extract($args);

    if (empty($file) || !file_exists($file) || !preg_match('/\.xml$/',$file)) {
        $msg = xarML('Invalid import file');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }

    $fp = @fopen($file, 'r');
    if (!$fp) {
        $msg = xarML('Unable to open import file');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    $what = '';
    $count = 0;
    $objectname2objectid = array();
    $objectcache = array();
    $objectmaxid = array();
    while (!feof($fp)) {
        $line = fgets($fp, 4096);
        $count++;
        if (empty($what)) {
            if (preg_match('#<object name="(\w+)">#',$line,$matches)) { // in case we import the object definition
                $object = array();
                $object['name'] = $matches[1];
                $what = 'object';
            } elseif (preg_match('#<items>#',$line)) { // in case we only import data
                $what = 'item';
            }

         } elseif ($what == 'object') {
            if (preg_match('#<([^>]+)>(.*)</\1>#',$line,$matches)) {
                $key = $matches[1];
                $value = $matches[2];
                if (isset($object[$key])) {
                    $msg = xarML('Duplicate definition for #(1) key #(2) on line #(3)','object',xarVarPrepForDisplay($key),$count);
                    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                    new SystemException($msg));
                    fclose($fp);
                    return;
                }
                $object[$key] = $value;
            } elseif (preg_match('#<properties>#',$line)) {
                // let's create the object now...
                if (empty($object['name']) || empty($object['moduleid'])) {
                    $msg = xarML('Missing keys in object definition');
                    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                    new SystemException($msg));
                    fclose($fp);
                    return;
                }
                // make sure we drop the object id, because it might already exist here
                unset($object['objectid']);

                // for objects that belong to dynamicdata itself, reset the itemtype too
                if ($object['moduleid'] == xarModGetIDFromName('dynamicdata')) {
                    $object['itemtype'] = -1;
                }

                $objectid = xarModAPIFunc('dynamicdata','admin','createobject',
                                          $object);
                if (!isset($objectid)) {
                    fclose($fp);
                    return;
                }

                // retrieve the correct itemtype if necessary
                if ($object['itemtype'] < 0) {
                    $objectinfo = xarModAPIFunc('dynamicdata','user','getobjectinfo',
                                                array('objectid' => $objectid));
                    $object['itemtype'] = $objectinfo['itemtype'];
                }

                $what = 'property';
            } elseif (preg_match('#<items>#',$line)) {
                $what = 'item';
            } elseif (preg_match('#</object>#',$line)) {
                $what = '';
            } else {
                // multi-line entries not relevant here
            }

        } elseif ($what == 'property') {
            if (preg_match('#<property name="(\w+)">#',$line,$matches)) {
                $property = array();
                $property['name'] = $matches[1];
            } elseif (preg_match('#</property>#',$line)) {
                // let's create the property now...
                $property['objectid'] = $objectid;
                $property['moduleid'] = $object['moduleid'];
                $property['itemtype'] = $object['itemtype'];
                if (empty($property['name']) || empty($property['type'])) {
                    $msg = xarML('Missing keys in property definition');
                    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                    new SystemException($msg));
                    fclose($fp);
                    return;
                }
                // make sure we drop the property id, because it might already exist here
                unset($property['id']);
                $prop_id = xarModAPIFunc('dynamicdata','admin','createproperty',
                                         $property);
                if (!isset($prop_id)) {
                    fclose($fp);
                    return;
                }
            } elseif (preg_match('#<([^>]+)>(.*)</\1>#',$line,$matches)) {
                $key = $matches[1];
                $value = $matches[2];
                if (isset($property[$key])) {
                    $msg = xarML('Duplicate definition for #(1) key #(2) on line #(3)','property',xarVarPrepForDisplay($key),$count);
                    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                    new SystemException($msg));
                    fclose($fp);
                    return;
                }
                $property[$key] = $value;
            } elseif (preg_match('#</properties>#',$line)) {
                $what = 'object';
            } elseif (preg_match('#<items>#',$line)) {
                $what = 'item';
            } elseif (preg_match('#</object>#',$line)) {
                $what = '';
            } else {
                // multi-line entries not relevant here
            }

        } elseif ($what == 'item') {
            if (preg_match('#<([^> ]+) itemid="(\d+)">#',$line,$matches)) {
                // find out what kind of item we're dealing with
                $objectname = $matches[1];
                $itemid = $matches[2];
                if (empty($objectname2objectid[$objectname])) {
                    $objectinfo = Dynamic_Object_Master::getObjectInfo(array('name' => $objectname));
                    if (isset($objectinfo) && !empty($objectinfo['objectid'])) {
                        $objectname2objectid[$objectname] = $objectinfo['objectid'];
                    } else {
                        $msg = xarML('Unknown #(1) "#(2)" on line #(3)','object',xarVarPrepForDisplay($objectname),$count);
                        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                        new SystemException($msg));
                        fclose($fp);
                        return;
                    }
                }
                $objectid = $objectname2objectid[$objectname];
                $item = array();
                // don't save the item id for now...
            // TODO: keep the item id if we set some flag
                //$item['itemid'] = $itemid;
                $closeitem = $objectname;
                $closetag = 'N/A';
            } elseif (preg_match("#</$closeitem>#",$line)) {
                // let's create the item now...
                if (!isset($objectcache[$objectid])) {
                    $objectcache[$objectid] = new Dynamic_Object(array('objectid' => $objectid));
                }
                // set the item id to 0
            // TODO: keep the item id if we set some flag
                $item['itemid'] = 0;
                // create the item
                $itemid = $objectcache[$objectid]->createItem($item);
                if (empty($itemid)) {
                    fclose($fp);
                    return;
                }
                // keep track of the highest item id
                if (empty($objectmaxid[$objectid]) || $objectmaxid[$objectid] < $itemid) {
                    $objectmaxid[$objectid] = $itemid;
                }
                $closeitem = 'N/A';
                $closetag = 'N/A';
            } elseif (preg_match('#<([^>]+)>(.*)</\1>#',$line,$matches)) {
                $key = $matches[1];
                $value = $matches[2];
                if (isset($item[$key])) {
                    $msg = xarML('Duplicate definition for #(1) key #(2) on line #(3)','item',xarVarPrepForDisplay($key),$count);
                    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                    new SystemException($msg));
                    fclose($fp);
                    return;
                }
                $item[$key] = $value;
                $closetag = 'N/A';
            } elseif (preg_match('#<([^/>]+)>(.*)#',$line,$matches)) {
                // multi-line entries *are* relevant here
                $key = $matches[1];
                $value = $matches[2];
                if (isset($item[$key])) {
                    $msg = xarML('Duplicate definition for #(1) key #(2)','item',xarVarPrepForDisplay($key));
                    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                    new SystemException($msg));
                    fclose($fp);
                    return;
                }
                $item[$key] = $value;
                $closetag = $key;
            } elseif (preg_match("#(.*)</$closetag>#",$line,$matches)) {
                // multi-line entries *are* relevant here
                $value = $matches[1];
                if (!isset($item[$closetag])) {
                    $msg = xarML('Undefined #(1) key #(2)','item',xarVarPrepForDisplay($closetag));
                    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                    new SystemException($msg));
                    fclose($fp);
                    return;
                }
                $item[$closetag] .= $value;
                $closetag = 'N/A';
            } elseif ($closetag != 'N/A') {
                // multi-line entries *are* relevant here
                if (!isset($item[$closetag])) {
                    $msg = xarML('Undefined #(1) key #(2)','item',xarVarPrepForDisplay($closetag));
                    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                    new SystemException($msg));
                    fclose($fp);
                    return;
                }
                $item[$closetag] .= $line;
            } elseif (preg_match('#</items>#',$line)) {
                $what = 'object';
            } elseif (preg_match('#</object>#',$line)) {
                $what = '';
            } else {
            }
        } else {
        }
    }
    fclose($fp);

    // adjust maxid (for objects stored in the dynamic_data table)
    if (count($objectcache) > 0 && count($objectmaxid) > 0) {
        foreach (array_keys($objectcache) as $objectid) {
            if (!empty($objectmaxid[$objectid]) && $objectcache[$objectid]->maxid < $objectmaxid[$objectid]) {
                $itemid = Dynamic_Object_Master::updateObject(array('objectid' => $objectid,
                                                                    'maxid'    => $objectmaxid[$objectid]));
                if (empty($itemid)) return;
            }
        }
    }

    return $objectid;
}

/**
 * (try to) get the "static" properties, corresponding to fields in dedicated
 * tables for this module + item type
// TODO: allow modules to specify their own properties
 *
 * @author the DynamicData module development team
 * @param $args['module'] module name of table you're looking for, or
 * @param $args['modid'] module id of table you're looking for
 * @param $args['itemtype'] item type of table you're looking for
 * @param $args['table']  table name of table you're looking for (better)
 * @returns mixed
 * @return value of the field, or false on failure
 * @raise BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_utilapi_getstatic($args)
{
    static $propertybag = array();

    extract($args);

    if (empty($modid) && !empty($module)) {
        $modid = xarModGetIDFromName($module);
    }
    if (empty($modid)) {
        $modid = xarModGetIDFromName(xarModGetName());
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
                    join(', ',$invalid), 'util', 'getstatic', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }
    if (empty($table)) {
        $table = '';
    }
    if (isset($propertybag["$modid:$itemtype:$table"])) {
        return $propertybag["$modid:$itemtype:$table"];
    }

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

// TODO: support site tables as well
    $systemPrefix = xarDBGetSystemTablePrefix();
    $metaTable = $systemPrefix . '_tables';

    if ($modinfo['name'] == 'dynamicdata') {
        // let's cheat a little for DD, because otherwise it won't find any tables :)
        if ($itemtype == 0) {
            $modinfo['name'] = 'dynamic_objects';
        } elseif ($itemtype == 1) {
            $modinfo['name'] = 'dynamic_properties';
        } elseif ($itemtype == 2) {
            $modinfo['name'] = 'dynamic_data';
        }
    }

    $query = "SELECT xar_tableid,
                     xar_table,
                     xar_field,
                     xar_type,
                     xar_size,
                     xar_default,
                     xar_increment,
                     xar_primary_key
              FROM $metaTable ";

    // it's easy if the table name is known
    if (!empty($table)) {
        $query .= " WHERE xar_table = '" . xarVarPrepForStore($table) . "'";

    // otherwise try to get any table that starts with prefix_modulename
    } else {
        $query .= " WHERE xar_table LIKE '" . xarVarPrepForStore($systemPrefix)
                                   . '_' . xarVarPrepForStore($modinfo['name']) . '%' . "' ";
    }
    $query .= " ORDER BY xar_tableid ASC";

    $result =& $dbconn->Execute($query);

    if (!$result) return;

    $static = array();

    // add the list of table + field
    $order = 1;
    while (!$result->EOF) {
        list($id,$table, $field, $datatype, $size, $default,$increment,$primary_key) = $result->fields;
    // TODO: what kind of security checks do we want/need here ?
        //if (xarSecAuthAction(0, 'DynamicData::Field', "$name:$type:$id", ACCESS_READ)) {
        //}

        // assign some default label for now, by removing everything except the last part (xar_..._)
// TODO: let modules define this
        $name = preg_replace('/^.+_/','',$field);
        $label = ucfirst($name);
        if (isset($static[$name])) {
            $i = 1;
            while (isset($static[$name . '_' . $i])) {
                $i++;
            }
            $name = $name . '_' . $i;
            $label = $label . '_' . $i;
        }

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

        $static[$name] = array('name' => $name,
                               'label' => $label,
                               'type' => $proptype,
                               'id' => $id,
                               'default' => $default,
                               'source' => $table . '.' . $field,
                               'status' => 1,
                               'order' => $order,
                               'validation' => $validation);
        $order++;
        $result->MoveNext();
    }

    $result->Close();

    $propertybag["$modid:$itemtype:$table"] = $static;
    return $static;
}

/**
 * (try to) get the "meta" properties of tables via PHP ADODB
 *
 * @author the DynamicData module development team
 * @param $args['table']  optional table you're looking for
 * @returns mixed
 * @return array of field definitions, or null on failure
 * @raise BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_utilapi_getmeta($args)
{
    static $propertybag = array();

// Security Check
	if(!securitycheck('Admin')) return;

    extract($args);

    if (empty($table)) {
        $table = '';
    } elseif (isset($propertybag[$table])) {
        return $propertybag[$table];
    }

    list($dbconn) = xarDBGetConn();

    if (!empty($table)) {
        $tables = array($table);
    } else {
        $tables = $dbconn->MetaTables();
    }
    if (!isset($tables)) {
        return;
    }

    $metadata = array();
    foreach ($tables as $table) {
        $fields = $dbconn->MetaColumns($table);
        $keys = $dbconn->MetaPrimaryKeys($table);

        $id = 1;
        $columns = array();
        foreach ($fields as $field) {
            $fieldname = $field->name;
            $datatype = $field->type;
            $size = $field->max_length;

            // assign some default label for now, by removing everything except the last part (xar_..._)
            $name = preg_replace('/^.+_/','',$fieldname);
            $label = ucfirst($name);
            if (isset($columns[$name])) {
                $i = 1;
                while (isset($columns[$name . '_' . $i])) {
                    $i++;
                }
                $name = $name . '_' . $i;
                $label = $label . '_' . $i;
            }

            // (try to) assign some default property type for now
            // = obviously limited to basic data types in this case
            $dtype = $datatype;
            // skip special definitions (unsigned etc.)
            $dtype = preg_replace('/\(.*$/','',$dtype);
            switch ($dtype) {
                case 'char':
                case 'varchar':
                    $proptype = 2; // Text Box
                    break;
                case 'int':
                case 'integer':
                case 'tinyint':
                case 'smallint':
                case 'mediumint':
                    if ($size == 1) {
                        $proptype = 14; // Checkbox
                    } else {
                        $proptype = 15; // Number Box
                    }
                    break;
                case 'float':
                case 'decimal':
                case 'double':
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
                case 'longtext':
                    $proptype = 5; // Large Text Area
                    break;
                case 'blob':       // caution, could be binary too !
                    $proptype = 4; // Medium Text Area
                    break;
                case 'enum':
                    $proptype = 6; // Dropdown
                    break;
                default:
                    $proptype = 1; // Static Text
                    break;
            }

            // assign some default validation for now
            $validation = $datatype;
            $validation .= (empty($size) || $size < 0) ? '' : ' (' . $size . ')';

            // try to figure out if it's the item id
            if (!empty($keys) && in_array($fieldname,$keys)) {
                // not allowed to modify primary key !
                $proptype = 21; // Item ID
            }

            $columns[$name] = array('name' => $name,
                                   'label' => $label,
                                   'type' => $proptype,
                                   'id' => $id,
                                   'default' => '', // unknown here
                                   'source' => $table . '.' . $fieldname,
                                   'status' => 1,
                                   'order' => $id,
                                   'validation' => $validation);
            $id++;
        }
        $metadata[$table] = $columns;
    }

    $propertybag = $metadata;
    return $metadata;
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
function dynamicdata_utilapi_getrelations($args)
{
    static $propertybag = array();

    extract($args);

    if (empty($modid) && !empty($module)) {
        $modid = xarModGetIDFromName($module);
    }
    if (empty($modid)) {
        $modid = xarModGetIDFromName(xarModGetName());
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
                    join(', ',$invalid), 'util', 'getrelations', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (isset($propertybag["$modid:$itemtype"])) {
        return $propertybag["$modid:$itemtype"];
    }

    // get the list of static properties for this module
    $static = xarModAPIFunc('dynamicdata','util','getstatic',
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
            $modstatic = xarModAPIFunc('dynamicdata','util','getstatic',
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
                }
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
 * import property fields from a static table
 *
 * @author the DynamicData module development team
 * @param $args['modid'] module id of the table to import
 * @param $args['itemtype'] item type of the table to import
 * @param $args['table'] name of the table you want to import
 * @param $args['objectid'] object id to assign these properties to
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_utilapi_importproperties($args)
{
    extract($args);

    // Required arguments
    $invalid = array();
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module id', 'util', 'importproperties', 'DynamicData');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if (!xarSecAuthAction(0, 'DynamicData::Field', "::", ACCESS_ADD)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    if (empty($itemtype)) {
        $itemtype = 0;
    }
    if (empty($table)) {
        $table = '';
    }

    // search for an object, or create one
    if (empty($objectid)) {
        $object = xarModAPIFunc('dynamicdata','user','getobjectinfo',
                                array('modid' => $modid,
                                      'itemtype' => $itemtype));
        if (!isset($object)) {
            $modinfo = xarModGetInfo($modid);
            $name = $modinfo['name'];
            if (!empty($itemtype)) {
                $name .= '_' . $itemtype;
            }
            $objectid = xarModAPIFunc('dynamicdata','admin','createobject',
                                      array('moduleid' => $modid,
                                            'itemtype' => $itemtype,
                                            'name' => $name,
                                            'label' => ucfirst($name)));
            if (!isset($objectid)) return;
        } else {
            $objectid = $object['objectid'];
        }
    }

    $fields = xarModAPIFunc('dynamicdata','util','getstatic',
                            array('modid' => $modid,
                                  'itemtype' => $itemtype,
                                  'table' => $table));
    if (!isset($fields) || !is_array($fields)) return;

    // create new properties
    foreach ($fields as $name => $field) {
        $prop_id = xarModAPIFunc('dynamicdata','admin','createproperty',
                                array('name' => $name,
                                      'label' => $field['label'],
                                      'objectid' => $objectid,
                                      'moduleid' => $modid,
                                      'itemtype' => $itemtype,
                                      'type' => $field['type'],
                                      'default' => $field['default'],
                                      'source' => $field['source'],
                                      'status' => $field['status'],
                                      'order' => $field['order'],
                                      'validation' => $field['validation']));
        if (empty($prop_id)) {
            return;
        }
    }
    return true;
}


?>
