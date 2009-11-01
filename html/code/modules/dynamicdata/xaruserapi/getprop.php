<?php
/**
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Get field properties for a specific module + item type
 *
 * @author the DynamicData module development team
 * @param $args['objectid'] object id of the properties to get
 * @param $args['module'] module name of the item fields, or
 * @param $args['module_id'] module id of the item field to get
 * @param $args['itemtype'] item type of the item field to get
 * @param $args['fieldlist'] array of field labels to retrieve (default is all)
 * @param $args['status'] limit to property fields of a certain status (e.g. active)
 * @param $args['allprops'] skip disabled properties by default
 * @param $args['static'] include the static properties (= module tables) too (default no)
 * @return mixed value of the field, or false on failure
 * @throws BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getprop($args)
{
    static $propertybag = array();

    $args = DataObjectMaster::getObjectInfo($args);
    if (empty($args)) return array();

    extract($args);
    $module_id = $args['moduleid'];

    if (empty($module_id) && !empty($module)) {
        $module_id = xarMod::getRegID($module);
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
    if (!isset($module_id) || !is_numeric($module_id)) {
        $invalid[] = 'module id';
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (count($invalid) > 0) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array(join(', ',$invalid), 'user', 'getprop', 'DynamicData');
        throw new BadParameterException($vars,$msg);
    }

    if (empty($static) && isset($propertybag["$module_id:$itemtype"])) {
        if (!empty($fieldlist)) {
            $myfields = array();
            foreach ($fieldlist as $name) {
                if (isset($propertybag["$module_id:$itemtype"][$name])) {
                    $myfields[$name] = $propertybag["$module_id:$itemtype"][$name];
                }
            }
            return $myfields;
        } elseif (isset($status)) {
            $myfields = array();
            foreach ($propertybag["$module_id:$itemtype"] as $name => $field) {
                if ($field['status'] == $status) {
                    $myfields[$name] = $propertybag["$module_id:$itemtype"][$name];
                }
            }
            return $myfields;
        } else {
            return $propertybag["$module_id:$itemtype"];
        }
    }

    $fields = DataPropertyMaster::getProperties(array('objectid' => $objectid,
                                                           'moduleid' => $module_id,
                                                           'itemtype' => $itemtype,
                                                           'allprops' => $allprops));
    if (!empty($static)) {
        // get the list of static properties for this module
        $staticlist = xarMod::apiFunc('dynamicdata','util','getstatic',
                                    array('module_id' => $module_id,
                                          'itemtype' => $itemtype));
// TODO: watch out for conflicting property ids ?
        $fields = array_merge($staticlist,$fields);
    }

    if (empty($static)) {
        $propertybag["$module_id:$itemtype"] = $fields;
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
