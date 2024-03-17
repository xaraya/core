<?php
/**
 * Get the next itemtype of extended objects pertaining to a given module
 *
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author random <mfl@netspan.ch>
*/
sys::import('modules.dynamicdata.class.userapi');
/**
 * get the next itemtype of objects pertaining to a given module
 *
 * @uses Xaraya\DataObject\UserApi::getModuleItemTypes()
 * @author the DynamicData module development team
 * @param array<string, mixed> $args array of optional parameters<br/>
 * @return int of object definitions
 * @todo combine this with DataObject::getNextItemType()?
 */
function dynamicdata_adminapi_getnextitemtype($args = [], $context = null)
{
    extract($args);
    if (empty($module_id)) {
        $module_id = 182;
    }
    $types = Xaraya\DataObject\UserApi::getModuleItemTypes($module_id);
    $ids = array_keys($types);
    sort($ids);
    $lastid = array_pop($ids);
    return $lastid + 1;
    /**
    // DD and DD-type modules go one way
    if ($module_id == 182 || $module_id == 27) return $lastid + 1;
    // other module go another
    else return max(1000,$lastid + 1);
     */
}
