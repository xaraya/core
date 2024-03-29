<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Get field properties for a specific module + item type
 *
 * @author the DynamicData module development team
 * @param array<string, mixed> $args array of optional parameters<br/>
 * with
 *        integer  $args['objectid'] object id of the properties to get, or<br/>
 *        integer  $args['name'] object name of the properties to get, or<br/>
 *        string   $args['module'] module name of the properties, or<br/>
 *        integer  $args['moduleid'] module id of the properties to get +<br/>
 *        string   $args['itemtype'] item type of the properties to get<br/>
 *        array    $args['fieldlist'] array of field labels to retrieve (default is all)<br/>
 *        integer  $args['status'] limit to property fields of a certain status (e.g. active)<br/>
 *        integer  $args['allprops'] skip disabled properties by default<br/>
 *        boolena  $args['static'] include the static properties (= module tables) too (default no)
 * @return mixed value of the field, or false on failure
 * @throws BadParameterException
 */
function dynamicdata_userapi_getprop(array $args = [], $context = null)
{
    static $propertybag = [];

    if (empty($args['objectid']) && empty($args['name'])) {
        $args = DataObjectDescriptor::getObjectID($args);
    }
    $args = DataObjectFactory::getObjectInfo($args);
    if (empty($args)) {
        return [];
    }

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

    $invalid = [];
    if (!isset($module_id) || !is_numeric($module_id)) {
        $invalid[] = 'module id';
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (count($invalid) > 0) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = [join(', ', $invalid), 'user', 'getprop', 'DynamicData'];
        throw new BadParameterException($vars, $msg);
    }

    if (empty($static) && isset($propertybag["$module_id:$itemtype"])) {
        if (!empty($fieldlist)) {
            $myfields = [];
            foreach ($fieldlist as $name) {
                if (isset($propertybag["$module_id:$itemtype"][$name])) {
                    $myfields[$name] = $propertybag["$module_id:$itemtype"][$name];
                }
            }
            return $myfields;
        } elseif (isset($status)) {
            $myfields = [];
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
    if (empty($objectid)) {
        $objectid = null;
    }

    $fields = DataPropertyMaster::getProperties(['objectid' => $objectid,
                                                'moduleid' => $module_id,
                                                'itemtype' => $itemtype,
                                                'allprops' => $allprops]);
    if (!empty($static)) {
        // get the list of static properties for this module
        $staticlist = xarMod::apiFunc(
            'dynamicdata',
            'util',
            'getstatic',
            ['module_id' => $module_id,
            'itemtype' => $itemtype],
            $context
        );
        // TODO: watch out for conflicting property ids ?
        $fields = array_merge($staticlist, $fields);
    }

    if (empty($static)) {
        $propertybag["$module_id:$itemtype"] = $fields;
    }
    if (!empty($fieldlist)) {
        $myfields = [];
        // this should return the fields in the right order, normally
        foreach ($fieldlist as $name) {
            if (isset($fields[$name])) {
                $myfields[$name] = $fields[$name];
            }
        }
        return $myfields;
    } elseif (isset($status)) {
        $myfields = [];
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
