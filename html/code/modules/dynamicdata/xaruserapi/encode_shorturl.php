<?php
/**
 * Encode short urls
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * return the path for a short URL to xarModURL for this module
 * @param array    $args array of optional parameters<br/>
 *        string   $args the function and arguments passed to xarModURL
 * @return string path to be added to index.php for a short URL, or empty if failed
 */
function dynamicdata_userapi_encode_shorturl(Array $args=array())
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
    if (empty($module_id)) {
        $module_id = xarMod::getRegID('dynamicdata');
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    if (count($objectcache) == 0) {
        $objects = DataObjectMaster::getObjects();
        foreach ($objects as $object) {
            $objectcache[$object['moduleid'].':'.$object['itemtype']] = $object['name'];
        }
    }

    // specify some short URLs relevant to your module
    if (!empty($table)) {
        // no short URLs for this one...

    } elseif ($func == 'view') {
        if (!empty($objectcache[$module_id.':'.$itemtype])) {
            $name = $objectcache[$module_id.':'.$itemtype];
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
        if (!empty($objectcache[$module_id.':'.$itemtype])) {
            $name = $objectcache[$module_id.':'.$itemtype];
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
