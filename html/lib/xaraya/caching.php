<?php

use Xaraya\Services\CachingService;

/**
 * Xaraya Caching Configuration
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
 */

sys::import('xaraya.services.xar');
use Xaraya\Services\xar;

/**
 * @deprecated 2.8.5 use xar::cache() instead
 */
class xarCache extends xarObject
{
    protected static ?CachingService $cacheService = null;

    protected static function cache(): CachingService
    {
        if (!isset(self::$cacheService)) {
            $xar = xar::getServicesClass();
            self::$cacheService = $xar->cache();
        }
        return self::$cacheService;
    }

    /**
     * Initialise the caching options
     *
     * @param string $cacheDir optional cache directory (default is sys::varpath() . '/cache')
     * @return bool or exit if session-less page caching finds a hit
     */
    public static function init($cacheDir = null)
    {
        // static cache for migration
        self::$cacheService = null;
        if (empty($cacheDir) || !is_dir($cacheDir)) {
            return self::cache()->init();
        }
        return self::cache()->init(['cacheDir' => $cacheDir]);
    }

    /**
     * Get the current caching configuration
     * @return array<string, mixed>
     */
    public static function getConfig()
    {
        return self::cache()->getConfig();
    }

    /**
     * Get a cache key for page output caching
     *
     * @param string $url optional url to be checked if not the current url
     * @return mixed cacheKey to be used with xar::cache()->(has|get|set)Page, or null if not applicable
     */
    public static function getPageKey($url = null)
    {
        return self::cache()->getPageKey($url);
    }

    /**
     * Get a cache key for block output caching
     *
     * @param array<string, mixed> $blockInfo block information
     * @return mixed cacheKey to be used with xar::cache()->(has|get|set)Block, or null if not applicable
     */
    public static function getBlockKey($blockInfo)
    {
        return self::cache()->getBlockKey($blockInfo);
    }

    /**
     * Get a cache key for module output caching
     *
     * @param string $modName
     * @param string $modType
     * @param string $funcName
     * @param array<string, mixed> $args optional parameters
     * @return mixed cacheKey to be used with xar::cache()->(has|get|set)Module, or null if not applicable
     */
    public static function getModuleKey($modName, $modType = 'user', $funcName = 'main', $args = [])
    {
        return self::cache()->getModuleKey($modName, $modType, $funcName, $args);
    }

    /**
     * Get a cache key for object output caching
     *
     * @param string $objectName
     * @param string $methodName
     * @param array<string, mixed> $args optional parameters
     * @return mixed cacheKey to be used with xar::cache()->(has|get|set)Object, or null if not applicable
     */
    public static function getObjectKey($objectName, $methodName = 'view', $args = [])
    {
        return self::cache()->getObjectKey($objectName, $methodName, $args);
    }

    /**
     * Get a cache key for variable value caching
     *
     * @param string $scope the scope identifying which part of the cache you want to access
     * @param string $name  the name of the variable in that particular scope
     * @return mixed cacheKey to be used with xar::cache()->(has|get|set)Variable, or null if not applicable
     */
    public static function getVariableKey($scope, $name)
    {
        return self::cache()->getVariableKey($scope, $name);
    }

    /**
     * Disable caching of the current output, e.g. when an authid is generated or if we redirect
     * @return void
     */
    public static function noCache()
    {
        return self::cache()->noCache();
    }

    /**
     * Keep track of some page title for caching - see xar::tpl()->setPageTitle()
     * @param ?string $title
     * @param ?string $module
     * @return void
     */
    public static function setPageTitle($title = null, $module = null)
    {
        return self::cache()->setPageTitle($title, $module);
    }

    /**
     * Keep track of some stylesheet for caching - see xar::mod()->apiFunc('themes','user','register')
     * @param array<string, mixed> $args
     * @return void
     */
    public static function addStyle(array $args = [])
    {
        return self::cache()->addStyle($args);
    }

    /**
     * Keep track of some javascript for caching - xar::mod()->apiFunc('themes','user','registerjs')
     * @param array<string, mixed> $args
     * @return void
     */
    public static function addJavaScript(array $args = [])
    {
        return self::cache()->addJavascript($args);
    }

    /**
     * Keep track of some meta tags for caching - xar::mod()->apiFunc('themes','user','registermeta')
     * @param array<string, mixed> $args
     * @return void
     */
    public static function addMeta(array $args = [])
    {
        return self::cache()->addMeta($args);
    }

    /**
     * Get a storage class instance for some type of cached data
     *
     * @param array<string, mixed> $args
     * with
     *     string  $storage the storage you want (filesystem, database, apcu or doctrine)
     *     string  $type the type of cached data (page, block, template, ...)
     *     string  $cachedir the path to the cache directory (for filesystem)
     *     string  $code the cache code (for URL factors et al.) if it's fixed
     *     integer $expire the expiration time for this data
     *     integer $sizelimit the maximum size for the cache storage
     *     string  $logfile the path to the logfile for HITs and MISSes
     *     integer $logsize the maximum size of the logfile
     *     string  $namespace optional namespace prefix for the cache keys
     *     object  $provider an instantiated Doctrine CacheProvider (for doctrine)
     * @return ixarCache_Storage the specified cache storage
     */
    public static function getStorage(array $args = [])
    {
        return self::cache()->getStorage($args);
    }

    /**
     * Get the parent group ids of the current user (with minimal overhead)
     *
     * @param ?int $currentid
     * @return array<mixed> of parent gids
     * @todo avoid DB lookup by passing groups via cookies ?
     * @todo Note : don't do this if admins get cached too :)
     */
    public static function getParents($currentid = null)
    {
        return self::cache()->getParents($currentid);
    }

    /**
     * Get the output cache directory to access stats and items in cache storage even
     * if output caching is disabled (cfr. cachemanager admin stats/view/flushcache)
     * @return string
     */
    public static function getOutputCacheDir()
    {
        return self::cache()->getOutputCacheDir();
    }

    /**
     * Summary of isOutputCacheEnabled
     * @return bool
     * @deprecated 2.8.4 not used
     */
    public static function isOutputCacheEnabled()
    {
        return self::cache()->isOutputCacheEnabled();
    }

    /**
     * Summary of isCoreCacheEnabled
     * @return bool
     * @deprecated 2.8.4 not used
     */
    public static function isCoreCacheEnabled()
    {
        return self::cache()->isCoreCacheEnabled();
    }

    /**
     * Summary of isTemplateCacheEnabled
     * @return bool
     * @deprecated 2.8.4 not used
     */
    public static function isTemplateCacheEnabled()
    {
        return self::cache()->isTemplateCacheEnabled();
    }

    /**
     * Summary of isVariableCacheEnabled
     * @return bool
     * @deprecated 2.8.4 not used
     */
    public static function isVariableCacheEnabled()
    {
        return self::cache()->isVariableCacheEnabled();
    }
}
