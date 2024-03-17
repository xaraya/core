<?php
/**
 * Count number of items held by this module
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
 * utility function to count the number of items held by this module
 *
 * @author the DynamicData module development team
 * @param array<string, mixed> $args array of optional parameters<br/>
 * with
 *        integer  $args['objectid'] id of the objectlist you're looking for, or<br/>
 *        string   $args['name'] name of the objectlist you're looking for, or<br/>
 *        integer  $args['moduleid'] module id of the objectlist to get +<br/>
 *        string   $args['itemtype'] item type of the objectlist to get
 * @return integer|void number of items held by this module
 */
function dynamicdata_userapi_countitems(array $args = [], $context = null)
{
    if (empty($args['objectid']) && empty($args['name'])) {
        $args = DataObjectDescriptor::getObjectID($args);
    }
    $mylist = DataObjectFactory::getObjectList($args, $context);
    if (!isset($mylist)) {
        return;
    }

    return $mylist->countItems();
}
