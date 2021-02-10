<?php
/**
 * Xaraya Variable Cache
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
 * Variable caching in cache storage to keep values for longer than the current HTTP request
 */
class xarVariableCache extends xarObject
{
    public static $cacheTime      = 7200;
    public static $cacheSizeLimit = 2097152;
    public static $cacheScopes    = null;
    public static $cacheStorage   = null;

    public static $cacheSettings  = null;
    public static $cacheDir       = null;

    /**
     * Initialise the variable caching options
     *
     * @param array $config caching configuration from config.caching.php
     * @return boolean true on success, false on failure
     */
    public static function init(array $config = array())
    {
        self::$cacheTime = isset($config['Variable.TimeExpiration']) ?
            $config['Variable.TimeExpiration'] : 7200;
        self::$cacheSizeLimit = isset($config['Variable.SizeLimit']) ?
            $config['Variable.SizeLimit'] : 2097152;
        self::$cacheScopes = isset($config['Variable.CacheScopes']) ?
            $config['Variable.CacheScopes'] : array('DataObject.ByName' => 1,
                                                'DataObjectList.ByName' => 1,
                                                'DataObject.ById' => 1,
                                                'DataObjectList.ById' => 1
                                                );

        $storage = !empty($config['Variable.CacheStorage']) ?
            $config['Variable.CacheStorage'] : 'database';
        $provider = !empty($config['Variable.CacheProvider']) ?
            $config['Variable.CacheProvider'] : null;
        self::$cacheDir = isset($config['Variable.CacheDir']) ?
            $config['Variable.CacheDir'] : xarCache::$cacheDir . '/variables';
    // CHECKME: we won't actually support filesystem as storage here for security !?
        if ($storage == 'filesystem') {
            return false;
        }
        $logfile = !empty($config['Variable.LogFile']) ?
            $config['Variable.LogFile'] : null;
        // Note: make sure this isn't used before core loading if we use database storage
        self::$cacheStorage = xarCache::getStorage(array('storage'   => $storage,
                                                         'type'      => 'variable',
                                                         'provider'  => $provider,
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

        if (empty($scope) || empty($name)) {
            return;
        }

        // Check if this variable is suitable for caching
        if (!(self::checkCachingRules($scope, $name))) {
            return;
        }

        // CHECKME: use cacheCode and/or namespace instead ?
        // Answer: unlike for output caching, no external factors should influence this (user, theme, locale, url params, ...)
        //self::$cacheCode = md5($factors);
        //self::$cacheStorage->setCode(self::$cacheCode);
        // CHECKME: what about variable scope and/or name that isn't OS compliant ?
        // Answer: this must be handled by the cacheStorage if necessary
        // cache storage typically only works with a single cache namespace, so we add our own scope prefix here
        // Note: the cacheStorage may add its own namespace internally to take into account the host, site, ...
        return $scope.':'.$name;
    }

    /**
     * Get cache settings for the variables
     * @return array
     */
    public static function getCacheSettings()
    {
        if (!isset(self::$cacheSettings)) {
            // TODO: make things configurable in xarcachemanager
            try {
                $settings = xarConfigVars::get(null, 'Site.Variable.CacheSettings');
            } catch (Exception $e) {
            }
            if (empty($settings)) {
                $settings = array();
                // CHECKME: get a list of potential scopes from xarCoreCache as examples?
                //$scopelist = xarCoreCache::getCachedScopes();
                //foreach ($scopelist as $scope) {
                //    $settings[$scope] = 0;
                //}
                // CHECKME: we only cache some default scopes for now
                foreach (self::$cacheScopes as $scope => $value) {
                    $settings[$scope] = $value;
                }
                xarConfigVars::set(null, 'Site.Variable.CacheSettings', $settings);
            }
            self::$cacheSettings = $settings;
        }
        return self::$cacheSettings;
    }

    /**
     * Check if this variable is suitable for caching in storage for longer than the current HTTP request
     *
     * @param string $scope the scope identifying which part of the cache you want to access
     * @param string $name  the name of the variable in that particular scope
     * @return boolean  true if the module function is suitable for caching, false if not
     */
    public static function checkCachingRules($scope, $name)
    {
        $settings = self::getCacheSettings();

        if (!empty($settings) && !empty($settings[$scope])) {
            // this variable scope is configured for caching
            // TODO: make things configurable in xarcachemanager
            // CHECKME: if we want to go further and specify rules by name within a scope someday...
            //if (is_array($settings[$scope]) && empty($settings[$scope][$name])) {
            //    // this variable scope & name is not configured for caching
            //    return false;
            //}

        } else {
            // this variable scope is not configured for caching
            return false;
        }

        return true;
    }

    /**
     * Check if a variable value is cached
     *
     * @param string $cacheKey the key identifying the particular variable you want to access
     * @return boolean true if the variable is cached, false if not
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
     * Flush a particular cache scope
     *
     * @param string $scope the scope identifying which part of the cache you want to wipe out
     * @return null
    **/
    public static function flushCached($scope)
    {
        if (empty(self::$cacheStorage)) {
            return;
        }
        // CHECKME: not all cache storage supports this in the same way !
        self::$cacheStorage->flushCached($scope.':');
    }
}

