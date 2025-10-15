<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\UserApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\UserApi;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles userapi getitemtypes function
 * @extends MethodClass<UserApi>
 */
class GetitemtypesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Utility function to retrieve the list of itemtypes of this module (if any).
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @return array the itemtypes of this module and their description *
     * @see UserApi::getitemtypes()
     */
    public function __invoke(array $args = [])
    {
        return $this->mod()->apiFunc('dynamicdata', 'user', 'getmoduleitemtypes', ['moduleid' => 27, 'native' => false]);
    }
}
