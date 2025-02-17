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
use xarDB;
use xarMod;
use xarSecurity;
use sys;

sys::import('xaraya.modules.method');

/**
 * categories userapi groupcount function
 * @extends MethodClass<UserApi>
 */
class GroupcountMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Count number of items per category, or number of categories for each item
     * @param array<string,mixed> $args
     * @param mixed $args ['groupby'] group entries by 'category' or by 'item'
     * @param mixed $args ['modid'] module ID
     * @param mixed $args ['itemid'] optional item ID that we are selecting on
     * @param mixed $args ['itemids'] optional array of item IDs that we are selecting on
     * @param mixed $args ['itemtype'] item type
     * @param mixed $args ['cids'] optional array of cids we're counting for (OR/AND)
     * @param mixed $args ['andcids'] true means AND-ing categories listed in cids
     * @param mixed $args ['groupcids'] the number of categories you want items grouped by
     * @return array|void Returns array of number of items per category, or caterogies per item
     * @see UserApi::groupcount()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Get arguments from argument array
        extract($args);

        // Optional arguments
        if (!isset($groupby)) {
            $groupby = 'category';
        }

        // Security check
        if (!xarSecurity::check('ViewCategoryLink')) {
            return;
        }

        // Get database setup
        $dbconn = xarDB::getConn();

        // Get the field names and LEFT JOIN ... ON ... parts from categories
        // By passing on the $args, we can let leftjoin() create the WHERE for
        // the categories-specific columns too now
        $categoriesdef = $userapi->leftjoin($args);

        // Collection of where-clause expressions.
        $where = [];

        // Filter by itemids.
        if (!empty($itemids) && is_array($itemids)) {
            $itemids = array_filter($itemids, 'is_numeric');
            if (!empty($itemids)) {
                $where[] = $categoriesdef['iid'] . ' in (' . implode(', ', $itemids) . ')';
            }
        }

        // Filter by single itemid.
        if (!empty($itemid) && is_numeric($itemid)) {
            $where[] = $categoriesdef['iid'] . '=' . $itemid;
        }

        // Filter by category.
        if (!empty($categoriesdef['where'])) {
            $where[] = $categoriesdef['where'];
        }

        if ($groupby == 'item') {
            $field = $categoriesdef['item_id'];
        } else {
            $field = $categoriesdef['category_id'];
        }

        $sql = 'SELECT ' . $field . ', COUNT(*)';
        $sql .= ' FROM ' . $categoriesdef['table'];
        $sql .= $categoriesdef['more'];
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' GROUP BY ' . $field;

        $result = $dbconn->Execute($sql);
        if (!$result) {
            return;
        }

        $count = [];
        while (!$result->EOF) {
            $fields = $result->fields;
            $num = array_pop($fields);
            // TODO: use multi-level array for multi-category grouping ?
            $id = join('+', $fields);
            $count[$id] = (int) $num;
            $result->MoveNext();
        }

        $result->Close();

        return $count;
    }
}
