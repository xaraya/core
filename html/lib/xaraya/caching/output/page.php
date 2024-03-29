<?php
/**
 * Page caching
 *
 * @package core\caching
 * @subpackage caching
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub
 * @author jsb
**/

class xarPageCache extends xarObject
{
    public static int $cacheTime         = 1800;
    public static int $cacheDisplay      = 0;
    public static int $cacheShowTime     = 1;
    public static int $cacheExpireHeader = 0;
    public static string $cacheGroups       = '';
    public static int $cacheHookedOnly   = 0;
    public static int $cacheSizeLimit    = 2097152;
    public static ?ixarCache_Storage $cacheStorage = null;

    /** @var ?array<mixed> */
    public static $cacheSettings      = null;
    public static ?string $cacheKey   = null;
    public static ?string $cacheCode  = null;
    public static int $cacheNoSession = 0;

    /**
     * Initialise the page caching options
     *
     * @param array<string, mixed> $args cache configuration
     * @return mixed true on success, exit if session-less page caching finds a hit
     */
    public static function init(array $args = [])
    {
        self::$cacheTime = $args['Page.TimeExpiration'] ?? 1800;
        self::$cacheDisplay = $args['Page.DisplayView'] ?? 0;
        self::$cacheShowTime = $args['Page.ShowTime'] ?? 1;
        self::$cacheExpireHeader = $args['Page.ExpireHeader'] ?? 0;
        self::$cacheGroups = $args['Page.CacheGroups'] ?? '';
        self::$cacheHookedOnly = $args['Page.HookedOnly'] ?? 0;
        self::$cacheSizeLimit = $args['Page.SizeLimit'] ?? 2097152;

        // Check if we need to try session-less page caching here
        $sessionLessList = $args['Page.SessionLess'] ?? null;
        $autoCachePeriod = $args['AutoCache.Period'] ?? 0;

        if (!empty($sessionLessList) || !empty($autoCachePeriod)) {
            sys::import('xaraya.caching.output.sessionless');
            xarSessionLessCache::isCached($sessionLessList, $autoCachePeriod);
            // Note : we may already exit here if session-less page caching is enabled
        }

        $storage = !empty($args['Page.CacheStorage']) ?
            $args['Page.CacheStorage'] : 'filesystem';
        $provider = !empty($args['Page.CacheProvider']) ?
            $args['Page.CacheProvider'] : null;
        $logfile = !empty($args['Page.LogFile']) ?
            $args['Page.LogFile'] : null;
        // Note: make sure this isn't used before core loading if we use database storage
        self::$cacheStorage = xarCache::getStorage([
            'storage'   => $storage,
            'type'      => 'page',
            'provider'  => $provider,
            // we store output cache files under this
            'cachedir'  => xarOutputCache::getCacheDir(),
            'expire'    => self::$cacheTime,
            'sizelimit' => self::$cacheSizeLimit,
            'logfile'   => $logfile,
        ]);
        if (empty(self::$cacheStorage)) {
            return false;
        }

        return true;
    }

    /**
     * Get a cache key if this page is suitable for output caching
     *
     * @param string $url optional url to be checked if not the current url
     * @return mixed cacheKey to be used with (is|get|set)Cached, or null if not applicable
     */
    public static function getCacheKey($url = null)
    {
        if (empty(self::$cacheStorage)) {
            return;
        }

        // check if this page is suitable for page caching
        if (!(self::checkCachingRules($url))) {
            return;
        }

        // we should be safe for caching now

        // set the current cacheKey - already done in checkCachingRules() here
        //self::$cacheKey = $cacheKey;

        // set the cacheCode for the current cacheKey

        // the output depends on the current host, theme and locale
        $factors = xarServer::getVar('HTTP_HOST') . xarTpl::getThemeDir() .
                xarUser::getNavigationLocale();

        // add user groups as a factor if necessary
        // Note : we don't share the cache between groups or with anonymous here
        if (!empty(self::$cacheGroups) && xarUser::isLoggedIn()) {
            $gidlist = xarCache::getParents();
            $factors .= join(';', $gidlist);
        }

        // add page identifier
        $factors .= xarServer::getVar('REQUEST_URI');
        $param = xarServer::getVar('QUERY_STRING');
        if (!empty($param)) {
            $factors .= '?' . $param;
        }

        self::$cacheCode = md5($factors);
        self::$cacheStorage->setCode(self::$cacheCode);

        // return the cacheKey
        return self::$cacheKey;
    }

    /**
     * Get cache settings for the pages
     * @return array<mixed>
     */
    public static function getCacheSettings()
    {
        if (!isset(self::$cacheSettings)) {
            $settings = [];
            // TODO: make more things configurable ?
            self::$cacheSettings = $settings;
        }
        return self::$cacheSettings;
    }

