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
use xarMod;
use sys;

sys::import('xaraya.modules.method');

/**
 * dynamicdata userapi getitemtypes function
 * @extends MethodClass<UserApi>
 */
class GetitemtypesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Utility function to retrieve the list of itemtypes of this module (if any).
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @return array the itemtypes of this module and their description *
     * @see UserApi::getitemtypes()
     */
    public function __invoke(array $args = [])
    {
        // use module urls here
        $args['linktype'] ??= 'user';
        $args['linkfunc'] ??= 'view';
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        $itemtypes = $userapi->getItemTypes($args);
        return $itemtypes;
    }
}
