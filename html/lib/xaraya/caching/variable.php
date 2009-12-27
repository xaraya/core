<?php
/**
 * Xaraya Variable Cache
 *
 * @package core
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage caching
 * @author mikespub
 * @author jsb
 */

/**
 * Variable caching in cache storage to keep values for longer than the current HTTP request
 */
class xarVariableCache extends Object
{
// CHECKME: we won't actually support filesystem as storage here for security !?
    public static $cacheDir       = 'var/cache/variables';
    public static $cacheTime      = 7200;
    public static $cacheSizeLimit = 2097152;
    public static $cacheStorage   = null;

    /**
     * Initialise the variable caching options
     *
     * @param array $config caching configuration from config.caching.php
     * @return bool true on success, false on failure
     */
    public static function init(array $config = array())
    {
        self::$cacheTime = isset($config['Variable.TimeExpiration']) ?
            $config['Variable.TimeExpiration'] : 7200;
        self::$cacheSizeLimit = isset($config['Variable.SizeLimit']) ?
            $config['Variable.SizeLimit'] : 2097152;
        self::$cacheDir = isset($config['Variable.CacheDir']) ?
            $config['Variable.CacheDir'] : xarCache::$cacheDir . '/variables';

        $storage = !empty($config['Variable.CacheStorage']) ?
            $config['Variable.CacheStorage'] : 'database';
    // CHECKME: we won't actually support filesystem as storage here for security !?
        if ($storage == 'filesystem') {
            return false;
        }
        $logfile = !empty($config['Variable.LogFile']) ?
            $config['Variable.LogFile'] : null;
        // Note: make sure this isn't used before core loading if we use database storage
        self::$cacheStorage = xarCache::getStorage(array('storage'   => $storage,
                                                         'type'      => 'variable',
                                                         // we (won't) store cache files under this
                                                         'cachedir'  => self::$cacheDir,
                                                         'expire'    => self::$cacheTime,
                                                         'sizelimit' => self::$cacheSizeLimit,
                                                         'logfile'   => $logfile));
        if (empty(self::$cacheStorage)) {
            return false;
        }

        return true;
    }

    /**
     * Get a cache key if this variable is suitable for caching
     *
     * @param string $scope the scope identifying which part of the cache you want to access
     * @param string $name  the name of the variable in that particular scope
     * @return mixed cacheKey to be used with (is|get|set)Cached, or null if not applicable
     */
    public static function getCacheKey($scope, $name)
    {
        if (empty(self::$cacheStorage)) {
            return;
        }
    // CHECKME: use cacheCode and/or namespace instead ?
    // CHECKME: what about variable scope and/or name that isn't OS compliant ?
        // cache storage typically only works with a single cache namespace, so we add our own scope prefix here
        return $scope.':'.$name;
    }

    /**
     * Check if a variable value is cached
     *
     * @param string $cacheKey the key identifying the particular variable you want to access
     * @return bool true if the variable is cached, false if not
    **/
    public static function isCached($cacheKey)
    {
        if (empty(self::$cacheStorage)) {
            return false;
        }
        return self::$cacheStorage->isCached($cacheKey);
    }

    /**
     * Get the value of a cached variable
     *
     * @param string $cacheKey the key identifying the particular variable you want to access
     * @return mixed value of the variable, or null if variable isn't cached
    **/
    public static function getCached($cacheKey)
    {
        if (empty(self::$cacheStorage)) {
            return;
        }
        // get the value from cache
        $value = self::$cacheStorage->getCached($cacheKey);
        // check if we serialized it for storage
        if (!empty($value) && is_string($value) && strpos($value, ':serial:') === 0) {
            try {
                $value = unserialize(substr($value,8));
            } catch (Exception $e) {
            }
        }
        return $value;
    }

    /**
     * Set the value of a cached variable
     *
     * @param string $cacheKey the key identifying the particular variable you want to access
     * @param string $value    the new value for that variable
     * @param string $expire   optional expiration time for the varable (default is cacheTime)
     * @return null
    **/
    public static function setCached($cacheKey, $value, $expire = null)
    {
        if (empty(self::$cacheStorage)) {
            return;
        }
        // serialize the value for storage if necessary
        if (!is_string($value) && !is_numeric($value)) {
            $value = ':serial:' . serialize($value);
        }
        // save the value to cache
        if (isset($expire)) {
            self::$cacheStorage->setCached($cacheKey, $value, $expire);
        } else {
            self::$cacheStorage->setCached($cacheKey, $value);
        }
    }

    /**
     * Delete a cached variable
     *
     * @param string $cacheKey the key identifying the particular variable you want to access
     * @return null
    **/
    public static function delCached($cacheKey)
    {
        if (empty(self::$cacheStorage)) {
            return;
        }
        // delete the value from cache
        self::$cacheStorage->delCached($cacheKey);
    }

    /**
     * Flush a particular cache (e.g. for session initialization)
     *
     * @param string $scope the scope identifying which part of the cache you want to wipe out
     * @return null
    **/
    public static function flushCached($scope)
    {
        if (empty(self::$cacheStorage)) {
            return;
        }
    // CHECKME: use cacheCode and/or namespace instead ?
        // CHECKME: not all cache storage supports this in the same way !
        self::$cacheStorage->flushCached($scope.':');
    }
}

?>
