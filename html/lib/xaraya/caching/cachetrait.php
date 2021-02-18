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
 *         static::setCacheScope('myFancyItems');
 *     }
 *
 *     public function getItemCached($id)
 *     {
 *         $cacheKey = static::getCacheKey($id);
 *         if (!empty($cacheKey) && static::isCached($cacheKey)) {
 *             return static::getCached($cacheKey);
 *         }
 *         // ... retrieve item ...
 *         $item = $this->getItem($id);
 *         if (!empty($cacheKey)) {
 *             static::setCached($cacheKey, $item);
 *         }
 *         return $item;
 *     }
 * }
 *
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
**/
trait xarCacheTrait
{
    public static $enableCache = false;
    public static $_cacheScope = 'CacheTrait';
    public static $_cacheKey = null;

    public static function setCacheScope($cacheScope, $allow = 0)
    {
        static::$_cacheScope = $cacheScope;
        // @checkme what to do with unknown cache scopes? Exception, deny or allow by default?
        $settings = xarVariableCache::getCacheSettings();
        if (!isset($settings[$cacheScope])) {
            //throw new BadParameterException($cacheScope, 'Unknown cache scope: "#(1)"');
            xarVariableCache::$cacheSettings[$cacheScope] = $allow;
        }
    }

    public static function getCacheKey($id = null)
    {
        if (!empty($id)) {
            static::$_cacheKey = xarCache::getVariableKey(static::$_cacheScope, $id);
        }
        return static::$_cacheKey;
    }

    public static function setCacheKey($cacheKey)
    {
        static::$_cacheKey = $cacheKey;
    }

    public static function hasCacheKey()
    {
        if (!empty(static::$_cacheKey)) {
            return true;
        }
        return false;
    }

    public static function isCached($cacheKey)
    {
        return xarVariableCache::isCached($cacheKey);
    }

    public static function getCached($cacheKey)
    {
        return xarVariableCache::getCached($cacheKey);
    }

    public static function setCached($cacheKey, $value)
    {
        xarVariableCache::setCached($cacheKey, $value);
    }

    public static function delCached($cacheKey)
    {
        xarVariableCache::delCached($cacheKey);
    }

    public static function getCachedValue($id, $callback, ...$args)
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
