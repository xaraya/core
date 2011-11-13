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
 * return the path for a short URL to xarModURL for this module
 *
 * @author the Example module development team
 * @param $args the function and arguments passed to xarModURL
 * @returns string
 * @return path to be added to index.php for a short URL, or empty if failed
 */
function categories_userapi_encode_shorturl($args)
{
    // Get arguments from argument array
    extract($args);

    // Check if we have something to work with
    if (!isset($func)) {
        return;
    }

    // Note : make sure you don't pass the following variables as arguments in
    // your module too - adapt here if necessary

    // default path is empty -> no short URL
    $path = '';
    // if we want to add some common arguments as URL parameters below
    $join = '?';
    // we can't rely on xarModGetName() here -> you must specify the modname !
    $module = 'categories';

    // specify some short URLs relevant to your module
    if ($func == 'main') {
        // check for required parameters
        if (isset($catid) && is_numeric($catid) && $catid > 0) {
/* needs the full path to the Top here */
            $name = xarMod::apiFunc('categories','user','cid2name',
                                 array('cid' => $catid,
                                       // for DMOZ-like URLs with the description field containing
                                       // the full path, use 1
                                       'usedescr' => 0));
            if (!empty($name)) {
                $path = '/' . $module . '/' . $name;
            } else {
                $path = '/' . $module . '/' . $catid;
            }
        } else {
            $path = '/' . $module . '/';
        }

        // you might have some additional parameter that you want to use to
        // create different virtual paths here - for example a category name
        // if (!empty($catid) && is_numeric($catid)) {
        //     // use a cache to avoid re-querying for each URL in the same cat
        //     static $catcache = array();
        //     if (xarModAPILoad('categories','user')) {
        //         if (isset($catcache[$catid])) {
        //             $cat = $catcache[$catid];
        //         } else {
        //             $cat = xarMod::apiFunc('categories','user','getcatinfo',
        //                                 array('cid' => $catid));
        //             // put the category in cache
        //             $catcache[$catid] = $cat;
        //         }
        //         if (!empty($cat) && !empty($cat['name'])) {
        //             // use the category name as part of the path here
        //             $path = '/' . $module . '/' . rawurlencode($cat['name']);
        //         }
        //     }
        // }

    } else {
        // anything else that you haven't defined a short URL equivalent for
        // -> don't create a path here
    }

    // add some other module arguments as standard URL parameters
    if (!empty($path) && isset($startnum)) {
        $path .= $join . 'startnum=' . $startnum;
    }

    return $path;
}

?>
