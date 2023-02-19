<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * delete a dynamic object and its properties
 *
 * @author the DynamicData module development team
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['objectid'] object id of the object to delete
 * @return integer object ID on success, null on failure
 */
function dynamicdata_adminapi_deleteobject(Array $args=array())
{
    $objectid = DataObjectMaster::deleteObject($args);
    return $objectid;
}
