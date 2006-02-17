<?php
/**
 * Return the properties and items
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
 * return the properties and items
 *
 * @param array $args array containing the items or fields to show
 * @return array containing a reference to the properties and a reference to the items
 * @TODO: move this to some common place in Xaraya (base module ?)
 */
function dynamicdata_userapi_getitemsforview($args)
{
    if (empty($args['fieldlist']) && empty($args['status'])) {
        // get the Active properties only (not those for Display Only)
        $args['status'] = 1;
    }
    $args['getobject'] = 1;
    $object =  xarModAPIFunc('dynamicdata','user','getitems',$args);
    if (!isset($object)) {
        return array(array(), array());
    }
    $properties = & $object->getProperties();
    $items = & $object->items;
    return array(& $properties, & $items);
}

?>