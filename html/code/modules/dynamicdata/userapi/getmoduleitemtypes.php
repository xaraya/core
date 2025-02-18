<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\UserApi;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\UserApi;
use BadParameterException;
use xarMod;
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata userapi getmoduleitemtypes function
 * @todo overlaps with static method in UserApi
 * @extends MethodClass<UserApi>
 */
class GetmoduleitemtypesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * utility function to retrieve the list of item types of a module (if any)
     * @uses \Xaraya\Modules\DynamicData\UserApi::findModuleItemTypes()
     * @todo remove this before it can propagate - too late, sorry
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @return array containing the item types and their description
     * @see UserApi::getmoduleitemtypes()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var int $moduleid */
        // Argument checks
        if (empty($moduleid)) {
            throw new BadParameterException('moduleid');
        }
        $native ??= true;
        $extensions ??= true;

        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        return $userapi::findModuleItemTypes($moduleid, $native, $extensions);
    }
}
