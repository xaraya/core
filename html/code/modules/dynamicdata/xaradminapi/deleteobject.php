<?php
/**
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * delete a dynamic object and its properties
 *
 * @author the DynamicData module development team
 * @param $args['objectid'] object id of the object to delete
 * @return int object ID on success, null on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_deleteobject($args)
{
    $objectid = DataObjectMaster::deleteObject($args);
    return $objectid;
}
?>
