<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 */

/**
 * Get orphan links
 * @param $args['modid'] module�s ID
 * @param $args['itemtype'] item type (if any)
 * @param $args['numitems'] optional number of items to return
 * @param $args['startnum'] optional start at this number (1-based)
 * @return array Returns an array of orphan links, or false on failure
 */
function categories_userapi_getorphanlinks($args)
{
    // Get arguments from argument array
    extract($args);

    if (empty($modid)) {
        return false;
    }
    if (!isset($itemtype)) {
        $itemtype = 0;
    }

    sys::import('xaraya.structures.query');
    $tables =& xarDB::getTables();
    $q = new Query();
    $q->addtable($tables['categories'], 'c');
    $q->addtable($tables['categories_linkage'], 'cl');
    $q->leftjoin('cl.category_id', 'c.id');
    $q->addfield('cl.category_id');
    $q->eq('c.id', NULL);
    $q->addgroup('cl.category_id');
    $q->run();
    $q->qecho();
    sys::import('modules.categories.class.worker');
    $worker = new CategoryWorker();
    $catbases = $worker->getcatbases(
                              array('module_id'    => $modid,
                                    'itemtype' => $itemtype));
    if (empty($catbases)) {
        $args['reverse'] = 1;
        // any link is an orphan here
        return xarMod::apiFunc('categories','user','getlinks', $args);
    }

    $seencid = array();
    foreach ($catbases as $catbase) {
        $seencid[$catbase['category_id']] = 1;
    }
    if (empty($seencid)) {
        $args['reverse'] = 1;
        // any link is an orphan here
        return xarMod::apiFunc('categories','user','getlinks', $args);
    }

    $catlist = xarMod::apiFunc('categories','user','getcatinfo',
                             array('cids' => array_keys($seencid)));
    uasort($catlist,'categories_userapi_getorphanlinks_sortbyleft');

    // Security check
    if(!xarSecurityCheck('ViewCategoryLink')) return;

    // Get database setup
    $dbconn = xarDB::getConn();

    // Table definition
    $xartable =& xarDB::getTables();
    $categoriestable = $xartable['categories'];
    $categorieslinkagetable = $xartable['categories_linkage'];

    $bindvars = array();
    $bindvars[] = (int) $modid;
    $bindvars[] = (int) $itemtype;

    // find out where the gaps between the base cats are
    $where = array();
    $right = 0;
    foreach ($catlist as $catinfo) {
        // skip empty gaps in the tree
        if ($catinfo['left'] == $right + 1) {
            $right = $catinfo['right'];
            continue;
        }
        $where[] = "($categoriestable.left_id > ? and $categoriestable.left_id < ?)";
        $bindvars[] = (int) $right;
        $bindvars[] = (int) $catinfo['left'];
        $right = $catinfo['right'];
    }
    $where[] = "($categoriestable.left_id > ?)";
    $bindvars[] = (int) $right;

    $sql = "SELECT $categorieslinkagetable.category_id, $categorieslinkagetable.item_id
              FROM $categorieslinkagetable
         LEFT JOIN $categoriestable
                ON $categoriestable.id = $categorieslinkagetable.category_id
             WHERE $categorieslinkagetable.module_id = ?
               AND $categorieslinkagetable.itemtype = ?
               AND (" . join(' OR ', $where) . ")";

    if (!empty($numitems)) {
        if (empty($startnum)) {
            $startnum = 1;
        }
        $result = $dbconn->SelectLimit($sql, $numitems, $startnum - 1, $bindvars);
    } else {
        $result = $dbconn->Execute($sql, $bindvars);
    }
    if (!$result) return;

    // Makes the linkages array to be returned
    $answer = array();

    for(; !$result->EOF; $result->MoveNext())
    {
        $fields = $result->fields;
        $iid = array_pop($fields);
        $answer[$iid][] = $fields[0];
    }

    $result->Close();

    // Return Array with linkage
    return $answer;
}

function categories_userapi_getorphanlinks_sortbyleft($a, $b)
{
    if ($a['left'] == $b['left']) return 0;
    return ($a['left'] > $b['left'] ? 1 : -1);
}

?>
