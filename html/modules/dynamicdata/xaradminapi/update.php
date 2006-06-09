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
 * update an item (the whole item or the dynamic data fields of it)
 *
 * @author the DynamicData module development team
 * @param $args['itemid'] item id of the original item
 * @param $args['modid'] module id for the original item
 * @param $args['itemtype'] item type of the original item
 * @param $args['values'] array of prop_id => value, or
 * @param $args['fields'] array containing the field definitions and values
 * @returns mixed
 * @return item id on success, null on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_update($args)
{
    extract($args);

    $invalid = array();
    if (!isset($itemid) || !is_numeric($itemid) || empty($itemid)) { // we can't accept item id 0 here
        $invalid[] = 'item id';
    }
    if (!isset($modid) || !is_numeric($modid)) {
        $invalid[] = 'module id';
    }
    if ((isset($fields) && is_array($fields)) ||
        (isset($values) && is_array($values)) ) {
    } else {
        $invalid[] = xarML('fields or values');
    }
    if (count($invalid) > 0) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array(join(', ',$invalid), 'admin', 'update', 'DynamicData');
        throw new BadParameterException($vars,$msg);
    }

    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $itemtype = 0;
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if(!xarSecurityCheck('EditDynamicDataItem',1,'Item',"$modid:$itemtype:$itemid")) return;

    if (!isset($fields) || !is_array($fields)) {
        $fields = array();
    }
    if (!isset($values) || !is_array($values)) {
        $values = array();
    }

// TODO: test this
    $myobject = & Dynamic_Object_Master::getObject(array('moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'itemid'   => $itemid));
    if (empty($myobject)) return;

    $myobject->getItem();

    if (count($values) == 0) {
        foreach ($fields as $field) {
            if (isset($field['value'])) {
                $values[$field['name']] = $field['value'];
            }
        }
    }
    $itemid = $myobject->updateItem($values);
    return $itemid;
}
?>
