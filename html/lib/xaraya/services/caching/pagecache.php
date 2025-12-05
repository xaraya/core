<?php

/**
 * Page output caching
 *
 * @package core\services
 * @subpackage caching
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
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
use ixarCache_Storage;

/**
 * Page output caching
 */
class PageCache extends ServiceClass
{
    public const SLICE = 'caching.page';

    public int $cacheTime         = 1800;
    public int $cacheDisplay      = 0;
    public int $cacheShowTime     = 1;
    public int $cacheExpireHeader = 0;
    public string $cacheGroups    = '';
    public int $cacheHookedOnly   = 0;
    public int $cacheSizeLimit    = 2097152;
    public ?ixarCache_Storage $cacheStorage = null;

    /** @var ?array<mixed> */
    public $cacheSettings      = null;
    public ?string $cacheKey   = null;
    public ?string $cacheCode  = null;
    public int $cacheNoSession = 0;

    protected CachingService $cache;

    /**
     * Create service class for parent
     */
    public function __construct(mixed $parent)
    {
        $this->parent = $parent;
        $this->cache = $parent->cache();
    }

    /**
     * Initialize service class
     * @param array<string, mixed> $config
     */
    public function init(array $config = []): bool
    {
        $this->cacheTime = $config['Page.TimeExpiration'] ?? 1800;
        $this->cacheDisplay = $config['Page.DisplayView'] ?? 0;
        $this->cacheShowTime = $config['Page.ShowTime'] ?? 1;
        $this->cacheExpireHeader = $config['Page.ExpireHeader'] ?? 0;
        $this->cacheGroups = $config['Page.CacheGroups'] ?? '';
        $this->cacheHookedOnly = $config['Page.HookedOnly'] ?? 0;
        $this->cacheSizeLimit = $config['Page.SizeLimit'] ?? 2097152;

        // Check if we need to try session-less page caching here
        $sessionLessList = $config['Page.SessionLess'] ?? null;
        $autoCachePeriod = $config['AutoCache.Period'] ?? 0;

        if (!empty($sessionLessList) || !empty($autoCachePeriod)) {
            $sessionLessCache = new SessionLessCache($this->getParent());
            $sessionLessCache->isCached($sessionLessList, $autoCachePeriod);
            // Note : we may already exit here if session-less page caching is enabled
        }

        $storage = !empty($config['Page.CacheStorage'])
            ? $config['Page.CacheStorage'] : 'filesystem';
        $provider = !empty($config['Page.CacheProvider'])
            ? $config['Page.CacheProvider'] : null;
        $logfile = !empty($config['Page.LogFile'])
            ? $config['Page.LogFile'] : null;
        // Note: make sure this isn't used before core loading if we use database storage
        $this->cacheStorage = $this->cache->getStorage([
            'storage'   => $storage,
            'type'      => 'page',
            'provider'  => $provider,
            // we store output cache files under this
            'cachedir'  => $this->cache->getOutputCacheDir(),
            'expire'    => $this->cacheTime,
            'sizelimit' => $this->cacheSizeLimit,
            'logfile'   => $logfile,
        ]);
        if (empty($this->cacheStorage)) {
            return false;
        }

        return true;
    }

    public function getCacheKey($url = null)
    {
        if (empty($this->cacheStorage)) {
            return null;
        }

        // check if this page is suitable for page caching
        if (!($this->checkCachingRules($url))) {
            return null;
        }

        // we should be safe for caching now
        $xar = $this->getServicesClass();

        // set the current cacheKey - already done in checkCachingRules() here
        //$this->cacheKey = $cacheKey;

        // set the cacheCode for the current cacheKey

        // the output depends on the current host, theme and locale
        $factors = $xar->req()->getHost() . $xar->tpl()->getThemeDir()
                . $xar->user()->getLocale();

        // add user groups as a factor if necessary
        // Note : we don't share the cache between groups or with anonymous here
        if (!empty($this->cacheGroups) && $xar->user()->isLoggedIn()) {
            $gidlist = $xar->cache()->getParents();
            $factors .= join(';', $gidlist);
        }

        // add page identifier (incl. path + query string)
        $factors .= $xar->req()->getRequestString();

        $this->cacheCode = md5($factors);
        $this->cacheStorage->setCode($this->cacheCode);

        // return the cacheKey
        return $this->cacheKey;
    }

    public function getCacheSettings()
    {
        if (!isset($this->cacheSettings)) {
            $settings = [];
            // TODO: make more things configurable ?
            $this->cacheSettings = $settings;
        }
        return $this->cacheSettings;
    }

