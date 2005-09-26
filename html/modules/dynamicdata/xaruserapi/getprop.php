<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
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
 * @param $args['allprops'] skip disabled properties by default
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

    // skip disabled properties by default
    if (!isset($allprops)) {
        $allprops = null;
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
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
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
                                                           'itemtype' => $itemtype,
                                                           'allprops' => $allprops));

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

?>