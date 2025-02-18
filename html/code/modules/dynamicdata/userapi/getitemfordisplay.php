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
use xarMod;
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata userapi getitemfordisplay function
 * @extends MethodClass<UserApi>
 */
class GetitemfordisplayMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * return the properties for an item
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @return array containing a reference to the properties of the item
     * @TODO: move this to some common place in Xaraya (base module ?)
     * @see UserApi::getitemfordisplay()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        $args['getobject'] = 1;
        $object = $userapi->getitem($args);
        $properties = [];
        if (isset($object)) {
            $properties = & $object->getProperties();
        }
        $item = [& $properties];
        return $item;
    }
}