    public function checkCachingRules($url = null)
    {
        $xar = $this->getServicesClass();
        if (empty($url)) {
            // get module parameters
            [$modName, $modType, $funcName] = $xar->req()->getRequest()->getInfo();
            // define the cacheKey
            $cacheKey = "$modName-$modType-$funcName";
            // get the current themeDir
            $themeDir = $xar->tpl()->getThemeDir();
        } else {
            $params = parse_url($url);
            // TODO: how far do we want to go here ?
            $cacheKey = '?';
            $themeDir = '?';
            return false;
        }

        //$settings = $this->getCacheSettings();
        $cacheTheme = $this->cache->outputCache->cacheTheme;

        if (// if this page is a user type page OR an object url AND
            (strpos($cacheKey, '-user-') || strpos($cacheKey, 'objecturl-') !== false)
            // (display views can be cached OR it is not a display view) AND
            && (($this->cacheDisplay == 1) || (!strpos($cacheKey, '-display')))
            // the http request is a GET OR a HEAD AND
            && ($xar->req()->getMethod() == 'GET' || $xar->req()->getMethod() == 'HEAD')
            // (we're caching the output of all themes OR this is the theme we're caching) AND
            && (empty($cacheTheme)
             || strpos($themeDir, $cacheTheme))
            // the current user is eligible for receiving cached pages AND
            && $this->checkUserCaching($this->cacheGroups)) {
            // set the current cacheKey
            $this->cacheKey = $cacheKey;

            return true;
        } else {
            return false;
        }
    }

    public function sendHeaders($modtime = 0)
    {
        // Note: still using $_SERVER here since xarServer is not initialized
        if (empty($modtime)) {
            // CHECKME: this means 304 will never apply then - is that what we want here ?
            // default to current time
            $modtime = time();
            if (!empty($this->cacheTime)) {
                // rounded down to the nearest multiple of $this->cacheTime
                $modtime -= ($modtime % $this->cacheTime);
            }
        }
        // @todo check if this can be auto-initialized
        $req = $this->getParent()->req();
        // doesn't seem to be taken into account ?
        $etag = $this->cacheCode . $modtime;
        $match = $_SERVER['HTTP_IF_NONE_MATCH'] ?? null;
        //$match = $req->getServerVar('HTTP_IF_NONE_MATCH');
        if (!empty($match) && $match == $etag) {
            // jsb:  for some reason, Mozilla based browsers
            // do not re-send an ETag after getting a 304
            // so this only works once per cached page
            header('HTTP/1.1 304 Not Modified');
            header("Cache-Control: public, must-revalidate");
            xarCore::exit();
            return;
        } else {
            $since = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? null;
            if (!empty($since) && strtotime($since) >= $modtime) {
                header('HTTP/1.1 304 Not Modified');
                header("Cache-Control: public, must-revalidate");
                xarCore::exit();
                return;
                // jsb: according to RFC 2616, if $match isn't empty but is
                // not equal to the ETag we should send a 412 response
                // But browser behavior seems inconsistant with the doc and
                // often results in more data being sent than is necessary.
            }
        }
        if (!empty($this->cacheExpireHeader)) {
            // this tells clients and proxies that this file is good until local
            // cache file is due to expire, and can be reused w/out revalidating
            header("Expires: "
                   . gmdate("D, d M Y H:i:s", $modtime + $this->cacheTime)
                   . " GMT");
            header("Cache-Control: public, max-age=" . $this->cacheTime);
        } else {
            header("Expires: 0");
            header("Cache-Control: public, must-revalidate");
        }
        $cacheCookie = $this->cache->outputCache->cacheCookie;
        header("ETag: $etag");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s", $modtime) . " GMT");
        // we can't use this after session_start()
        //session_cache_limiter('public');
        // PHP doesn't set the Pragma header when sending back a cookie
        if (isset($_COOKIE[$cacheCookie])) {
            header("Pragma: public");
        } else {
            header("Pragma:");
        }
        $cacheLocale = $this->cache->outputCache->cacheLocale;
        // Specify the charset
        $defaultLocale = !empty($cacheLocale) ? $cacheLocale : 'en_US.utf-8';
        [$lang_country, $charset] = explode('.', $defaultLocale);
        if (empty($charset)) {
            $charset = 'utf-8';
        }
        // CHECKME: what about other content types ?
        header("Content-type: text/html; charset=" . $charset);
    }

