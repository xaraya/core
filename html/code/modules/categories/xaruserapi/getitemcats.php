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
 * Get an array of assigned category details for a specific item, limiting by a base cid if required.
 * 
 * Get categories for an item, optionally limiting to just one category branch (to be expanded to allow base categories by name).
 * 
 * @param $args['basecid'] optional base cid under which the returned categories must lie
 * @param $args['basecids'] optional array of base cids under which the returned categories must lie
 * @param $args['module'] name of the module; or
 * @param $args['modid'] module ID
 * @param $args['itemtype'] item type
 * @param $args['itemid'] item ID
 * @return array|boolean Returns category info on success, false on failure.
 */
function categories_userapi_getitemcats($args)
{
    /**
     * Pending
     * TODO: allow ordering of the results by name, description etc.
     */
    
    // Get arguments from argument array
    extract($args);

    // Requires: module, itemtype, itemid (but not validated)

    // Default the module name.
    if (empty($modid) && empty($module)) {
        $module = xarModGetName();
    }

    // Get module ID if only a name provided.
    if (empty($modid) && !empty($module)) {
        $args['modid'] = xarMod::getRegId($module);
    }

    // Get the list of assigned categories for this module item.
    $args['groupby'] = 'category';
    $catlist = xarMod::apiFunc(
        'categories', 'user', 'groupcount', $args
    );

    // Throw back errors as an empty list.
    if (empty($catlist)) {
        return array();
    }

    // Flip the array, so the cat IDs are the values.
    $catlist = array_keys($catlist);

    if (!isset($basecids) || !is_array($basecids)) {
        $basecids = array();
    }

    if (isset($basecid)) {
        array_push($basecids, $basecid);
    }

    // Initialise the result array.
    $result = array();

    // Check whether we want to restrict the catergories by one or more base categories.
    // TODO: when categories supports 'base' categories (category itemtypes?) then add
    // another (much simpler) section here.
    if (!empty($basecids)) {
        // Get the ancestors (including self) of these categories.
        // Included, is a list of descendants for each category.
        $ancestors = xarMod::apiFunc(
            'categories', 'user', 'getancestors',
            array('cids'=>$catlist, 'self'=>true, 'descendants'=>'list')
        );

        $resultcids = array();

        foreach($basecids as $basecid) {
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
        $result = xarMod::apiFunc('categories', 'user', 'getcatinfo', array('cids'=>$catlist));
    }

    return $result;
}

?>
