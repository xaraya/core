<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\DataObject\UserApi;

use Xaraya\DataObject\MethodClass;
use Xaraya\DataObject\UserApi;
use DataPropertyMaster;
use xarMod;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata userapi getitemsforview function
 * @extends MethodClass<UserApi>
 */
class GetitemsforviewMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * return the properties and items
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @return array containing a reference to the properties and a reference to the items
     * @TODO: move this to some common place in Xaraya (base module ?)
     * @see UserApi::getitemsforview()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        if (empty($args['fieldlist']) && empty($args['status'])) {
            // get the Active properties only (not those for Display Only)
            $args['status'] = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE;
        }
        $args['getobject'] = 1;
        $object =  $userapi->getitems($args);
        if (!isset($object)) {
            return [[], []];
        }
        $properties = & $object->getProperties();
        $items = & $object->items;
        return [& $properties, & $items];
    }
}
