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

/**
 * categories userapi getlinks function
 * @extends MethodClass<UserApi>
 */
class GetlinksMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Get links
     * @param array<string,mixed> $args
     * @param mixed $args ['cids'] array of ids of categories to get linkage for (OR/AND)
     * @param mixed $args ['iids'] array of ids of itens to get linkage for
     * @param mixed $args ['modid'] module ID
     * @param mixed $args ['itemtype'] item type (if any)
     * @param mixed $args ['numitems'] optional number of items to return
     * @param mixed $args ['startnum'] optional start at this number (1-based)
     * @param mixed $args ['sort'] optional sort by itemid (default) or numlinks
     * @param mixed $args ['reverse'] if set to 1 the return will have as keys the 'iids'
     * else the keys are the 'cids'
     * @param mixed $args ['andcids'] true means AND-ing categories listed in cids
     * @param mixed $args ['groupcids'] the number of categories you want items grouped by
     * @return array|void Returns item array, or false on failure
     * @see UserApi::getlinks()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Get arguments from argument array
        extract($args);

        if (empty($reverse)) {
            $reverse = 0;
        }

        // Security check
        if (!$this->sec()->checkAccess('ViewCategoryLink', 0)) {
            return [];
        }

        // Get database setup
        $dbconn = $this->db()->getConn();

        // Get the field names and LEFT JOIN ... ON ... parts from categories
        // By passing on the $args, we can let leftjoin() create the WHERE for
        // the categories-specific columns too now
        $categoriesdef = $userapi->leftjoin($args);

        // Get item IDs
        $sql = 'SELECT ' . $categoriesdef['category_id'] . ', ' . $categoriesdef['item_id'];
        $sql .= ' FROM ' . $categoriesdef['table'];
        $sql .= $categoriesdef['more'];
        if (!empty($categoriesdef['where'])) {
            $sql .= ' WHERE ' . $categoriesdef['where'];
        }

        if (!empty($sort)) {
            if ($sort == 'itemid') {
                $sql .= " ORDER BY " . $categoriesdef['item_id'] . " ASC";
            } else {
                // no way to sort by number of links in the query itself
            }
        }

        if (!empty($numitems)) {
            if (empty($startnum)) {
                $startnum = 1;
            }
            $result = $dbconn->SelectLimit($sql, $numitems, $startnum - 1);
        } else {
            $result = $dbconn->Execute($sql);
        }
        if (!$result) {
            return;
        }

        // Makes the linkages array to be returned
        $answer = [];
        while ($result->next()) {
            $fields = $result->fields;
            $iid = array_pop($fields);
            if ($reverse == 1) {
                // the list of categories is in the N first fields here
                if (isset($cids) && count($cids) > 1 && $andcids) {
                    $answer[$iid] = $fields;
                } elseif (isset($groupcids) && $groupcids > 1) {
                    $answer[$iid] = $fields;
                    // we get 1 category per record here
                } else {
                    $answer[$iid][] = $fields[0];
                }
            } else {
                // TODO: use multi-level array for multi-category grouping ?
                $cid = join('+', $fields);
                $answer[$cid][] = $iid;
            }
        }

        $result->Close();

        if (!empty($sort) && $sort == 'numlinks' && count($answer) > 0) {
            // TODO: find some way to sort first on count, and then on itemid
            uasort($answer, [$this, 'getlinks_sortbycount']);
        }

        // Return Array with linkage
        return $answer;
    }

    public function getlinks_sortbycount($a, $b)
    {
        $ca = count($a);
        $cb = count($b);
        if ($ca == $cb) {
            return 0;
        }
        return ($ca > $cb ? 1 : -1);
    }
}