    /**
     * Check if this page is suitable for page caching
     *
     *
     * @param  string $url optional url to be checked if not the current url
     * @return boolean   true if the page is suitable for caching, false if not
     */
    public static function checkCachingRules($url = null)
    {
        if (empty($url)) {
            // get module parameters
            [$modName, $modType, $funcName] = xarController::getRequest()->getInfo();
            // define the cacheKey
            $cacheKey = "$modName-$modType-$funcName";
            // get the current themeDir
            $themeDir = xarTpl::getThemeDir();
        } else {
            $params = parse_url($url);
            // TODO: how far do we want to go here ?
            $cacheKey = '?';
            $themeDir = '?';
            return false;
        }

        $settings = self::getCacheSettings();

        if (// if this page is a user type page OR an object url AND
            (strpos($cacheKey, '-user-') || strpos($cacheKey, 'objecturl-') !== false) &&
            // (display views can be cached OR it is not a display view) AND
            ((self::$cacheDisplay == 1) || (!strpos($cacheKey, '-display'))) &&
            // the http request is a GET OR a HEAD AND
            (xarServer::getVar('REQUEST_METHOD') == 'GET' || xarServer::getVar('REQUEST_METHOD') == 'HEAD') &&
            // (we're caching the output of all themes OR this is the theme we're caching) AND
            (empty(xarOutputCache::$cacheTheme) ||
             strpos($themeDir, xarOutputCache::$cacheTheme)) &&
            // the current user is eligible for receiving cached pages AND
            xarPage_checkUserCaching(self::$cacheGroups)) {
            // set the current cacheKey
            self::$cacheKey = $cacheKey;

            return true;
        } else {
            return false;
        }
    }

    /**
     * Send HTTP headers for page caching (or return 304 Not Modified)
     *
     * @param int $modtime
     * @return void
     */
    public static function sendHeaders($modtime = 0)
    {
        // Note: still using $_SERVER here since xarServer is not initialized
        if (empty($modtime)) {
            // CHECKME: this means 304 will never apply then - is that what we want here ?
            // default to current time
            $modtime = time();
            if (!empty(self::$cacheTime)) {
                // rounded down to the nearest multiple of self::$cacheTime
                $modtime -= ($modtime % self::$cacheTime);
            }
        }
        // doesn't seem to be taken into account ?
        $etag = self::$cacheCode.$modtime;
        $match = $_SERVER['HTTP_IF_NONE_MATCH'] ?? null;
        if (!empty($match) && $match == $etag) {
            // jsb:  for some reason, Mozilla based browsers
            // do not re-send an ETag after getting a 304
            // so this only works once per cached page
            header('HTTP/1.1 304 Not Modified');
            header("Cache-Control: public, must-revalidate");
            exit;
        } else {
            $since = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? null;
            if (!empty($since) && strtotime($since) >= $modtime) {
                header('HTTP/1.1 304 Not Modified');
                header("Cache-Control: public, must-revalidate");
                exit;
                // jsb: according to RFC 2616, if $match isn't empty but is
                // not equal to the ETag we should send a 412 response
                // But browser behavior seems inconsistant with the doc and
                // often results in more data being sent than is necessary.
            }
        }
        if (!empty(self::$cacheExpireHeader)) {
            // this tells clients and proxies that this file is good until local
            // cache file is due to expire, and can be reused w/out revalidating
            header("Expires: " .
                   gmdate("D, d M Y H:i:s", $modtime + self::$cacheTime) .
                   " GMT");
            header("Cache-Control: public, max-age=" . self::$cacheTime);
        } else {
            header("Expires: 0");
            header("Cache-Control: public, must-revalidate");
        }
        header("ETag: $etag");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s", $modtime) . " GMT");
        // we can't use this after session_start()
        //session_cache_limiter('public');
        // PHP doesn't set the Pragma header when sending back a cookie
        if (isset($_COOKIE[xarOutputCache::$cacheCookie])) {
            header("Pragma: public");
        } else {
            header("Pragma:");
        }
        // Specify the charset
        $defaultLocale = !empty(xarOutputCache::$cacheLocale) ? xarOutputCache::$cacheLocale : 'en_US.utf-8';
        [$lang_country, $charset] = explode('.', $defaultLocale);
        if (empty($charset)) {
            $charset = 'utf-8';
        }
        // CHECKME: what about other content types ?
        header("Content-type: text/html; charset=" . $charset);
    }

