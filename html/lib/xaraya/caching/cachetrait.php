<?php

/**
 * Trait to cache variables in other classes
 *
 * Usage:
 * ```
 * use Xaraya\Caching\WithCacheInterface;
 * use Xaraya\Caching\WithCacheTrait;
 *
 * class myFancyClass implements WithCacheInterface
 * {
 *     use WithCacheTrait;  // activate with $this->enableCache(true)
 *
 *     public function __construct()
 *     {
 *         // ...
 *         $this->enableCache(true);
 *         $this->setCacheScope('myFancyItems');
 *     }
 *
 *     public function getItemCached($id)
 *     {
 *         // ... get item from cache ...
 *         $cacheKey = $this->getCacheKey($id);
 *         if ($this->isCached($cacheKey)) {
 *             return $this->getCached($cacheKey);
 *         }
 *
 *         // ... retrieve item here in myFancyClass ...
 *         $item = $this->getItem($id);
 *
 *         // ... set item in cache ...
 *         // if you don't know the $cacheKey for item from before (e.g. because it was defined with $id elsewhere)
 *         // if ($this->hasCacheKey()) {
 *         //     $cacheKey = $this->getCacheKey();
 *         // }
 *         $this->setCached($cacheKey, $item);
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

/**
 * For documentation purposes only - available via WithCacheTrait
 */
interface WithCacheInterface
{
    /**
     * Get or set enableCache
     */
    public function enableCache(?bool $enable = null): bool;

    /**
     * Summary of setCacheScope
     * @param string $cacheScope
     * @param int $allow
     * @return void
     */
    public function setCacheScope($cacheScope, $allow = 0): void;

    /**
     * Summary of getCacheKey
     * @param mixed $id
     * @return mixed
     */
    public function getCacheKey($id = null): mixed;

    /**
     * Summary of setCacheKey
     * @param string $cacheKey
     * @return void
     */
    public function setCacheKey($cacheKey): void;

    /**
     * Summary of hasCacheKey
     * @return bool
     */
    public function hasCacheKey(): bool;

    /**
     * Summary of isCached
     * @param string $cacheKey
     * @return bool
     */
    public function isCached($cacheKey): bool;

    /**
     * Summary of getCached
     * @param string $cacheKey
     * @return mixed
     */
    public function getCached($cacheKey): mixed;

    /**
     * Summary of setCached
     * @param string $cacheKey
     * @param mixed $value
     * @param ?int $expire
     * @return void
     */
    public function setCached($cacheKey, $value, $expire = null): void;

    /**
     * Summary of delCached
     * @param string $cacheKey
     * @return void
     */
    public function delCached($cacheKey): void;

    /**
     * Summary of keyCached
     * @param string $cacheKey
     * @return mixed
     */
    public function keyCached($cacheKey): mixed;

    /**
     * All-in-one utility method to get cached value if available, or set it based on callback function
     * @param mixed $id
     * @param mixed $callback
     * @param array<mixed> $args
     * @return mixed
     */
    public function getCachedValue($id, $callback, ...$args): mixed;
}

/**
 * @deprecated 2.9.3 use WithCacheInterface() instead
 */
interface CacheInterface extends WithCacheInterface
{
    // ...
}

/**
 * Summary of xarCacheTrait
 */
trait WithCacheTrait
{
    public bool $enableCache = false;  // activate with $this->enableCache(true)
    public string $_cacheScope = 'CacheTrait';
    public ?string $_cacheKey = null;
    protected ?CachingService $_cacheService = null;

    protected function _cache(): CachingService
    {
        if (!isset($this->_cacheService)) {
            // @checkme assume WithServicesTrait here
            $xar = $this->getServicesClass();
            $this->_cacheService = $xar->cache();
        }
        return $this->_cacheService;
    }

    /**
     * Get or set enableCache
     */
    public function enableCache(?bool $enable = null): bool
    {
        if (isset($enable)) {
            $this->enableCache = $enable;
            if ($enable) {
                $this->_cache();
            }
        }
        return $this->enableCache;
    }

