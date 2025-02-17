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
use xarMod;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories userapi deepcount function
 * @extends MethodClass<UserApi>
 */
class DeepcountMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Count number of items per category, or number of categories for each item
     * @param mixed $args ['groupby'] group entries by 'category' or by 'item'
     * @param mixed $args ['modid'] module�s ID
     * @param mixed $args ['itemtype'] item type
     * @param mixed $args ['cids'] optional array of cids we're counting for (OR/AND)
     * @param mixed $args ['andcids'] true means AND-ing categories listed in cids
     * @param mixed $args ['groupcids'] the number of categories you want items grouped by
     * @return array Number of items per category, or caterogies per item
     * @see UserApi::deepcount()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        $count = [];

        // Get the non-zero counts.
        // These are the leaf nodes that we then extend back to the top ancestor(s).
        $catcount = $userapi->groupcount($args);

        // Throw back errors as an empty list.
        if (empty($catcount)) {
            return $count;
        }

        $allcounts = $catcount;

        // Array of category IDs.
        $catlist = array_keys($catcount);

        // Get the ancestors (including self).
        $ancestors = $userapi->getancestors(['cids' => $catlist, 'self' => true]);

        // For each non-zero category count, traverse the ancestors and add on the counts.
        $allcounts[0] = 0;
        foreach ($catcount as $cat => $count) {
            // Keep track of categories visited to avoid infinite loops.
            $done = [];
            $nextcat = $ancestors[$cat]['parent'];
            while ($nextcat > 0 && !isset($done[$nextcat])) {
                $done[$nextcat] = $nextcat;
                if (!isset($allcounts[$nextcat])) {
                    $allcounts[$nextcat] = $count;
                } else {
                    $allcounts[$nextcat] += $count;
                }
                $nextcat = $ancestors[$nextcat]['parent'];
            }
            $allcounts[0] += $count;
        }

        return $allcounts;
    }
}
