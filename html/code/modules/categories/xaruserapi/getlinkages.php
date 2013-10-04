<?php
/**
 * Categories Module
 *
 * @package modules
 * @subpackage categories module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 */

/**
 * get links
 * @param $args['cids'] array of ids of categories to get linkage for (OR/AND)
 * @param $args['iids'] array of ids of itens to get linkage for
 * @param $args['module'] module (if any)
 * @param $args['itemtype'] item type (if any)
 * @param $args['numitems'] optional number of items to return
 * @param $args['startnum'] optional start at this number (1-based)
 * @param $args['sort'] optional sort by itemid (default) or numlinks
 * @param $args['andcids'] true means AND-ing categories listed in cids
 * @param $args['groupcids'] the number of categories you want items grouped by
 * @returns array
 * @return arrayof linkages with keys either item_id or category_id
 */
function categories_userapi_getlinkages($args)
{
    if(!xarSecurityCheck('ViewCategoryLink')) return;

    // Get arguments from argument array
    extract($args);

    $xartable =& xarDB::getTables();
    sys::import('xaraya.structures.query');
    $q = new Query('SELECT', $xartable['categories_linkage']);
    if (!empty($module)) $q->eq('module_id',xarMod::getID($module));
    if (!empty($itemtype)) $q->eq('itemtype',$itemtype);

    if (!empty($items)) {
        if (is_array($items)) $q->in('item_id',$items);
        else $q->eq('item_id',$items);
    } elseif (!empty($categories)) {
        if (is_array($categories)) $q->in('item_id',$categories);
        else $q->eq('category_id',$categories);
    }

//    $q->qecho();
    if (!$q->run()) return;

    $result = array();
    foreach ($q->output() as $row) {
        if (!empty($items)) $result[$row['item_id']][] = $row;
        elseif (!empty($categories)) $result[$row['category_id']][] = $row;
    }
    return $result;
}

?>