    /**
     * Summary of setCacheScope
     * @param string $cacheScope
     * @param int $allow
     * @return void
     */
    public function setCacheScope($cacheScope, $allow = 0): void
    {
        if (!$this->enableCache) {
            return;
        }
        $this->_cacheScope = $cacheScope;
        // @checkme what to do with unknown cache scopes? Exception, deny or allow by default?
        $settings = $this->_cache()->variableCache->getCacheSettings();
        if (!isset($settings[$cacheScope])) {
            //throw new BadParameterException($cacheScope, 'Unknown cache scope: "#(1)"');
            $this->_cache()->variableCache->cacheSettings[$cacheScope] = $allow;
        }
    }

    /**
     * Summary of getCacheKey
     * @param mixed $id
     * @return mixed
     */
    public function getCacheKey($id = null): mixed
    {
        if (!$this->enableCache) {
            return null;
        }
        if (!empty($id)) {
            $this->_cacheKey = $this->_cache()->getVariableKey($this->_cacheScope, $id);
        }
        return $this->_cacheKey;
    }

    /**
     * Summary of setCacheKey
     * @param string $cacheKey
     * @return void
     */
    public function setCacheKey($cacheKey): void
    {
        if (!$this->enableCache) {
            return;
        }
        $this->_cacheKey = $cacheKey;
    }

    /**
     * Summary of hasCacheKey
     * @return bool
     */
    public function hasCacheKey(): bool
    {
        if (!$this->enableCache || empty($this->_cacheKey)) {
            return false;
        }
        return true;
    }

    /**
     * Summary of isCached
     * @param string $cacheKey
     * @return bool
     */
    public function isCached($cacheKey): bool
    {
        if (!$this->enableCache || empty($cacheKey)) {
            return false;
        }
        return $this->_cache()->hasVariable($cacheKey);
    }

    /**
     * Summary of getCached
     * @param string $cacheKey
     * @return mixed
     */
    public function getCached($cacheKey): mixed
    {
        if (!$this->enableCache || empty($cacheKey)) {
            return null;
        }
        return $this->_cache()->getVariable($cacheKey);
    }

    /**
     * Summary of setCached
     * @param string $cacheKey
     * @param mixed $value
     * @param ?int $expire
     * @return void
     */
    public function setCached($cacheKey, $value, $expire = null): void
    {
        if (!$this->enableCache || empty($cacheKey)) {
            return;
        }
        $this->_cache()->setVariable($cacheKey, $value, $expire);
    }

    /**
     * Summary of delCached
     * @param string $cacheKey
     * @return void
     */
    public function delCached($cacheKey): void
    {
        if (!$this->enableCache || empty($cacheKey)) {
            return;
        }
        $this->_cache()->delVariable($cacheKey);
    }

    /**
     * Summary of keyCached
     * @param string $cacheKey
     * @return mixed
     */
    public function keyCached($cacheKey): mixed
    {
        if (!$this->enableCache || empty($cacheKey)) {
            return null;
        }
        return $this->_cache()->keyVariable($cacheKey);
    }

    /**
     * All-in-one utility method to get cached value if available, or set it based on callback function
     * @param mixed $id
     * @param mixed $callback
     * @param array<mixed> $args
     * @return mixed
     */
    public function getCachedValue($id, $callback, ...$args): mixed
    {
        $cacheKey = $this->getCacheKey($id);
        if (!empty($cacheKey) && $this->isCached($cacheKey)) {
            return $this->getCached($cacheKey);
        }
        if (!empty($args)) {
            //array_unshift($args, $id);
            $item = call_user_func_array($callback, $args);
        } else {
            $item = call_user_func($callback, $id);
        }
        if (!empty($cacheKey)) {
            $this->setCached($cacheKey, $item);
        }
        return $item;
    }
}

/**
 * @deprecated 2.9.3 use WithCacheTrait() instead
 */
trait CacheTrait
{
    use WithCacheTrait;
}
