<?php
/**
 * Trait to cache variables in other classes
 *
 * Usage:
 *
 * class myFancyClass
 * {
 *     use xarCacheTrait;
 *
 *     public function __construct()
 *     {
 *         // ...
 *         static::$enableCache = true;
 *         static::setCacheScope('myFancyItems');
 *     }
 *
 *     public function getItemCached($id)
 *     {
 *         // ... get item from cache ...
 *         $cacheKey = static::getCacheKey($id);
 *         if (!empty($cacheKey) && static::isCached($cacheKey)) {
 *             return static::getCached($cacheKey);
 *         }
 *
 *         // ... retrieve item here in myFancyClass ...
 *         $item = $this->getItem($id);
 *
 *         // ... set item in cache ...
 *         // if you don't know the $cacheKey for item from before (e.g. because it was defined with $id elsewhere)
 *         // if (static::$enableCache && static::hasCacheKey()) {
 *         //     $cacheKey = self::getCacheKey();
 *         // }
 *         if (!empty($cacheKey)) {
 *             static::setCached($cacheKey, $item);
 *         }
 *         return $item;
 *     }
 * }
 *
 * @package core\caching
 * @subpackage caching
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/
/**
 * For documentation purposes only - available via xarCacheTrait
 */
interface xarCacheTraitInterface
{
    public static function setCacheScope($cacheScope, $allow = 0): void;
    public static function getCacheKey($id = null): mixed;
    public static function setCacheKey($cacheKey): void;
    public static function hasCacheKey(): bool;
    public static function isCached($cacheKey): bool;
    public static function getCached($cacheKey): mixed;
    public static function setCached($cacheKey, $value, $expire = null): void;
    public static function delCached($cacheKey): void;
    public static function keyCached($cacheKey): mixed;
    public static function getCachedValue($id, $callback, ...$args): mixed;
}

trait xarCacheTrait
{
    public static $enableCache = false;  // activate with self::$enableCache = true
    public static $_cacheScope = 'CacheTrait';
    public static $_cacheKey = null;

    public static function setCacheScope($cacheScope, $allow = 0): void
    {
        if (!static::$enableCache) {
            return;
        }
        static::$_cacheScope = $cacheScope;
        // @checkme what to do with unknown cache scopes? Exception, deny or allow by default?
        $settings = xarVariableCache::getCacheSettings();
        if (!isset($settings[$cacheScope])) {
            //throw new BadParameterException($cacheScope, 'Unknown cache scope: "#(1)"');
            xarVariableCache::$cacheSettings[$cacheScope] = $allow;
        }
    }

    public static function getCacheKey($id = null): mixed
    {
        if (!static::$enableCache) {
            return null;
        }
        if (!empty($id)) {
            static::$_cacheKey = xarCache::getVariableKey(static::$_cacheScope, $id);
        }
        return static::$_cacheKey;
    }

    public static function setCacheKey($cacheKey): void
    {
        if (!static::$enableCache) {
            return;
        }
        static::$_cacheKey = $cacheKey;
    }

    public static function hasCacheKey(): bool
    {
        if (!static::$enableCache || empty(static::$_cacheKey)) {
            return false;
        }
        return true;
    }

    public static function isCached($cacheKey): bool
    {
        if (!static::$enableCache || empty($cacheKey)) {
            return false;
        }
        return xarVariableCache::isCached($cacheKey);
    }

    public static function getCached($cacheKey): mixed
    {
        if (!static::$enableCache || empty($cacheKey)) {
            return null;
        }
        return xarVariableCache::getCached($cacheKey);
    }

    public static function setCached($cacheKey, $value, $expire = null): void
    {
        if (!static::$enableCache || empty($cacheKey)) {
            return;
        }
        xarVariableCache::setCached($cacheKey, $value, $expire);
    }

    public static function delCached($cacheKey): void
    {
        if (!static::$enableCache || empty($cacheKey)) {
            return;
        }
        xarVariableCache::delCached($cacheKey);
    }

    public static function keyCached($cacheKey): mixed
    {
        if (!static::$enableCache || empty($cacheKey)) {
            return null;
        }
        return xarVariableCache::keyCached($cacheKey);
    }

    /**
     * All-in-one utility method to get cached value if available, or set it based on callback function
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
