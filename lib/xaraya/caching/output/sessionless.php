<?php
/**
 * Session-less page caching for first-time visitors
 * 
 * @package core
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage caching
 * @author mikespub
 * @author jsb
**/

class xarSessionLessCache extends Object
{
    /**
     * Check if this page is suitable for session-less page caching
     *
     * @access public
     * @returns bool
     * @return true if the page is suitable for session-less caching, false if not
     */
    public static function checkCachingRules()
    {
        if (
        // we have no session id in a cookie or URL parameter
            empty($_REQUEST[xarOutputCache::$cacheCookie]) &&
        // we're dealing with a GET OR a HEAD request
            !empty($_SERVER['REQUEST_METHOD']) &&
            ($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'HEAD') &&
        // TODO: make compatible with IIS and https (cfr. xarServer.php)
            !empty($_SERVER['HTTP_HOST']) &&
            !empty($_SERVER['REQUEST_URI'])
           ) {
           // the URL is one of the candidates for session-less caching
           return true;
        } else {
           return false;
        }
    }

    /**
     * Check session-less page caching
     *
     * @returns none
     * @return exit if session-less page caching finds a hit
     */
    public static function isCached($sessionLessList = null, $autoCachePeriod = 0)
    {
        // Check if this page is suitable for session-less page caching
        if (!(self::checkCachingRules())) {
            return;
        }

        if (empty($sessionLessList) || !is_array($sessionLessList)) {
            $sessionLessList = array();
        }

        // the URL is already in the list for session-less page caching
        if (in_array('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], $sessionLessList)) {
            $cacheKey = 'static';
            $cacheCode = md5($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
            $cache_file = xarOutputCache::$cacheDir . "/page/$cacheKey-" . $cacheCode . ".php";
        // Note: we stick to filesystem for session-less caching
            if (file_exists($cache_file) &&
                filesize($cache_file) > 0 &&
                (xarPageCache::$cacheTime == 0 ||
                 filemtime($cache_file) > time() - xarPageCache::$cacheTime)) {

                // CHECKME: set xarPageCache::$cacheCode for the ETag here or not ???
                xarPageCache::$cacheCode = $cacheCode;

                $modtime = filemtime($cache_file);
                xarPageCache::sendHeaders($modtime);
                // this may already exit if we have a 304 Not Modified

                // send the content of the cache file to the browser
                self::getCached($cache_file);

                // CHECKME: if we do this after xarPageCache::sendHeaders(), we'll never get the 304's logged for autocache
                if (file_exists(xarOutputCache::$cacheDir.'/autocache.start')) {
                    sys::import('xaraya.caching.output.autosession');
                    xarAutoSessionCache::logStatus('HIT', $autoCachePeriod);
                }

                // we're done here !
                exit;

            } else {
                // tell xarPageCache::setCached() that we want to save another copy here
                self::setCached();
                // we'll continue with the core loading etc. here
            }
        }
        // we haven't found a cache hit for this URL
        if (file_exists(xarOutputCache::$cacheDir.'/autocache.start')) {
            sys::import('xaraya.caching.output.autosession');
            xarAutoSessionCache::logStatus('MISS', $autoCachePeriod);
        }
    }

    public static function getCached($cache_file)
    {
        // send the content of the cache file to the browser
        @readfile($cache_file);
    // FIXME: separate cache cleaning for session-less caching if necessary
        //xarCache_CleanCached('Page');
    }

    public static function setCached()
    {
        // tell xarPageCache::setCached() that we want to save another copy here
        xarPageCache::$cacheNoSession = 1;
    }
}

?>
