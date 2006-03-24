<?php
/**
 * Get information about a defined dynamic object
 *
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
 * get information about a defined dynamic object
 *
 * @author the DynamicData module development team
 * @param $args['objectid'] id of the object you're looking for, or
 * @param $args['moduleid'] module id of the item field to get
 * @param $args['itemtype'] item type of the item field to get
 * @returns array
 * @return array of object definitions
 * @throws DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getobjectinfo($args)
{
    if (empty($args['moduleid']) && !empty($args['modid'])) {
       $args['moduleid'] = $args['modid'];
    }
    return Dynamic_Object_Master::getObjectInfo($args);
}

?>
