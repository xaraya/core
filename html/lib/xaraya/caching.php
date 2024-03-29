<?php
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

class xarCache extends xarObject
{
    public static bool $outputCacheIsEnabled    = false;
    public static bool $coreCacheIsEnabled      = true;
    public static bool $templateCacheIsEnabled  = true; // currently unused, cfr. xaraya/templates.php
    public static bool $variableCacheIsEnabled  = false;
    //public static bool $queryCacheIsEnabled     = false;
    public static string $cacheDir                = '';
    protected static bool $initialized = false;

    /**
     * Initialise the caching options
     *
     * @param string $cacheDir optional cache directory (default is sys::varpath() . '/cache')
     * @return void or exit if session-less page caching finds a hit
     */
    public static function init($cacheDir = null)
    {
        if (empty($cacheDir) && self::$initialized) {
            return;
        }
        if (empty($cacheDir) || !is_dir($cacheDir)) {
            $cacheDir = sys::varpath() . '/cache';
        }
        self::$cacheDir = $cacheDir;

        // Load the caching configuration
        $config = self::getConfig();

        // Enable output caching
        if (file_exists(self::$cacheDir . '/output/cache.touch')) {
            if (!empty($config)) {
                // initialize the output cache
                sys::import('xaraya.caching.output');
                self::$outputCacheIsEnabled = xarOutputCache::init($config);
                // Note : we may already exit here if session-less page caching is enabled
            } else {
                // if the config file is missing or empty, turn off output caching
                @unlink(self::$cacheDir . '/output/cache.touch');
            }
        }

        // Enable core caching
        sys::import('xaraya.caching.core');
        self::$coreCacheIsEnabled = xarCoreCache::init($config);

        // Enable template caching ? Too early in the process here, cfr. xaraya/templates.php

        // Enable variable caching (requires activating autoload for serialized objects et al.)
        if (!empty($config['Variable.CacheIsEnabled'])) {
            sys::import('xaraya.caching.variable');
            self::$variableCacheIsEnabled = xarVariableCache::init($config);
        }
        self::$initialized = true;
    }

    /**
     * Get the current caching configuration
     * @return array<string, mixed>
     */
    public static function getConfig()
    {
        // load the caching configuration
        $cachingConfiguration = [];
        if (file_exists(self::$cacheDir . '/config.caching.php')) {
            @include(self::$cacheDir . '/config.caching.php');
        }
        return $cachingConfiguration;
    }

    /**
     * Get a cache key for page output caching
     *
     * @param string $url optional url to be checked if not the current url
     * @return mixed cacheKey to be used with xarPageCache::(is|get|set)Cached, or null if not applicable
     */
    public static function getPageKey($url = null)
    {
        if (self::isOutputCacheEnabled() && xarOutputCache::isPageCacheEnabled()) {
            return xarPageCache::getCacheKey($url);
        }
        return null;
    }

    /**
     * Get a cache key for block output caching
     *
     * @param array<string, mixed> $blockInfo block information
     * @return mixed cacheKey to be used with xarBlockCache::(is|get|set)Cached, or null if not applicable
     */
    public static function getBlockKey($blockInfo)
    {
        if (self::isOutputCacheEnabled() && xarOutputCache::isBlockCacheEnabled()) {
            return xarBlockCache::getCacheKey($blockInfo);
        }
    }

    /**
     * Get a cache key for module output caching
     *
     * @param string $modName
     * @param string $modType
     * @param string $funcName
     * @param array<string, mixed> $args optional parameters
     * @return mixed cacheKey to be used with xarModuleCache::(is|get|set)Cached, or null if not applicable
     */
    public static function getModuleKey($modName, $modType = 'user', $funcName = 'main', $args = [])
    {
        if (self::isOutputCacheEnabled() && xarOutputCache::isModuleCacheEnabled()) {
            return xarModuleCache::getCacheKey($modName, $modType, $funcName, $args);
        }
        return null;
    }

    /**
     * Get a cache key for object output caching
     *
     * @param string $objectName
     * @param string $methodName
     * @param array<string, mixed> $args optional parameters
     * @return mixed cacheKey to be used with xarObjectCache::(is|get|set)Cached, or null if not applicable
     */
    public static function getObjectKey($objectName, $methodName = 'view', $args = [])
    {
        if (self::isOutputCacheEnabled() && xarOutputCache::isObjectCacheEnabled()) {
            return xarObjectCache::getCacheKey($objectName, $methodName, $args);
        }
        return null;
    }

    /**
     * Get a cache key for variable caching
     *
     * @param string $scope the scope identifying which part of the cache you want to access
     * @param string $name  the name of the variable in that particular scope
     * @return mixed cacheKey to be used with xarVariableCache::(is|get|set)Cached, or null if not applicable
     */
    public static function getVariableKey($scope, $name)
    {
        if (self::isVariableCacheEnabled()) {
            return xarVariableCache::getCacheKey($scope, $name);
        }
        return null;
    }

