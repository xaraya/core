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
 * get a dynamic object list
 *
 * @author the DynamicData module development team
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['objectid'] id of the object you're looking for, or<br/>
 *        integer  $args['moduleid'] module id of the item field to get<br/>
 *        string   $args['itemtype'] item type of the item field to get
 * @return object a particular DataObjectList
 */
function dynamicdata_userapi_getobjectlist(Array $args=array())
{
    sys::import('modules.dynamicdata.class.objects.master');
    $list = DataObjectMaster::getObjectList($args);
    return $list;
}

?>
