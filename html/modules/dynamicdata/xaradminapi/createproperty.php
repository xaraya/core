<?php
/**
 * Create a new property field for an object
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * create a new property field for an object
 *
 * @author the DynamicData module development team
 * @param $args['name'] name of the property to create
 * @param $args['label'] label of the property to create
 * @param $args['objectid'] object id of the property to create
 * @param $args['moduleid'] module id of the property to create
 * @param $args['itemtype'] item type of the property to create
 * @param $args['type'] type of the property to create
 * @param $args['default'] default of the property to create
 * @param $args['source'] data source for the property (dynamic_data table or other)
 * @param $args['status'] status of the property to create (disabled/active/...)
 * @param $args['order'] order of the property to create
 * @param $args['validation'] validation of the property to create
 * @returns int
 * @return property ID on success, null on failure
 * @raise BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_createproperty($args)
{
    extract($args);

    // Required arguments
    $invalid = array();
    if (!isset($name) || !is_string($name)) {
        $invalid[] = 'name';
    }
    if (!isset($type) || !is_numeric($type)) {
        $invalid[] = 'type';
    }
    if (count($invalid) > 0) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array(join(', ',$invalid), 'admin', 'createproperty', 'DynamicData');
        throw new BadParameterException($vars,$msg);
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if(!xarSecurityCheck('AdminDynamicDataField',1,'Field',"$name:$type:All")) return;

    if (empty($moduleid)) {
        // defaults to the current module
        $moduleid = xarModGetIDFromName(xarModGetName());
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }
    $itemid = 0;

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if(!xarSecurityCheck('AdminDynamicDataItem',1,'Item',"$moduleid:$itemtype:All")) return;

    // get the properties of the 'properties' object
    $fields =& xarModAPIFunc('dynamicdata','user','getprop',
                            array('objectid' => 2)); // the properties

    $values = array();
    // the acceptable arguments correspond to the property names !
    foreach ($fields as $name => $field) {
        if (isset($args[$name])) {
            $values[$name] = $args[$name];
        }
    }
/* this is already done via the table definition of xar_dynamic_properties
    // fill in some defaults if necessary
    if (empty($fields['source']['value'])) {
        $fields['source']['value'] = 'dynamic_data';
    }
    if (empty($fields['validation']['value'])) {
        $fields['validation']['value'] = '';
    }
*/

    $propid = xarModAPIFunc('dynamicdata', 'admin', 'create',
                            array('modid'    => xarModGetIDFromName('dynamicdata'), //$moduleid,
                                  'itemtype' => 1, //$itemtype,
                                  'itemid'   => $itemid,
                                  'values'   => $values));
    if (!isset($propid)) return;
    return $propid;
}
?>
