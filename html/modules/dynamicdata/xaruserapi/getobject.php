<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * get a dynamic object
 *
 * @author the DynamicData module development team
 * @param $args['objectid'] id of the object you're looking for, or
 * @param $args['moduleid'] module id of the item field to get
 * @param $args['itemtype'] item type of the item field to get
 * @returns object
 * @return a particular Dynamic Object
 */
function &dynamicdata_userapi_getobject($args)
{
    if (empty($args['itemtype']) && !empty($args['name'])) {
        $info = Dynamic_Object_Master::getObjectInfo($args);
        $args['moduleid'] = $info['moduleid'];
        $args['itemtype'] = $info['itemtype'];
    }
    if (empty($args['moduleid']) && !empty($args['module'])) {
       $args['moduleid'] = xarModGetIDFromName($args['module']);
    }
    if (empty($args['moduleid']) && !empty($args['modid'])) {
       $args['moduleid'] = $args['modid'];
    }
    $result = Dynamic_Object_Master::getObject($args);
    return $result;
}

?>
