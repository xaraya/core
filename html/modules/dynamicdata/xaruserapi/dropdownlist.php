<?php
/**
 * File: $Id$
 *
 * Get an array of DD items (id => field) for use in dropdown lists
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
*/
/**
 * Get an array of DD items (itemid => fieldvalue) for use in dropdown lists
 *
 * E.g. to specify the parent of an item for parent-child relationships,
 * add a dynamic data field of type Dropdown List with the validation rule
 * xarModAPIFunc('dynamicdata','user','dropdownlist',array('field' => 'name','module' => 'dynamicdata','itemtype' => 2))
 *
 * Note : for additional optional parameters, see the getitems() function
 *
 * @author the DynamicData module development team
 * @param $args['field'] field to use in the dropdown list (required here)
 * @param $args['showoutput'] go through showOutput() for this field (default false)
 * @param $args['module'] module name of the item fields to get, or
 * @param $args['modid'] module id of the item fields to get +
 * @param $args['itemtype'] item type of the item fields to get, or
 * @param $args['table'] database table to turn into an object
 * @returns array
 * @return array of (itemid => fieldvalue), or false on failure
 * @raise BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_userapi_dropdownlist($args)
{
    if (empty($args['field'])) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                     'field', 'user', 'dropdownlist', 'DynamicData');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // put the field in the required fieldlist
    $args['fieldlist'] = array($args['field']);

    // get back the object
    $args['getobject'] = 1;

    $object =& xarModAPIFunc('dynamicdata','user','getitems',$args);
    if (!isset($object)) return;

    $field = $args['field'];
    if (!isset($object->properties[$field])) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                     'property', 'user', 'dropdownlist', 'DynamicData');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    // Fill in the dropdown list
    $list = array();
    $list[0] = '';
    foreach ($object->items as $itemid => $item) {
        if (!isset($item[$field])) continue;
        if (!empty($args['showoutput'])) {
            $value = $object->properties[$field]->showOutput(array('value'=>$item[$field]));
            if (isset($value)) {
                $list[$itemid] = $value;
            }
        } else {
            $list[$itemid] = xarVarPrepForDisplay($item[$field]);
        }
    }
    return $list;
}

?>
