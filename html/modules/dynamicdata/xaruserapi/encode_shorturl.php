<?php
/**
 * Encode short urls
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * return the path for a short URL to xarModURL for this module
 * @param $args the function and arguments passed to xarModURL
 * @returns string
 * @return path to be added to index.php for a short URL, or empty if failed
 */
function dynamicdata_userapi_encode_shorturl($args)
{
    static $objectcache = array();

    // Get arguments from argument array
    extract($args);

    // check if we have something to work with
    if (!isset($func)) {
        return;
    }

    // make sure you don't pass the following variables as arguments too

    // default path is empty -> no short URL
    $path = '';
    // if we want to add some common arguments as URL parameters below
    $join = '?';
    // we can't rely on xarModGetName() here !
    $module = 'dynamicdata';

    // return immediately when we're dealing with the main function (don't load unnecessary stuff)
    if ($func == 'main') {
        return '/' . $module . '/';
    }

    // fill in default values
    if (empty($modid)) {
        $modid = xarModGetIDFromName('dynamicdata');
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    if (count($objectcache) == 0) {
        $objects = xarModAPIFunc('dynamicdata','user','getobjects');
        foreach ($objects as $object) {
            $objectcache[$object['moduleid'].':'.$object['itemtype']] = $object['name'];
        }
    }

    // specify some short URLs relevant to your module
    if (!empty($table)) {
        // no short URLs for this one...

    } elseif ($func == 'view') {
        if (!empty($objectcache[$modid.':'.$itemtype])) {
            $name = $objectcache[$modid.':'.$itemtype];
            $alias = xarModGetAlias($name);
            if ($module == $alias) {
                // OK, we can use a 'fake' module name here
                $path = '/' . $name . '/';
            } else {
                $path = '/' . $module . '/' . $name . '/';
            }
            if (!empty($catid)) {
                $path .= 'c' . $catid . '/';
            }
        } else {
            // we don't know this one...
        }
    } elseif ($func == 'display' && isset($itemid)) {
        if (!empty($objectcache[$modid.':'.$itemtype])) {
            $name = $objectcache[$modid.':'.$itemtype];
            $alias = xarModGetAlias($name);
            if ($module == $alias) {
                // OK, we can use a 'fake' module name here
                $path = '/' . $name . '/' . $itemid;
            } else {
                $path = '/' . $module . '/' . $name . '/' . $itemid;
            }
        } else {
            // we don't know this one...
        }
    }
    // anything else does not have a short URL equivalent

// TODO: add *any* extra args we didn't use yet here
    // add some other module arguments as standard URL parameters
    if (!empty($path)) {
        // search
        if (isset($q)) {
            $path .= $join . 'q=' . urlencode($q);
            $join = '&';
        }
        // sort
        if (isset($sort)) {
            $path .= $join . 'sort=' . $sort;
            $join = '&';
        }
        // pager
        if (isset($startnum) && $startnum != 1) {
            $path .= $join . 'startnum=' . $startnum;
            $join = '&';
        }
        // multi-page articles
        if (isset($page)) {
            $path .= $join . 'page=' . $page;
            $join = '&';
        }
    }

    return $path;
}

?>
