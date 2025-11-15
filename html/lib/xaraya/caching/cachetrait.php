<?php

/**
 * Trait to cache variables in other classes
 *
 * Usage:
 * ```
 * use Xaraya\Caching\CacheInterface;
 * use Xaraya\Caching\CacheTrait;
 *
 * class myFancyClass implements CacheInterface
 * {
 *     use CacheTrait;  // activate with self::enableCache(true)
 *
 *     public function __construct()
 *     {
 *         // ...
 *         self::enableCache(true);
 *         self::setCacheScope('myFancyItems');
 *     }
 *
 *     public function getItemCached($id)
 *     {
 *         // ... get item from cache ...
 *         $cacheKey = self::getCacheKey($id);
 *         if (self::isCached($cacheKey)) {
 *             return self::getCached($cacheKey);
 *         }
 *
 *         // ... retrieve item here in myFancyClass ...
 *         $item = $this->getItem($id);
 *
 *         // ... set item in cache ...
 *         // if you don't know the $cacheKey for item from before (e.g. because it was defined with $id elsewhere)
 *         // if (self::hasCacheKey()) {
 *         //     $cacheKey = self::getCacheKey();
 *         // }
 *         self::setCached($cacheKey, $item);
 *         return $item;
 *     }
 * }
 * ```
 *
 * @package core\caching
 * @subpackage caching
 * @category Xaraya Web Applications Framework
 * @version 2.5.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Caching;

use Xaraya\Services\CachingService;
use Xaraya\Services\xar;

/**
 * For documentation purposes only - available via CacheTrait
 */
interface CacheInterface
{
    /**
     * Get or set enableCache
     */
    public static function enableCache(?bool $enable = null): bool;

    /**
     * Summary of setCacheScope
     * @param string $cacheScope
     * @param int $allow
     * @return void
     */
    public static function setCacheScope($cacheScope, $allow = 0): void;

    /**
     * Summary of getCacheKey
     * @param mixed $id
     * @return mixed
     */
    public static function getCacheKey($id = null): mixed;

    /**
     * Summary of setCacheKey
     * @param string $cacheKey
     * @return void
     */
    public static function setCacheKey($cacheKey): void;

    /**
     * Summary of hasCacheKey
     * @return bool
     */
    public static function hasCacheKey(): bool;

    /**
     * Summary of isCached
     * @param string $cacheKey
     * @return bool
     */
    public static function isCached($cacheKey): bool;

    /**
     * Summary of getCached
     * @param string $cacheKey
     * @return mixed
     */
    public static function getCached($cacheKey): mixed;

    /**
     * Summary of setCached
     * @param string $cacheKey
     * @param mixed $value
     * @param ?int $expire
     * @return void
     */
    public static function setCached($cacheKey, $value, $expire = null): void;

    /**
     * Summary of delCached
     * @param string $cacheKey
     * @return void
     */
    public static function delCached($cacheKey): void;

    /**
     * Summary of keyCached
     * @param string $cacheKey
     * @return mixed
     */
    public static function keyCached($cacheKey): mixed;

    /**
     * All-in-one utility method to get cached value if available, or set it based on callback function
     * @param mixed $id
     * @param mixed $callback
     * @param array<mixed> $args
     * @return mixed
     */
    public static function getCachedValue($id, $callback, ...$args): mixed;
}

/**
 * Summary of xarCacheTrait
 */
trait CacheTrait
{
    public static bool $enableCache = false;  // activate with self::enableCache(true)
    public static string $_cacheScope = 'CacheTrait';
    public static ?string $_cacheKey = null;
    protected static ?CachingService $_cacheService = null;

    protected static function _cache($xar = null): CachingService
    {
        if (!isset(self::$_cacheService)) {
            $xar ??= xar::getServicesClass();
            self::$_cacheService = $xar->cache();
        }
        return self::$_cacheService;
    }

