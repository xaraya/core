<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @subpackage categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/147.html
 *
 */

/**
 * Count number of items per category, or number of categories for each item
 * @param array $args
 * @param $args['groupby'] group entries by 'category' or by 'item'
 * @param $args['modid'] module ID
 * @param $args['itemid'] optional item ID that we are selecting on
 * @param $args['itemids'] optional array of item IDs that we are selecting on
 * @param $args['itemtype'] item type
 * @param $args['cids'] optional array of cids we're counting for (OR/AND)
 * @param $args['andcids'] true means AND-ing categories listed in cids
 * @param $args['groupcids'] the number of categories you want items grouped by
 * @return array|void Returns array of number of items per category, or caterogies per item
 */
function categories_userapi_groupcount($args)
{
    // Get arguments from argument array
    extract($args);

    // Optional arguments
    if (!isset($groupby)) {
        $groupby = 'category';
    }

    // Security check
    if(!xarSecurity::check('ViewCategoryLink')) return;

    // Get database setup
    $dbconn = xarDB::getConn();

    // Get the field names and LEFT JOIN ... ON ... parts from categories
    // By passing on the $args, we can let leftjoin() create the WHERE for
    // the categories-specific columns too now
    $categoriesdef = xarMod::apiFunc('categories','user','leftjoin',$args);

    // Collection of where-clause expressions.
    $where = array();

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
    if (!$result) return;

    $count = array();
    while (!$result->EOF) {
        $fields = $result->fields;
        $num = array_pop($fields);
// TODO: use multi-level array for multi-category grouping ?
        $id = join('+',$fields);
        $count[$id] = (int)$num;
        $result->MoveNext();
    }

    $result->Close();

    return $count;
}
