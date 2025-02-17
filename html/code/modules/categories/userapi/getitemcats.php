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
 * categories userapi getitemcats function
 * @extends MethodClass<UserApi>
 */
class GetitemcatsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get an array of assigned category details for a specific item, limiting by a base cid if required.
     * Get categories for an item, optionally limiting to just one category branch (to be expanded to allow base categories by name).
     * @param mixed $args ['basecid'] optional base cid under which the returned categories must lie
     * @param mixed $args ['basecids'] optional array of base cids under which the returned categories must lie
     * @param mixed $args ['module'] name of the module; or
     * @param mixed $args ['modid'] module ID
     * @param mixed $args ['itemtype'] item type
     * @param mixed $args ['itemid'] item ID
     * @return array|bool Returns category info on success, false on failure.
     * @see UserApi::getitemcats()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        /**
         * Pending
         * TODO: allow ordering of the results by name, description etc.
         */

        // Get arguments from argument array
        extract($args);

        // Requires: module, itemtype, itemid (but not validated)

        // Default the module name.
        if (empty($modid) && empty($module)) {
            $module = xarMod::getName();
        }

        // Get module ID if only a name provided.
        if (empty($modid) && !empty($module)) {
            $args['modid'] = xarMod::getRegID($module);
        }

        // Get the list of assigned categories for this module item.
        $args['groupby'] = 'category';
        $catlist = $userapi->groupcount(
            $args
        );

        // Throw back errors as an empty list.
        if (empty($catlist)) {
            return [];
        }

        // Flip the array, so the cat IDs are the values.
        $catlist = array_keys($catlist);

        if (!isset($basecids) || !is_array($basecids)) {
            $basecids = [];
        }

        if (isset($basecid)) {
            array_push($basecids, $basecid);
        }

        // Initialise the result array.
        $result = [];

        // Check whether we want to restrict the catergories by one or more base categories.
        // TODO: when categories supports 'base' categories (category itemtypes?) then add
        // another (much simpler) section here.
        if (!empty($basecids)) {
            // Get the ancestors (including self) of these categories.
            // Included, is a list of descendants for each category.
            $ancestors = $userapi->getancestors(
                ['cids' => $catlist, 'self' => true, 'descendants' => 'list']
            );

            $resultcids = [];

            foreach ($basecids as $basecid) {
                // Check each category to see if the base is an ancestor.
                // If base category is an ancestor, then we want to look at it.
                if (isset($ancestors[$basecid]['descendants'])) {
                    // The cats we want will be the insersection of the catlist for the item,
                    // and the descendants of this base.
                    $resultcids = array_merge($resultcids, array_intersect($ancestors[$basecid]['descendants'], $catlist));
                }
            }

            // If the intersect was not empty, then add the details of those
            // categories to the result list.
            if (!empty($resultcids)) {
                foreach ($resultcids as $cid) {
                    if (!isset($result[$cid])) {
                        $result[$cid] = $ancestors[$cid];
                    }
                }
            }
        } else {
            // Get the details for these categories, with no restrictions.
            // This is almost a 'passthrough'.
            // TODO: include the 'basecid' stuff directly in 'getcatinfo', or
            // leave getcatinfo to handle the raw database stuff and this to do
            // the specials?
            $result = $userapi->getcatinfo(['cids' => $catlist]);
        }

        return $result;
    }
}
