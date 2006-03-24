<?php
/**
 * Delete a dynamic object and its properties
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
 * delete a dynamic object and its properties
 *
 * @author the DynamicData module development team
 * @param $args['objectid'] object id of the object to delete
 * @returns int
 * @return object ID on success, null on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_deleteobject($args)
{
    $objectid = Dynamic_Object_Master::deleteObject($args);
    return $objectid;
}
?>