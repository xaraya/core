<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\AdminApi;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\AdminApi;
use Xaraya\Modules\DynamicData\UserApi;
use DataObject;
use xarMod;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata adminapi getnextitemtype function
 * @extends MethodClass<AdminApi>
 */
class GetnextitemtypeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * get the next itemtype of objects pertaining to a given module
     * @uses \Xaraya\Modules\DynamicData\UserApi::findModuleItemTypes()
     * @author the DynamicData module development team
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @return int of object definitions
     * @todo combine this with DataObject::getNextItemType()?
     * @see AdminApi::getnextitemtype()
     */
    public function __invoke($args = [])
    {
        extract($args);
        if (empty($module_id)) {
            $module_id = 182;
        }
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        $types = $userapi::findModuleItemTypes($module_id);
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
}
