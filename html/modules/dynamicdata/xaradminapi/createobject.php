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
 * create a new dynamic object
 *
 * @author the DynamicData module development team
 * @param $args['name'] name of the object to create
 * @param $args['label'] label of the object to create
 * @param $args['moduleid'] module id of the object to create
 * @param $args['itemtype'] item type of the object to create
 * @param $args['urlparam'] URL parameter to use for the item (itemid, exid, aid, ...)
 * @param $args['config'] some configuration for the object (free to define and use)
 * @param $args['objectid'] object id of the object to create (for import only)
 * @param $args['maxid'] for purely dynamic objects, the current max. itemid (for import only)
 * @param $args['parent'] itemtype of the parent of this object
 * @returns int
 * @return object ID on success, null on failure
 * @raise BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_createobject($args)
{
    $objectid = Dynamic_Object_Master::createObject($args);
    return $objectid;
}
?>
