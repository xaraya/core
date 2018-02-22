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
 * Utility function to pass individual item links to whoever
 *
 * @param $args['itemtype'] item type (optional)
 * @param $args['itemids'] array of item ids to get
 * @return array Returns array containing the itemlink(s) for the item(s).
 */
function categories_userapi_getitemlinks($args)
{
    $itemlinks = array();
    $catlist = xarMod::apiFunc('categories','user','getcatinfo',
                             array('cids' => $args['itemids']));
    if (!isset($catlist) || !is_array($catlist) || count($catlist) == 0) {
       return $itemlinks;
    }

    foreach ($args['itemids'] as $itemid) {
        if (!isset($catlist[$itemid])) continue;
        $itemlinks[$itemid] = array('url'   => xarModURL('categories', 'user', 'main',
                                                         array('catid' => $itemid)),
                                            'title' => xarVarPrepForDisplay($catlist[$itemid]['name']),
                                            'label' => xarVarPrepForDisplay($catlist[$itemid]['description']));
    }
    return $itemlinks;
}

?>