    /**
     * check if the content of a page is available in cache or not
     *
     *
     * @param  string $cacheKey the key identifying the particular page you want to access
     * @return boolean   true if the page is available in cache, false if not
     */
    public static function isCached($cacheKey)
    {
        if (empty(self::$cacheStorage)) {
            return false;
        }
        // we only cache the top-most page in case of nested pages
        if (empty($cacheKey) || $cacheKey != self::$cacheKey) {
            return false;
        }

        // Note: still using $_SERVER here since xarServer is not initialized
        if (// the cache entry exists and hasn't expired yet...
            (self::$cacheStorage->isCached($cacheKey))) {
            // create another copy for session-less page caching if necessary
            if (!empty(self::$cacheNoSession)) {
                $cacheKey2 = 'static';
                $cacheCode2 = md5($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
                $cache_file2 = xarOutputCache::getCacheDir()."/page/$cacheKey2-$cacheCode2.php";
                // Note that if we get here, the first-time visitor will receive a session cookie,
                // so he will no longer benefit from this himself ;-)
                self::$cacheStorage->saveFile($cacheKey, $cache_file2);
            }

            $modtime = self::$cacheStorage->getLastModTime();
            // this may already exit if we have a 304 Not Modified
            self::sendHeaders($modtime);

            return true;
        } else {
            return false;
        }
    }

    /**
     * get the content of a cached page
     *
     *
     * @param  string $cacheKey the key identifying the particular page you want to access
     * @return boolean|void   true if succeeded, false otherwise
     */
    public static function getCached($cacheKey)
    {
        if (empty(self::$cacheStorage)) {
            return false;
        }
        // we only cache the top-most page in case of nested pages
        if (empty($cacheKey) || $cacheKey != self::$cacheKey) {
            return;
        }

        // output the content directly to the browser here
        $result = self::$cacheStorage->getCached($cacheKey, 1);

        return $result;
    }

    /**
     * set the content of a cached page
     *
     *
     * @param  string $cacheKey the key identifying the particular page you want to access
     * @param  string $value    the new content for that page
     * @return void
     */
    public static function setCached($cacheKey, $value)
    {
        if (empty(self::$cacheStorage)) {
            return;
        }
        // we only cache the top-most page in case of nested pages
        if (empty($cacheKey) || $cacheKey != self::$cacheKey) {
            return;
        }

        // Check if isCached() or xarSecurity or ... has told not to cache this page
        if (xarCoreCache::isCached('Page.Caching', 'nocache')) {
            // reset for next page request when using second-level cache storage
            xarCoreCache::delCached('Page.Caching', 'nocache');
            return;
        }

        // We delay checking this extra caching rule until now
        if (self::$cacheHookedOnly) {
            $modName = substr($cacheKey, 0, strpos($cacheKey, '-'));
            if (!xarModHooks::isHooked('xarcachemanager', $modName)) {
                return;
            }
        }

        // Note: still using $_SERVER here since xarServer is not initialized
        if (// the cache entry doesn't exist or has expired (no log here) AND
            // CHECKME: do we really want to check this again, or do we ignore it ?
            !(self::$cacheStorage->isCached($cacheKey, 0, 0)) &&
            // the cache collection directory hasn't reached its size limit...
            !(self::$cacheStorage->sizeLimitReached())) {
            // if request, modify the end of the file with a time stamp
            if (self::$cacheShowTime == 1) {
                $now = xarML(
                    'Last updated on #(1)',
                    date(DATE_RFC7231)
                );
                $value = preg_replace(
                    '#</body>#',
                    // TODO: set this up to be templated
                    '<div class="xar-sub" style="text-align: center; padding: 8px; ">'.$now.'</div></body>',
                    $value
                );
            }

            self::$cacheStorage->setCached($cacheKey, $value);

            // create another copy for session-less page caching if necessary
            if (!empty(self::$cacheNoSession)) {
                $cacheKey2 = 'static';
                $cacheCode2 = md5($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
                $cache_file2 = xarOutputCache::getCacheDir()."/page/$cacheKey2-$cacheCode2.php";
                // Note that if we get here, the first-time visitor will receive a session cookie,
                // so he will no longer benefit from this himself ;-)
                self::$cacheStorage->saveFile($cacheKey, $cache_file2);
            }

            $modtime = time();
            self::sendHeaders($modtime);
        }
    }

    /**
     * Flush page cache entries
     * @param string $cacheKey
     * @return void
     */
    public static function flushCached($cacheKey)
    {
        if (empty(self::$cacheStorage)) {
            return;
        }

        self::$cacheStorage->flushCached($cacheKey);
    }
}

/**
 * Check if the user can benefit from page caching
 *
 * @package core\caching
 * @subpackage caching
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @param string $cacheGroups
 * @return boolean
 * @todo Note : don't do this if admins get cached too :)
**/
function xarPage_checkUserCaching($cacheGroups)
{
    if (!xarUser::isLoggedIn()) {
        // always allow caching for anonymous users
        return true;
    } elseif (empty($cacheGroups)) {
        // if no other cache groups are defined
        return false;
    }

    $gidlist = xarCache::getParents();

    $groups = explode(';', $cacheGroups);
    foreach ($groups as $groupid) {
        if (in_array($groupid, $gidlist)) {
            return true;
        }
    }
    return false;
}
