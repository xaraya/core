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

sys::import('xaraya.modules.method');

/**
 * dynamicdata userapi getitemlinks function
 * @extends MethodClass<UserApi>
 */
class GetitemlinksMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * utility function to pass individual item links to whoever
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args['itemtype'] item type (optional)<br/>
     * array    $args['itemids'] array of item ids to get
     * @return array containing the itemlink(s) for the item(s).
     * @see UserApi::getitemlinks()
     */
    public function __invoke(array $args = [])
    {
        // use module urls here
        $args['linktype'] ??= 'user';
        $args['linkfunc'] ??= 'display';
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        $itemlinks = $userapi->getItemLinks($args);
        return $itemlinks;
    }
}
