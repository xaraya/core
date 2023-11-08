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
 * @return integer|void number of items held by this module
 */
function dynamicdata_userapi_countitems(array $args = [])
{
    if (empty($args['objectid']) && empty($args['name'])) {
        $args = DataObjectDescriptor::getObjectID($args);
    }
    $mylist = DataObjectFactory::getObjectList($args);
    if (!isset($mylist)) {
        return;
    }

    return $mylist->countItems();
}
