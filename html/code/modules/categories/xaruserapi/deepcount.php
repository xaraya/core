<?php
/**
 * Categories Module
 *
 * @package modules
 * @subpackage categories module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 */

/**
 * count number of items per category, or number of categories for each item
 * @param $args['groupby'] group entries by 'category' or by 'item'
 * @param $args['modid'] module´s ID
 * @param $args['itemtype'] item type
 * @param $args['cids'] optional array of cids we're counting for (OR/AND)
 * @param $args['andcids'] true means AND-ing categories listed in cids
 * @param $args['groupcids'] the number of categories you want items grouped by
 * @returns array
 * @return number of items per category, or caterogies per item
 */
function categories_userapi_deepcount($args)
{
    extract($args);

    $count = array();

    // Get the non-zero counts.
    // These are the leaf nodes that we then extend back to the top ancestor(s).
    $catcount = xarMod::apiFunc(
        'categories', 'user', 'groupcount', $args
    );

    // Throw back errors as an empty list.
    if (empty($catcount)) {return $count;}

    $allcounts = $catcount;

    // Array of category IDs.
    $catlist = array_keys($catcount);

    // Get the ancestors (including self).
    $ancestors = xarMod::apiFunc('categories', 'user', 'getancestors', array('cids'=>$catlist, 'self'=>true));

    // For each non-zero category count, traverse the ancestors and add on the counts.
    $allcounts[0] = 0;
    foreach ($catcount as $cat => $count) {
        // Keep track of categories visited to avoid infinite loops.
        $done = array();
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

?>