    public function isCached($cacheKey)
    {
        if (empty($this->cacheStorage)) {
            return false;
        }
        // we only cache the top-most page in case of nested pages
        if (empty($cacheKey) || $cacheKey != $this->cacheKey) {
            return false;
        }

        // Note: still using $_SERVER here since xarServer is not initialized
        if (// the cache entry exists and hasn't expired yet...
            ($this->cacheStorage->isCached($cacheKey))) {
            // create another copy for session-less page caching if necessary
            if (!empty($this->cacheNoSession)) {
                $cacheDir = $this->cache->getOutputCacheDir();
                $cacheKey2 = 'static';
                $cacheCode2 = md5($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                $cache_file2 = $cacheDir . "/page/$cacheKey2-$cacheCode2.php";
                // Note that if we get here, the first-time visitor will receive a session cookie,
                // so he will no longer benefit from this himself ;-)
                $this->cacheStorage->saveFile($cacheKey, $cache_file2);
            }

            $modtime = $this->cacheStorage->getLastModTime();
            // this may already exit if we have a 304 Not Modified
            $this->sendHeaders($modtime);

            return true;
        } else {
            return false;
        }
    }

    public function getCached($cacheKey, $output = 1)
    {
        if (empty($this->cacheStorage)) {
            return false;
        }
        // we only cache the top-most page in case of nested pages
        if (empty($cacheKey) || $cacheKey != $this->cacheKey) {
            return null;
        }

        // output the content directly to the browser here with $output = 1
        $result = $this->cacheStorage->getCached($cacheKey, $output);

        return $result;
    }

    public function setCached($cacheKey, $value)
    {
        if (empty($this->cacheStorage)) {
            return;
        }
        // we only cache the top-most page in case of nested pages
        if (empty($cacheKey) || $cacheKey != $this->cacheKey) {
            return;
        }

        $mem = $this->getParent()->mem();
        // Check if isCached() or xarSecurity or ... has told not to cache this page
        if ($mem->has('Page.Caching', 'nocache')) {
            // reset for next page request when using second-level cache storage
            $mem->del('Page.Caching', 'nocache');
            return;
        }

        // We delay checking this extra caching rule until now
        if ($this->cacheHookedOnly) {
            $modName = substr($cacheKey, 0, strpos($cacheKey, '-'));
            if (!$this->getParent()->hooked()->isAttached('cachemanager', $modName)) {
                return;
            }
        }

        // Note: still using $_SERVER here since xarServer is not initialized
        if (// the cache entry doesn't exist or has expired (no log here) AND
            // CHECKME: do we really want to check this again, or do we ignore it ?
            !($this->cacheStorage->isCached($cacheKey, 0, 0))
            // the cache collection directory hasn't reached its size limit...
            && !($this->cacheStorage->sizeLimitReached())) {
            // if request, modify the end of the file with a time stamp
            if ($this->cacheShowTime == 1) {
                $now = $this->getParent()->ml(
                    'Last updated on #(1)',
                    date(DATE_RFC7231),
                );
                $value = str_replace(
                    '</body>',
                    // TODO: set this up to be templated
                    '<div class="xar-sub" style="text-align: center; padding: 8px; ">' . $now . '</div></body>',
                    $value,
                );
            }

            $this->cacheStorage->setCached($cacheKey, $value);

            // create another copy for session-less page caching if necessary
            if (!empty($this->cacheNoSession)) {
                $cacheDir = $this->cache->getOutputCacheDir();
                $cacheKey2 = 'static';
                $cacheCode2 = md5($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                $cache_file2 = $cacheDir . "/page/$cacheKey2-$cacheCode2.php";
                // Note that if we get here, the first-time visitor will receive a session cookie,
                // so he will no longer benefit from this himself ;-)
                $this->cacheStorage->saveFile($cacheKey, $cache_file2);
            }

            $modtime = time();
            $this->sendHeaders($modtime);
        }
    }

    public function flushCached($cacheKey)
    {
        if (empty($this->cacheStorage)) {
            return;
        }

        $this->cacheStorage->flushCached($cacheKey);
    }

    public function checkUserCaching($cacheGroups)
    {
        $user = $this->getParent()->user();
        if (!$user->isLoggedIn()) {
            // always allow caching for anonymous users
            return true;
        } elseif (empty($cacheGroups)) {
            // if no other cache groups are defined
            return false;
        }

        $gidlist = $this->cache->getParents();

        $groups = explode(';', $cacheGroups);
        foreach ($groups as $groupid) {
            if (in_array($groupid, $gidlist)) {
                return true;
            }
        }
        return false;
    }
}
