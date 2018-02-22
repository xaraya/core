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
 * get the list of defined dynamic objects
 *
 * @author the DynamicData module development team
 * @param array    $args array of optional parameters<br/>
 * @return array of object definitions
 * @throws DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getobjects($args = array())
{
    $objects =  DataObjectMaster::getObjects($args);
    return $objects;
}

?>