    /**
     * Get or set enableCache
     */
    public static function enableCache(?bool $enable = null, $xar = null): bool
    {
        if (isset($enable)) {
            static::$enableCache = $enable;
            if ($enable) {
                self::_cache($xar);
            }
        }
        return static::$enableCache;
    }

    /**
     * Summary of setCacheScope
     * @param string $cacheScope
     * @param int $allow
     * @return void
     */
    public static function setCacheScope($cacheScope, $allow = 0): void
    {
        if (!static::$enableCache) {
            return;
        }
        static::$_cacheScope = $cacheScope;
        // @checkme what to do with unknown cache scopes? Exception, deny or allow by default?
        $settings = self::_cache()->variableCache->getCacheSettings();
        if (!isset($settings[$cacheScope])) {
            //throw new BadParameterException($cacheScope, 'Unknown cache scope: "#(1)"');
            self::_cache()->variableCache->cacheSettings[$cacheScope] = $allow;
        }
    }

    /**
     * Summary of getCacheKey
     * @param mixed $id
     * @return mixed
     */
    public static function getCacheKey($id = null): mixed
    {
        if (!static::$enableCache) {
            return null;
        }
        if (!empty($id)) {
            static::$_cacheKey = self::_cache()->getVariableKey(static::$_cacheScope, $id);
        }
        return static::$_cacheKey;
    }

    /**
     * Summary of setCacheKey
     * @param string $cacheKey
     * @return void
     */
    public static function setCacheKey($cacheKey): void
    {
        if (!static::$enableCache) {
            return;
        }
        static::$_cacheKey = $cacheKey;
    }

    /**
     * Summary of hasCacheKey
     * @return bool
     */
    public static function hasCacheKey(): bool
    {
        if (!static::$enableCache || empty(static::$_cacheKey)) {
            return false;
        }
        return true;
    }

    /**
     * Summary of isCached
     * @param string $cacheKey
     * @return bool
     */
    public static function isCached($cacheKey): bool
    {
        if (!static::$enableCache || empty($cacheKey)) {
            return false;
        }
        return self::_cache()->hasVariable($cacheKey);
    }

    /**
     * Summary of getCached
     * @param string $cacheKey
     * @return mixed
     */
    public static function getCached($cacheKey): mixed
    {
        if (!static::$enableCache || empty($cacheKey)) {
            return null;
        }
        return self::_cache()->getVariable($cacheKey);
    }

    /**
     * Summary of setCached
     * @param string $cacheKey
     * @param mixed $value
     * @param ?int $expire
     * @return void
     */
    public static function setCached($cacheKey, $value, $expire = null): void
    {
        if (!static::$enableCache || empty($cacheKey)) {
            return;
        }
        self::_cache()->setVariable($cacheKey, $value, $expire);
    }

    /**
     * Summary of delCached
     * @param string $cacheKey
     * @return void
     */
    public static function delCached($cacheKey): void
    {
        if (!static::$enableCache || empty($cacheKey)) {
            return;
        }
        self::_cache()->delVariable($cacheKey);
    }

    /**
     * Summary of keyCached
     * @param string $cacheKey
     * @return mixed
     */
    public static function keyCached($cacheKey): mixed
    {
        if (!static::$enableCache || empty($cacheKey)) {
            return null;
        }
        return self::_cache()->keyVariable($cacheKey);
    }

    /**
     * All-in-one utility method to get cached value if available, or set it based on callback function
     * @param mixed $id
     * @param mixed $callback
     * @param array<mixed> $args
     * @return mixed
     */
    public static function getCachedValue($id, $callback, ...$args): mixed
    {
        $cacheKey = static::getCacheKey($id);
        if (!empty($cacheKey) && static::isCached($cacheKey)) {
            return static::getCached($cacheKey);
        }
        if (!empty($args)) {
            //array_unshift($args, $id);
            $item = call_user_func_array($callback, $args);
        } else {
            $item = call_user_func($callback, $id);
        }
        if (!empty($cacheKey)) {
            static::setCached($cacheKey, $item);
        }
        return $item;
    }
}
