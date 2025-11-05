<?php

/**
 * Session-less page caching for first-time visitors
 *
 * @package core\services
 * @subpackage caching
 * @category Xaraya Web Applications Framework
 * @version 2.8.6
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services\Caching;

use Xaraya\Services\CachingService;
use Xaraya\Services\ServiceClass;
use xarCore;

/**
 * Session-less page caching for first-time visitors
 */
class SessionLessCache extends ServiceClass
{
    public const SLICE = 'caching.sessionless';

    protected CachingService $cache;

    /**
     * Create service class for parent
     */
    public function __construct(mixed $parent)
    {
        $this->parent = $parent;
        $this->cache = $parent->cache();
    }

    public function checkCachingRules()
    {
        $cacheCookie = $this->cache->outputCache->cacheCookie;
        // Note: still using $_SERVER here since xarServer is not initialized
        if (
            // we have no session id in a cookie or URL parameter
            empty($_REQUEST[$cacheCookie])
        // we're dealing with a GET OR a HEAD request
            && !empty($_SERVER['REQUEST_METHOD'])
            && ($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'HEAD')
        // TODO: make compatible with IIS and https (cfr. xarServer.php)
            && !empty($_SERVER['HTTP_HOST'])
            && !empty($_SERVER['REQUEST_URI'])
        ) {
            // the URL is one of the candidates for session-less caching
            return true;
        } else {
            return false;
        }
    }

    public function isCached($sessionLessList = null, $autoCachePeriod = 0)
    {
        // Check if this page is suitable for session-less page caching
        if (!($this->checkCachingRules())) {
            return;
        }

        // Note: still using $_SERVER here since xarServer is not initialized
        if (empty($sessionLessList) || !is_array($sessionLessList)) {
            $sessionLessList = [];
        }
        $cacheDir = $this->cache->getOutputCacheDir();
        $pageCache = $this->cache->pageCache;
        $cacheTime = $pageCache->cacheTime;

        // the URL is already in the list for session-less page caching
        if (in_array('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], $sessionLessList)) {
            $cacheKey = 'static';
            $cacheCode = md5($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            $cache_file = $cacheDir . "/page/$cacheKey-" . $cacheCode . ".php";
            // Note: we stick to filesystem for session-less caching
            if (file_exists($cache_file)
                && filesize($cache_file) > 0
                && ($cacheTime == 0
                 || filemtime($cache_file) > time() - $cacheTime)) {
                // CHECKME: set PageCache cacheCode for the ETag here or not ???
                $pageCache->cacheCode = $cacheCode;

                $modtime = filemtime($cache_file);
                $pageCache->sendHeaders($modtime);
                // this may already exit if we have a 304 Not Modified

                // send the content of the cache file to the browser
                $this->getCached($cache_file);

                // CHECKME: if we do this after PageCache::sendHeaders(), we'll never get the 304's logged for autocache
                if (file_exists($cacheDir . '/autocache.start')) {
                    AutoSessionCache::logStatus('HIT', $autoCachePeriod, $cacheDir);
                }

                // we're done here !
                xarCore::exit();
                return;
            } else {
                // tell PageCache::setCached() that we want to save another copy here
                $this->setCached();
                // we'll continue with the core loading etc. here
            }
        }
        // we haven't found a cache hit for this URL
        if (file_exists($cacheDir . '/autocache.start')) {
            AutoSessionCache::logStatus('MISS', $autoCachePeriod, $cacheDir);
        }
    }

    public function getCached($cache_file)
    {
        // send the content of the cache file to the browser
        @readfile($cache_file);
        // FIXME: separate cache cleaning for session-less caching if necessary
        //$this->cache->pageCache->cacheStorage->cleanCached();
    }

    public function setCached()
    {
        // tell PageCache::setCached() that we want to save another copy here
        $this->cache->pageCache->cacheNoSession = 1;
    }
}
