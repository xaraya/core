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
use Query;
use xarDB;
use xarMod;
use xarSecurity;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories userapi getlinkages function
 * @extends MethodClass<UserApi>
 */
class GetlinkagesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get links
     * @param array<string,mixed> $args
     * @param mixed $args ['cids'] array of ids of categories to get linkage for (OR/AND)
     * @param mixed $args ['iids'] array of ids of itens to get linkage for
     * @param mixed $args ['module'] module (if any)
     * @param mixed $args ['itemtype'] item type (if any)
     * @param mixed $args ['numitems'] optional number of items to return
     * @param mixed $args ['startnum'] optional start at this number (1-based)
     * @param mixed $args ['sort'] optional sort by itemid (default) or numlinks
     * @param mixed $args ['andcids'] true means AND-ing categories listed in cids
     * @param mixed $args ['groupcids'] the number of categories you want items grouped by
     * @return array|void Returns array of linkages with keys either item_id or category_id
     * @see UserApi::getlinkages()
     */
    public function __invoke(array $args = [])
    {
        if (!xarSecurity::check('ViewCategoryLink')) {
            return;
        }

        // Get arguments from argument array
        extract($args);

        $xartable = xarDB::getTables();
        sys::import('xaraya.structures.query');
        $q = new Query('SELECT', $xartable['categories_linkage']);
        if (!empty($module)) {
            $q->eq('module_id', xarMod::getID($module));
        }
        if (!empty($itemtype)) {
            $q->eq('itemtype', $itemtype);
        }

        if (!empty($items)) {
            if (is_array($items)) {
                $q->in('item_id', $items);
            } else {
                $q->eq('item_id', $items);
            }
        } elseif (!empty($categories)) {
            if (is_array($categories)) {
                $q->in('item_id', $categories);
            } else {
                $q->eq('category_id', $categories);
            }
        }

        //    $q->qecho();
        if (!$q->run()) {
            return;
        }

        $result = [];
        foreach ($q->output() as $row) {
            if (!empty($items)) {
                $result[$row['item_id']][] = $row;
            } elseif (!empty($categories)) {
                $result[$row['category_id']][] = $row;
            }
        }
        return $result;
    }
}
