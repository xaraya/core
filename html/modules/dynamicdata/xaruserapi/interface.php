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
 * get a dynamic object interface
 *
 * @author the DynamicData module development team
 * @param id $args['objectid'] id of the object you're looking for, or
 * @param id $args['moduleid'] module id of the item field to get
 * @param int $args['itemtype'] item type of the item field to get
 * @param string $args['classname'] optional classname (e.g. <module>_Dynamic_Object[_Interface])
 * @return object a particular Dynamic Object Interface
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
