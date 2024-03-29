<?php
/**
 * Module gui function caching
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

class xarModuleCache extends xarObject
{
    public static int $cacheTime      = 7200;
    public static int $cacheSizeLimit = 2097152;
    /** @var ?array<mixed> */
    public static $cacheFunctions = null;
    public static ?ixarCache_Storage $cacheStorage   = null;

    /** @var ?array<mixed> */
    public static $cacheSettings  = null;
    public static ?string $cacheKey       = null;
    public static ?string $cacheCode      = null;

    public static ?int $noCache        = null;
    public static ?int $userShared     = null;
    public static ?int $expireTime     = null;
    public static string $funcParams   = '';

    /** @var ?array<mixed> */
    public static $pageTitle      = [];
    /** @var array<mixed> */
    public static $styleList      = [];
    /** @var array<mixed> */
    public static $scriptList     = [];

    /**
     * Initialise the module caching options
     *
     * @param array<string, mixed> $args
     * @return boolean true on success, false on failure
     */
    public static function init(array $args = [])
    {
        self::$cacheTime = $args['Module.TimeExpiration'] ?? 7200;
        self::$cacheSizeLimit = $args['Module.SizeLimit'] ?? 2097152;
        self::$cacheFunctions = $args['Module.CacheFunctions'] ?? [
            'main' => 1,
            'view' => 1,
            'display' => 0,
        ];

        $storage = !empty($args['Module.CacheStorage']) ?
            $args['Module.CacheStorage'] : 'filesystem';
        $provider = !empty($args['Module.CacheProvider']) ?
            $args['Module.CacheProvider'] : null;
        $logfile = !empty($args['Module.LogFile']) ?
            $args['Module.LogFile'] : null;
        self::$cacheStorage = xarCache::getStorage([
            'storage'   => $storage,
            'type'      => 'module',
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
     * Get a cache key if this module function is suitable for output caching
     *
     * @param string $modName registered name of module
     * @param string $modType type of function to run
     * @param string $funcName specific function to run
     * @param array<string, mixed> $args arguments to pass to the function
     * @return mixed cacheKey to be used with (is|get|set)Cached, or null if not applicable
     */
    public static function getCacheKey($modName, $modType = 'user', $funcName = 'main', $args = [])
    {
        if (empty(self::$cacheStorage)) {
            return;
        }

        if (empty($modName) || empty($funcName)) {
            return;
        }

        // Check if this module function is suitable for module caching
        if (!(self::checkCachingRules($modName, $modType, $funcName, $args))) {
            return;
        }

        // Check the specified function params
        if (empty(self::$funcParams)) {
            $params = [];
        } else {
            $params = explode(',', self::$funcParams);
        }
        // add missing function params to $args
        foreach ($params as $param) {
            if (!isset($args[$param])) {
                xarVar::fetch($param, 'isset', $args[$param], null, xarVar::NOT_REQUIRED);
            }
        }

        if (!empty($args['preview'])) {
            // we don't cache preview
            return false;
        }

        // we should be safe for caching now

        // set the current cacheKey
        self::$cacheKey = $modName . '-' . $funcName . '-';

        // CHECKME: should we detect the param for the itemid here ?
        if (!empty($args['itemid'])) {
            self::$cacheKey .= $args['itemid'];
        }

        // set the cacheCode for the current cacheKey

        // the output depends on the current host, theme and locale
        $factors = xarServer::getVar('HTTP_HOST') . xarTpl::getThemeDir() .
                   xarUser::getNavigationLocale();

        // add group or user identifier if needed
        if (self::$userShared == 2) {
            $factors .= 0;
        } elseif (self::$userShared == 1) {
            $gidlist = xarCache::getParents();
            $factors .= join(';', $gidlist);
        } else {
            $factors .= xarSession::getVar('role_id');
        }

        // add the function params
        $factors .= serialize($args);

        self::$cacheCode = md5($factors);
        self::$cacheStorage->setCode(self::$cacheCode);

        // return the cacheKey
        return self::$cacheKey;
    }

    /**
     * Get cache settings for the modules
     * @return array<mixed>
     */
    public static function getCacheSettings()
    {
        if (!isset(self::$cacheSettings)) {
            $settings = [];
            $serialsettings = xarModVars::get('modules', 'modulecache_settings');
            if (!empty($serialsettings)) {
                $settings = unserialize($serialsettings);
            }
            self::$cacheSettings = $settings;
        }
        return self::$cacheSettings;
    }

    /**
     * Check if this module function is suitable for module caching
     *
     * @param string $modName registered name of module
     * @param string $modType type of function to run
     * @param string $funcName specific function to run
     * @param array<string, mixed> $args arguments to pass to the function
     * @return boolean  true if the module function is suitable for caching, false if not
     */
    public static function checkCachingRules($modName, $modType = 'user', $funcName = 'main', $args = [])
    {
        // we only cache the top-most module function in case of nested functions
        if (!empty(self::$cacheKey)) {
            return false;
        }

        // we only support user functions here
        if ($modType != 'user') {
            return false;
        }

        self::$noCache    = null;
        self::$userShared = null;
        self::$expireTime = null;
        self::$funcParams = '';

        // CHECKME: should we allow POST requests here ?

        $settings = self::getCacheSettings();

        if (!empty($settings[$modName]) && !empty($settings[$modName][$funcName])) {
            self::$noCache    = $settings[$modName][$funcName]['nocache'];
            self::$userShared = $settings[$modName][$funcName]['usershared'];
            self::$expireTime = $settings[$modName][$funcName]['cacheexpire'];
            self::$funcParams = $settings[$modName][$funcName]['params'];
        } else {
            // this module function is not configured for caching
            return false;
        }

        if (!empty(self::$noCache)) {
            // this module function is configured for nocache
            return false;
        } else {
            self::$noCache = 0;
        }
        if (empty(self::$userShared)) {
            self::$userShared = 0;
        }
        if (!isset(self::$expireTime)) {
            self::$expireTime = self::$cacheTime;
        }

        return true;
    }

    /**
     * Check whether a module is cached
     *
     * @param  string $cacheKey the key identifying the particular module you want to access
     * @return boolean   true if the module is available in cache, false if not
     */
    public static function isCached($cacheKey = null)
    {
        if (empty(self::$cacheStorage)) {
            return false;
        }

        // we only cache the top-most module function in case of nested functions
        if (empty($cacheKey) || $cacheKey != self::$cacheKey) {
            return false;
        }

        // Note: we pass along the expiration time here, because it may be different for each module
        $result = self::$cacheStorage->isCached($cacheKey, self::$expireTime);

        if (empty($result)) {
            // initialize the title, styles and script arrays for the current cacheKey
            self::$pageTitle = null;
            self::$styleList = [];
            self::$scriptList = [];
        }

        return $result;
    }

    /**
     * Get the contents of a module from the cache
     *
     * @param  string $cacheKey the key identifying the particular module you want to access
     * @return string the cached output of the module function
     */
    public static function getCached($cacheKey)
    {
        if (empty(self::$cacheStorage)) {
            return '';
        }

        // we only cache the top-most module function in case of nested functions
        if (empty($cacheKey) || $cacheKey != self::$cacheKey) {
            return 'cacheKey mismatch in xarModuleCache::getCached - please submit a bug report with details of your configuration';
        }

        // Note: we pass along the expiration time here, because it may be different for each module
        $value = self::$cacheStorage->getCached($cacheKey, 0, self::$expireTime);

        // we're done with this cacheKey
        self::$cacheKey = null;

        $content = unserialize($value);
        if (!empty($content['title']) && is_array($content['title'])) {
            xarTpl::setPageTitle($content['title'][0], $content['title'][1]);
        }
        if (!empty($content['styles']) && is_array($content['styles'])) {
            foreach ($content['styles'] as $info) {
                xarMod::apiFunc('themes', 'user', 'register', $info);
            }
        }
        if (!empty($content['script']) && is_array($content['script'])) {
            foreach ($content['script'] as $info) {
                xarMod::apiFunc('themes', 'user', 'registerjs', $info);
            }
        }
        return $content['output'];
    }

    /**
     * Set the contents of a module in the cache
     *
     * @param  string $cacheKey the key identifying the particular module you want to access
     * @param  string $value    the new content for that module
     * @return void
     */
    public static function setCached($cacheKey, $value)
    {
        if (empty(self::$cacheStorage)) {
            return;
        }

        // we only cache the top-most module function in case of nested functions
        if (empty($cacheKey) || $cacheKey != self::$cacheKey) {
            return;
        }

        if (// the http request is a GET AND
            xarServer::getVar('REQUEST_METHOD') == 'GET' &&
        // CHECKME: do we really want to check this again, or do we ignore it ?
            // the cache entry doesn't exist or has expired (no log here) AND
            !(self::$cacheStorage->isCached($cacheKey, self::$expireTime, 0)) &&
            // the cache collection directory hasn't reached its size limit...
            !(self::$cacheStorage->sizeLimitReached())) {
            // CHECKME: add cacheKey cacheCode in comments if template filenames are already added
            if (xarTpl::outputTemplateFilenames()) {
                // separate with space here - we must avoid issues with double -- !?
                $value = "<!-- start cache: module/" . $cacheKey . ' ' . self::$cacheCode . " -->\n"
                         . $value
                         . "<!-- end cache: module/" . $cacheKey . ' ' . self::$cacheCode . " -->\n";
            }

            $content = ['output' => $value,
                        'link'   => xarServer::getCurrentURL(),
                        'title'  => self::$pageTitle,
                        'styles' => self::$styleList,
                        'script' => self::$scriptList];
            $value = serialize($content);

            // Note: we pass along the expiration time here, because it may be different for each module
            self::$cacheStorage->setCached($cacheKey, $value, self::$expireTime);
        }

        // we're done with this cacheKey
        self::$cacheKey = null;
    }

    /**
     * Flush module cache entries
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

    /**
     * Keep track of some page title for caching - see xarTpl::setPageTitle()
     * @param ?string $title
     * @param ?string $module
     * @return void
     */
    public static function setPageTitle($title = null, $module = null)
    {
        if (empty(self::$cacheKey)) {
            return;
        }
        self::$pageTitle = [$title, $module];
    }

    /**
     * Keep track of some stylesheet for caching - see xarMod::apiFunc('themes','user','register')
     * @param array<string, mixed> $args
     * @return void
     */
    public static function addStyle(array $args = [])
    {
        if (empty(self::$cacheKey)) {
            return;
        }
        self::$styleList[] = $args;
    }

    /**
     * Keep track of some javascript for caching - see xarMod::apiFunc('themes','user','registerjs')
     * @param array<string, mixed> $args
     * @return void
     */
    public static function addJavaScript(array $args = [])
    {
        if (empty(self::$cacheKey)) {
            return;
        }
        self::$scriptList[] = $args;
    }
}
