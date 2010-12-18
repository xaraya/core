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
 * get a dynamic object
 *
 * @author the DynamicData module development team
 * @param array    $args array of optional parameters<br/>
 *        integer  $args['objectid'] id of the object you're looking for, or<br/>
 *        integer  $args['moduleid'] module id of the item field to get<br/>
 *        string   $args['itemtype'] item type of the item field to get
 * @return object a particular DataObject
 */
function &dynamicdata_userapi_getobject(Array $args=array())
{
    return DataObjectMaster::getObject($args);
}

?>
