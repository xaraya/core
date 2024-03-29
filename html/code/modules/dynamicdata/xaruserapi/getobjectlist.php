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
 * @param array<string, mixed> $args array of optional parameters<br/>
 * with
 *        integer  $args['objectid'] id of the objectlist you're looking for, or<br/>
 *        string   $args['name'] name of the objectlist you're looking for, or<br/>
 *        integer  $args['moduleid'] module id of the objectlist to get +<br/>
 *        string   $args['itemtype'] item type of the objectlist to get
 * @return object a particular DataObjectList
 */
function dynamicdata_userapi_getobjectlist(array $args = [], $context = null)
{
    if (empty($args['objectid']) && empty($args['name'])) {
        sys::import('modules.dynamicdata.class.objects.descriptor');
        $args = DataObjectDescriptor::getObjectID($args);
    }
    sys::import('modules.dynamicdata.class.objects.factory');
    // set context if available in function
    $list = DataObjectFactory::getObjectList($args, $context);
    return $list;
}
