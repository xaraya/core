<?php

/**
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Categories\UserApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Categories\UserApi;
use xarController;
use xarMod;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories userapi getitemlinks function
 * @extends MethodClass<UserApi>
 */
class GetitemlinksMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Utility function to pass individual item links to whoever
     * @param mixed $args ['itemtype'] item type (optional)
     * @param mixed $args ['itemids'] array of item ids to get
     * @return array Returns array containing the itemlink(s) for the item(s).
     * @see UserApi::getitemlinks()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        $itemlinks = [];
        $catlist = $userapi->getcatinfo(['cids' => $args['itemids']]);
        if (!isset($catlist) || !is_array($catlist) || count($catlist) == 0) {
            return $itemlinks;
        }

        foreach ($args['itemids'] as $itemid) {
            if (!isset($catlist[$itemid])) {
                continue;
            }
            $itemlinks[$itemid] = ['url'   => xarController::URL(
                'categories',
                'user',
                'main',
                ['catid' => $itemid]
            ),
                'title' => xarVar::prepForDisplay($catlist[$itemid]['name']),
                'label' => xarVar::prepForDisplay($catlist[$itemid]['description'])];
        }
        return $itemlinks;
    }
}
