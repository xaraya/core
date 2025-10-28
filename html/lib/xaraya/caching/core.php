<?php

/**
 * Xaraya Core Cache
 *
 * @package core\caching
 * @subpackage caching
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub
 * @author jsb
 */

use Xaraya\Services\MemoryService;
use Xaraya\Services\xar;

/**
 * Core caching in memory for frequently-used values (within a single HTTP request)
 * @deprecated 2.8.4 use xar::mem() instead
 */
class xarCoreCache extends xarObject
{
    protected static ?MemoryService $mem = null;

    protected static function mem()
    {
        if (!isset(self::$mem)) {
            self::$mem = xar::mem();
        }
        return self::$mem;
    }

    /**
     * Initialise the caching options
     *
     * @param array<string, mixed> $config caching configuration from config.caching.php
     * @return boolean
     * @todo configure optional second-level cache here ?
    **/
    public static function init(array $config = [])
    {
        return self::mem()->init($config);
    }

    /**
     * Check if a variable value is cached
     *
     * @param string $scope the scope identifying which part of the cache you want to access
     * @param string $name  the name of the variable in that particular scope
     * @return boolean true if the variable is cached, false if not
    **/
    public static function isCached($scope, $name)
    {
        return self::mem()->has($scope, $name);
    }

    /**
     * Get the value of a cached variable
     *
     * @param string $scope the scope identifying which part of the cache you want to access
     * @param string $name  the name of the variable in that particular scope
     * @return mixed value of the variable, or null if variable isn't cached
    **/
    public static function getCached($scope, $name)
    {
        return self::mem()->get($scope, $name);
    }

    /**
     * Set the value of a cached variable
     *
     * @param string $scope the scope identifying which part of the cache you want to access
     * @param string $name  the name of the variable in that particular scope
     * @param mixed $value the new value for that variable
     * @return void
    **/
    public static function setCached($scope, $name, $value)
    {
        return self::mem()->set($scope, $name, $value);
    }

    /**
     * Delete a cached variable
     *
     * @param string $scope the scope identifying which part of the cache you want to access
     * @param string $name  the name of the variable in that particular scope
     * @return void
    **/
    public static function delCached($scope, $name)
    {
        return self::mem()->del($scope, $name);
    }

    /**
     * Flush a particular cache (e.g. for session initialization)
     *
     * @param string $scope the scope identifying which part of the cache you want to wipe out
     * @return void
    **/
    public static function flushCached($scope)
    {
        return self::mem()->flush($scope);
    }

    /**
     * Check if a particular scope and name can be preloaded
     *
     * @param string $scope the scope identifying which part of the cache you want to access
     * @param ?string $name  the name of the variable in that particular scope
     * @return boolean
    **/
    public static function hasPreload($scope, $name = null)
    {
        return self::mem()->hasPreload($scope, $name);
    }

    /**
     * Load a particular scope and name from .php file (opcache) - not serialized, so plain values/arrays only
     *
     * @param string $scope the scope identifying which part of the cache you want to access
     * @param ?string $name  the name of the variable in that particular scope
     * @return boolean
    **/
    public static function loadCached($scope, $name = null)
    {
        return self::mem()->load($scope, $name);
    }

    /**
     * Save a particular scope and name to .php file (opcache) - not serialized, so plain values/arrays only
     *
     * @param string $scope the scope identifying which part of the cache you want to access
     * @param ?string $name  the name of the variable in that particular scope
     * @param ?string $source the source requester for saving this scope and name
     * @return boolean
    **/
    public static function saveCached($scope, $name = null, $source = null)
    {
        return self::mem()->save($scope, $name);
    }

    /**
     * Delete preload .php file (opcache) for a particular scope and name
     *
     * @param string $scope the scope identifying which part of the cache you want to access
     * @param ?string $name  the name of the variable in that particular scope
     * @return void
    **/
    public static function delPreload($scope, $name = null)
    {
        return self::mem()->delPreload($scope, $name);
    }

    /**
     * Set second-level cache storage if you want to keep values for longer than the current HTTP request
     *
     * @param ixarCache_Storage $cacheStorage  the cache storage instance you want to use (typically in-memory like apcu, redis, ...)
     * @param int    $cacheExpire   how long do you want to keep values in second-level cache storage (if the storage supports it)
     * @return void
    **/
    public static function setCacheStorage($cacheStorage, $cacheExpire = 0)
    {
        return self::mem()->setCacheStorage($cacheStorage, $cacheExpire);
    }

    /**
     * Get the list of cached scopes from the cache collection
     *
     * @return array<mixed> list of cache scopes
    **/
    public static function getCachedScopes()
    {
        return self::mem()->getCachedScopes();
    }
}
