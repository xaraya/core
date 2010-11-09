<?php
/**
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
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
