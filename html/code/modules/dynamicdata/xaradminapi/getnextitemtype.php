<?php
/**
 * Get the next itemtype of extended objects pertaining to a given module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata module
 * @author random <mfl@netspan.ch>
*/
/**
 * get the next itemtype of objects pertaining to a given module
 *
 * @author the DynamicData module development team
 * @returns array
 * @return array of object definitions
 * @raise DATABASE_ERROR, NO_PERMISSION
 * @todo should we wrap this?
 */
function dynamicdata_adminapi_getnextitemtype($args = array())
{
    extract($args);
    if (empty($module_id)) $module_id = 182;
    $types = DataObjectMaster::getModuleItemTypes(array('moduleid' => $module_id));
    $ids = array_keys($types);
    sort($ids);
    $lastid = array_pop($ids);
    return $lastid + 1;
    // DD and DD-type modules go one way
    if ($module_id == 182 || $module_id == 27) return $lastid + 1;
    // other module go another
    else return max(1000,$lastid + 1);
}
?>