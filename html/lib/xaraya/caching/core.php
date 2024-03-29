<?php
/**
 * Xaraya Core Cache
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

/**
 * Core caching in memory for frequently-used values (within a single HTTP request)
 */
class xarCoreCache extends xarObject
{
    /** @var array<string, mixed> */
    private static $cacheCollection = [];
    private static ?ixarCache_Storage $cacheStorage = null;
    private static int $isBulkStorage = 0;

    /**
     * Initialise the caching options
     *
     * @param array<string, mixed> $config caching configuration from config.caching.php
     * @return boolean
     * @todo configure optional second-level cache here ?
    **/
    public static function init(array $config = [])
    {
        $scopes = ['CoreCache.Preload'];
        // initialize core cache with some values from caching configuration
        foreach ($scopes as $scope) {
            if (!empty($config[$scope])) {
                self::$cacheCollection[$scope] = $config[$scope];
            }
        }

        return true;
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
        // initialize cache if necessary
        self::$cacheCollection[$scope] ??= [];
        if (isset(self::$cacheCollection[$scope][$name])) {
            return true;

        } elseif (self::hasPreload($scope, $name) && self::loadCached($scope, $name)) {
            return true;

        // cache storage typically only works with a single cache namespace, so we add our own scope prefix here
        } elseif (isset(self::$cacheStorage) && empty(self::$isBulkStorage) && self::$cacheStorage->isCached($scope.':'.$name)) {
            // pre-fetch the value from second-level cache here (if we don't load from bulk storage)
            self::$cacheCollection[$scope][$name] = self::$cacheStorage->getCached($scope.':'.$name);
            return true;
        }
        return false;
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
        if (!isset(self::$cacheCollection[$scope][$name])) {
            // don't fetch the value from second-level cache here
            return;
        }
        return self::$cacheCollection[$scope][$name];
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
        // initialize cache if necessary
        self::$cacheCollection[$scope] ??= [];
        self::$cacheCollection[$scope][$name] = $value;
        if (self::hasPreload($scope, $name)) {
            self::saveCached($scope, $name);
        }
        if (isset(self::$cacheStorage) && empty(self::$isBulkStorage)) {
            // save the value to second-level cache here
            self::$cacheStorage->setCached($scope.':'.$name, $value);
        }
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
        if (isset(self::$cacheCollection[$scope][$name])) {
            unset(self::$cacheCollection[$scope][$name]);
        }
        if (self::hasPreload($scope, $name)) {
            self::delPreload($scope, $name);
        }
        if (isset(self::$cacheStorage) && empty(self::$isBulkStorage)) {
            // delete the value from second-level cache here
            self::$cacheStorage->delCached($scope.':'.$name);
        }
    }

    /**
     * Flush a particular cache (e.g. for session initialization)
     *
     * @param string $scope the scope identifying which part of the cache you want to wipe out
     * @return void
    **/
    public static function flushCached($scope)
    {
        if (isset(self::$cacheCollection[$scope])) {
            unset(self::$cacheCollection[$scope]);
        }
        if (self::hasPreload($scope)) {
            self::delPreload($scope);
        }
        if (isset(self::$cacheStorage) && empty(self::$isBulkStorage)) {
            // CHECKME: not all cache storage supports this in the same way !
            self::$cacheStorage->flushCached($scope.':');
        }
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
        if ($scope === 'CoreCache.Preload') {
            return false;
        }
        if (isset($name)) {
            // cache storage typically only works with a single cache namespace, so we add our own scope prefix here
            return self::isCached('CoreCache.Preload', $scope.':'.$name);
        }
        return self::isCached('CoreCache.Preload', $scope);
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
        if (isset($name)) {
            $filepath = sys::varpath() . '/cache/core/' . $scope . '.' . $name . '.php';
            if (!is_file($filepath)) {
                return false;
            }
            // initialize cache if necessary
            self::$cacheCollection[$scope] ??= [];
            // replace value for name in cache scope
            $value = include $filepath;
            self::$cacheCollection[$scope][$name] = $value;
            return true;
        }
        $filepath = sys::varpath() . '/cache/core/' . $scope . '.php';
        if (!is_file($filepath)) {
            return false;
        }
        // replace values for names in cache scope - keep the others as is
        $values = include $filepath;
        if (!is_array($values)) {
            return false;
        }
        // initialize cache if necessary
        self::$cacheCollection[$scope] ??= [];
        foreach ($values as $name => $value) {
            self::$cacheCollection[$scope][$name] = $value;
        }
        return true;
    }

