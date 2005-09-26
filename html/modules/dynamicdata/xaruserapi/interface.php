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
 * get a dynamic object interface
 *
 * @author the DynamicData module development team
 * @param $args['objectid'] id of the object you're looking for, or
 * @param $args['moduleid'] module id of the item field to get
 * @param $args['itemtype'] item type of the item field to get
 * @param $args['classname'] optional classname (e.g. <module>_Dynamic_Object[_Interface])
 * @returns object
 * @return a particular Dynamic Object Interface
 */
function &dynamicdata_userapi_interface($args)
{
    if (empty($args['moduleid']) && !empty($args['module'])) {
       $args['moduleid'] = xarModGetIDFromName($args['module']);
    }
    if (empty($args['moduleid']) && !empty($args['modid'])) {
       $args['moduleid'] = $args['modid'];
    }
    $result = Dynamic_Object_Master::getObjectInterface($args);
    return $result;
}

?>