    /**
     * Disable caching of the current output, e.g. when an authid is generated or if we redirect
     * @return void
     */
    public static function noCache()
    {
        if (!self::isOutputCacheEnabled()) {
            return;
        }
        if (xarOutputCache::isPageCacheEnabled()) {
            // set the current cacheKey to null
            xarPageCache::$cacheKey = null;
            xarCoreCache::setCached('Page.Caching', 'nocache', true);
        }
        if (xarOutputCache::isBlockCacheEnabled()) {
            // set the current cacheKey to null
            xarBlockCache::$cacheKey = null;
        }
        if (xarOutputCache::isModuleCacheEnabled()) {
            // set the current cacheKey to null
            xarModuleCache::$cacheKey = null;
        }
        if (xarOutputCache::isObjectCacheEnabled()) {
            // set the current cacheKey to null
            xarObjectCache::$cacheKey = null;
        }
    }

    /**
     * Keep track of some page title for caching - see xarTpl::setPageTitle()
     * @param ?string $title
     * @param ?string $module
     * @return void
     */
    public static function setPageTitle($title = null, $module = null)
    {
        if (!self::isOutputCacheEnabled()) {
            return;
        }
        // TODO: refactor common code ?
        if (xarOutputCache::isModuleCacheEnabled()) {
            // set page title for module output
            xarModuleCache::setPageTitle($title, $module);
        }
        if (xarOutputCache::isObjectCacheEnabled()) {
            // set page title for object output
            xarObjectCache::setPageTitle($title, $module);
        }
    }

    /**
     * Keep track of some stylesheet for caching - see xarMod::apiFunc('themes','user','register')
     * @param array<string, mixed> $args
     * @return void
     */
    public static function addStyle(array $args = [])
    {
        if (!self::isOutputCacheEnabled()) {
            return;
        }
        // TODO: refactor common code ?
        if (xarOutputCache::isModuleCacheEnabled()) {
            // add stylesheet for module output
            xarModuleCache::addStyle($args);
        }
        if (xarOutputCache::isObjectCacheEnabled()) {
            // add stylesheet for object output
            xarObjectCache::addStyle($args);
        }
    }

    /**
     * Keep track of some javascript for caching - xarMod::apiFunc('themes','user','registerjs')
     * @param array<string, mixed> $args
     * @return void
     */
    public static function addJavaScript(array $args = [])
    {
        if (!self::isOutputCacheEnabled()) {
            return;
        }
        // TODO: refactor common code ?
        if (xarOutputCache::isModuleCacheEnabled()) {
            // add javascript for module output
            xarModuleCache::addJavaScript($args);
        }
        if (xarOutputCache::isObjectCacheEnabled()) {
            // add javascript for object output
            xarObjectCache::addJavaScript($args);
        }
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
        sys::import('xaraya.caching.storage');
        return xarCache_Storage::getCacheStorage($args);
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
        if (empty($currentid)) {
            $currentid = xarSession::getVar('role_id');
        }
        if (xarCoreCache::isCached('User.Variables.'.$currentid, 'parentlist')) {
            return xarCoreCache::getCached('User.Variables.'.$currentid, 'parentlist');
        }
        $rolemembers = xarDB::getPrefix() . '_rolemembers';
        $dbconn = xarDB::getConn();
        $query = "SELECT parent_id FROM $rolemembers WHERE role_id = ?";
        $stmt   = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery([$currentid]);

        $gidlist = [];
        while ($result->next()) {
            $gidlist[] = $result->getInt(1);
        }
        $result->Close();
        xarCoreCache::setCached('User.Variables.'.$currentid, 'parentlist', $gidlist);
        return $gidlist;
    }

    /**
     * Get the output cache directory to access stats and items in cache storage even
     * if output caching is disabled (cfr. xarcachemanager admin stats/view/flushcache)
     * @return string
     */
    public static function getOutputCacheDir()
    {
        // make sure xarOutputCache is initialized
        if (!self::isOutputCacheEnabled()) {
            // get the caching configuration
            $config = self::getConfig();
            // initialize the output cache
            sys::import('xaraya.caching.output');
            //self::$outputCacheIsEnabled = xarOutputCache::init($config);
            xarOutputCache::init($config);
            // make sure we don't cache here
            self::noCache();
        }
        return xarOutputCache::getCacheDir();
    }

    /**
     * Summary of isOutputCacheEnabled
     * @return bool
     */
    public static function isOutputCacheEnabled()
    {
        return self::$outputCacheIsEnabled;
    }

    /**
     * Summary of isCoreCacheEnabled
     * @return bool
     */
    public static function isCoreCacheEnabled()
    {
        return self::$coreCacheIsEnabled;
    }

    /**
     * Summary of isTemplateCacheEnabled
     * @return bool
     */
    public static function isTemplateCacheEnabled()
    {
        return self::$templateCacheIsEnabled;
    }

    /**
     * Summary of isVariableCacheEnabled
     * @return bool
     */
    public static function isVariableCacheEnabled()
    {
        return self::$variableCacheIsEnabled;
    }
}