    /**
     * Save a particular scope and name to .php file (opcache) - not serialized, so plain values/arrays only
     *
     * @param string $scope the scope identifying which part of the cache you want to access
     * @param ?string $name  the name of the variable in that particular scope
     * @return boolean
    **/
    public static function saveCached($scope, $name = null)
    {
        if (isset($name)) {
            if (!self::isCached($scope, $name)) {
                return false;
            }
            $filepath = sys::varpath() . '/cache/core/' . $scope . '.' . $name . '.php';
            $value = self::$cacheCollection[$scope][$name];
            $info = '<?php
$value = ' . var_export($value, true) . ';
return $value;
';
            file_put_contents($filepath, $info);
            return true;
        }
        if (!isset(self::$cacheCollection[$scope])) {
            return false;
        }
        $filepath = sys::varpath() . '/cache/core/' . $scope . '.php';
        $values = self::$cacheCollection[$scope];
        $info = '<?php
$values = ' . var_export($values, true) . ';
return $values;
';
        file_put_contents($filepath, $info);
        return true;
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
        if (isset($name)) {
            $filepath = sys::varpath() . '/cache/core/' . $scope . '.' . $name . '.php';
            if (is_file($filepath)) {
                unlink($filepath);
            }
            return;
        }
        $filepath = sys::varpath() . '/cache/core/' . $scope . '.php';
        if (is_file($filepath)) {
            unlink($filepath);
        }
    }

    /**
     * Set second-level cache storage if you want to keep values for longer than the current HTTP request
     *
     * @param ixarCache_Storage $cacheStorage  the cache storage instance you want to use (typically in-memory like apc, memcached, xcache, ...)
     * @param int    $cacheExpire   how long do you want to keep values in second-level cache storage (if the storage supports it)
     * @param int   $isBulkStorage do we load/save all variables in bulk by scope or not ? - deprecated
     * @return void
    **/
    public static function setCacheStorage($cacheStorage, $cacheExpire = 0, $isBulkStorage = 0)
    {
        self::$cacheStorage = $cacheStorage;
        self::$cacheStorage->setExpire($cacheExpire);
        // Make sure we use type 'core' for the cache storage here
        if (empty(self::$cacheStorage->type) || self::$cacheStorage->type != 'core') {
            self::$cacheStorage->type = 'core';
            // Update the global namespace and prefix of the cache storage
            self::$cacheStorage->setNamespace(self::$cacheStorage->namespace);
        }
        // see what's going on in the cache storage ;-)
        //self::$cacheStorage->logfile = sys::varpath() . '/logs/core_cache.txt';
        // FIXME: some in-memory cache storage requires explicit garbage collection !?

        self::$isBulkStorage = $isBulkStorage;
        /** @deprecated 2.4.1 no longer relevant
        if ($isBulkStorage) {
            // load from second-level cache storage here
            self::loadBulkStorage();
            // save to second-level cache storage at shutdown
            //register_shutdown_function(['xarCoreCache','saveBulkStorage']);
        }
         */
    }

    /**
     * Get the list of cached scopes from the cache collection
     *
     * @return array<mixed> list of cache scopes
    **/
    public static function getCachedScopes()
    {
        return array_keys(self::$cacheCollection);
    }

    /**
     * CHECKME: work with bulk load per scope instead of individual gets per scope:name ?
     *          But what about concurrent updates in bulk then (+ unserialize & autoload too early) ?<br/>
     *          Get the list of scopes and load each scope from second-level cache. There doesn't seem to be a big difference in performance using bulk or not, at least with xcache
     * @deprecated 2.4.1 no longer relevant
     * @return void
    */
    public static function loadBulkStorage()
    {
        if (!isset(self::$cacheStorage) || empty(self::$isBulkStorage)) {
            return;
        }
        // get the list of scopes
        if (!self::$cacheStorage->isCached('__scopelist__')) {
            return;
        }
        $scopelist = [];
        $value = self::$cacheStorage->getCached('__scopelist__');
        if (!empty($value)) {
            $scopelist = unserialize($value);
        }
        if (empty($scopelist)) {
            return;
        }
        // load each scope from second-level cache
        foreach ($scopelist as $scope) {
            $value = self::$cacheStorage->getCached($scope);
            if (!empty($value)) {
                self::$cacheCollection[$scope] = unserialize($value);
            }
        }
    }
    /**
     * CHECKME: work with bulk save per scope instead of individual gets per scope:name ?<br/>
     *          But what about concurrent updates in bulk then (+ unserialize & autosave too early) ?<br/>
     *          It gets the list of scopes and save each scope to second-level cache
     * @deprecated 2.4.1 no longer relevant
     * @return void
     */
    public static function saveBulkStorage()
    {
        if (!isset(self::$cacheStorage) || empty(self::$isBulkStorage)) {
            return;
        }
        // get the list of scopes
        $scopelist = array_keys(self::$cacheCollection);
        $value = serialize($scopelist);
        self::$cacheStorage->setCached('__scopelist__', $value);
        // save each scope to second-level cache
        foreach ($scopelist as $scope) {
            $value = serialize(self::$cacheCollection[$scope]);
            self::$cacheStorage->setCached($scope, $value);
        }
    }
}
