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
use xarController;
use xarMod;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles userapi getitemlinks function
 * @extends MethodClass<UserApi>
 */
class GetitemlinksMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * utility function to pass individual item links to whoever
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args ['itemtype'] item type (optional)<br/>
     * array    $args ['itemids'] array of item ids to get
     * @return array|void the itemlink(s) for the item(s).
     * @see UserApi::getitemlinks()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        $itemlinks = [];
        if (!xarSecurity::check('ViewRoles', 0)) {
            return $itemlinks;
        }

        foreach ($args['itemids'] as $itemid) {
            $item = $userapi->get(['id' => $itemid]);
            if (!isset($item)) {
                return;
            }
            $itemlinks[$itemid] = ['url' => xarController::URL(
                'roles',
                'user',
                'display',
                ['id' => $itemid]
            ),
                'title' => xarML('Display User'),
                'label' => xarVar::prepForDisplay($item['name'])];
        }
        return $itemlinks;
    }
}
