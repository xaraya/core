<?php

/**
 * Caching available via methods (TODO)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use ixarCache_Storage;
use xarCache;
use xarModuleCache;
use xarBlockCache;
use xarObjectCache;
use xarPageCache;
use xarVariableCache;
use sys;

sys::import('xaraya.services.servicetrait');

/**
 * For documentation purposes only - available via CachingTrait
 */
interface CachingInterface extends ServiceInterface
{
    public const SLICE = 'caching';

    /**
     * Get a cache key for module output caching
     * @param array<string, mixed> $args optional parameters
     * @return string|null cacheKey to be used with $this->cache()->(has|get|set)Module, or null if not applicable
     */
    public function getModuleKey(string $modName, string $modType = 'user', string $funcName = 'main', array $args = []): ?string;

    /**
     * Check if the output of a module function is cached
     */
    public function hasModule(?string $cacheKey): bool;

    /**
     * Get the output of the module function from cache
     */
    public function getModule(string $cacheKey): string;

    /**
     * Set the output of the module function in cache
     */
    public function setModule(?string $cacheKey, string $value): void;

    /**
     * Get a cache key for block output caching
     * @param array<string, mixed> $blockInfo block information
     * @return string|null cacheKey to be used with $this->cache()->(has|get|set)Block, or null if not applicable
     */
    public function getBlockKey(array $blockInfo = []): ?string;

    /**
     * Check if the output of a block display is cached
     */
    public function hasBlock(?string $cacheKey): bool;

    /**
     * Get the output of the block display from cache
     */
    public function getBlock(string $cacheKey): string;

    /**
     * Set the output of the block display in cache
     */
    public function setBlock(?string $cacheKey, string $value): void;

    /**
     * Get a cache key for object output caching
     * @param array<string, mixed> $args optional parameters
     * @return string|null cacheKey to be used with $this->cache()->(has|get|set)Object, or null if not applicable
     */
    public function getObjectKey(string $objectName, string $methodName = 'view', array $args = []): ?string;

    /**
     * Check if the output of an object method is cached
     */
    public function hasObject(?string $cacheKey): bool;

    /**
     * Get the output of the object method from cache
     */
    public function getObject(string $cacheKey): string;

    /**
     * Set the output of the object method in cache
     */
    public function setObject(string $cacheKey, string $value): void;

    /**
     * Get a cache key for variable instance caching
     * @return string|null cacheKey to be used with xarVariableCache::(is|get|set)Cached, or null if not applicable
     */
    public function getVariableKey(string $scope, string $name): ?string;

    /**
     * Check if a variable value is cached
     */
    public function hasVariable(?string $cacheKey): bool;

    /**
     * Get the value of a cached variable
     */
    public function getVariable(string $cacheKey): string|object;

    /**
     * Set the value of a cached variable
     */
    public function setVariable(?string $cacheKey, string|object $value): void;

    /**
     * Delete a cached variable
     */
    public function delVariable(?string $cacheKey): void;

    /**
     * Get a storage class instance for some type of cached data
     * @param array<string, mixed> $args
     */
    public function getStorage(array $args = []): ixarCache_Storage;
}

/**
 * Caching available via methods
 */
trait CachingTrait
{
    use ServiceTrait;

    /** @var ?ixarCache_Storage */
    protected ?ixarCache_Storage $moduleStorage = null;
    protected ?ixarCache_Storage $blockStorage = null;
    protected ?ixarCache_Storage $objectStorage = null;
    protected ?ixarCache_Storage $variableStorage = null;
    protected ?ixarCache_Storage $pageStorage = null;

    /**
     * Get a cache key for module output caching
     * @param array<string, mixed> $args optional parameters
     * @return string|null cacheKey to be used with $this->cache()->(has|get|set)Module, or null if not applicable
     */
    public function getModuleKey(string $modName, string $modType = 'user', string $funcName = 'main', array $args = []): ?string
    {
        if (empty($modName)) {
            return null;
        }
        if (empty($this->moduleStorage)) {
            return null;
        }
        return xarModuleCache::getCacheKey($modName, $modType, $funcName, $args);
    }

    /**
     * Check if the output of a module function is cached
     */
    public function hasModule(?string $cacheKey): bool
    {
        if (empty($cacheKey)) {
            return false;
        }
        if (empty($this->moduleStorage)) {
            return false;
        }
        return xarModuleCache::isCached($cacheKey);
    }

    /**
     * Get the output of the module function from cache
     */
    public function getModule(string $cacheKey): string
    {
        if (empty($this->moduleStorage)) {
            return '';
        }
        return xarModuleCache::getCached($cacheKey);
    }

    /**
     * Set the output of the module function in cache
     */
    public function setModule(?string $cacheKey, string $value): void
    {
        if (empty($cacheKey)) {
            return;
        }
        if (empty($this->moduleStorage)) {
            return;
        }
        xarModuleCache::setCached($cacheKey, $value);
    }

    /**
     * Get a cache key for block output caching
     * @param array<string, mixed> $blockInfo block information
     * @return string|null cacheKey to be used with $this->cache()->(has|get|set)Block, or null if not applicable
     */
    public function getBlockKey(array $blockInfo = []): ?string
    {
        if (empty($this->blockStorage)) {
            return null;
        }
        return xarBlockCache::getCacheKey($blockInfo);
    }

    /**
     * Check if the output of a block display is cached
     */
    public function hasBlock(?string $cacheKey): bool
    {
        if (empty($cacheKey)) {
            return false;
        }
        if (empty($this->blockStorage)) {
            return false;
        }
        return xarBlockCache::isCached($cacheKey);
    }

    /**
     * Get the output of the block display from cache
     */
    public function getBlock(string $cacheKey): string
    {
        if (empty($this->blockStorage)) {
            return '';
        }
        return xarBlockCache::getCached($cacheKey);
    }

    /**
     * Set the output of the block display in cache
     */
    public function setBlock(?string $cacheKey, string $value): void
    {
        if (empty($cacheKey)) {
            return;
        }
        if (empty($this->blockStorage)) {
            return;
        }
        xarBlockCache::setCached($cacheKey, $value);
    }

    /**
     * Get a cache key for object output caching
     * @param array<string, mixed> $args optional parameters
     * @return string|null cacheKey to be used with $this->cache()->(has|get|set)Object, or null if not applicable
     */
    public function getObjectKey(string $objectName, string $methodName = 'view', array $args = []): ?string
    {
        if (empty($objectName)) {
            return null;
        }
        if (empty($this->objectStorage)) {
            return null;
        }
        return xarObjectCache::getCacheKey($objectName, $methodName, $args);
    }

    /**
     * Check if the output of an object method is cached
     */
    public function hasObject(?string $cacheKey): bool
    {
        if (empty($cacheKey)) {
            return false;
        }
        if (empty($this->objectStorage)) {
            return false;
        }
        return xarObjectCache::isCached($cacheKey);
    }

    /**
     * Get the output of the object method from cache
     */
    public function getObject(string $cacheKey): string
    {
        if (empty($this->objectStorage)) {
            return '';
        }
        return xarObjectCache::getCached($cacheKey);
    }

    /**
     * Set the output of the object method in cache
     */
    public function setObject(?string $cacheKey, string $value): void
    {
        if (empty($cacheKey)) {
            return;
        }
        if (empty($this->objectStorage)) {
            return;
        }
        xarObjectCache::setCached($cacheKey, $value);
    }

    /**
     * Get a cache key for variable value caching
     * @return string|null cacheKey to be used with xarVariableCache::(is|get|set)Cached, or null if not applicable
     */
    public function getVariableKey(string $scope, string $name): ?string
    {
        if (empty($this->variableStorage)) {
            return null;
        }
        return xarVariableCache::getCacheKey($scope, $name);
    }

    /**
     * Check if a variable value is cached
     */
    public function hasVariable(?string $cacheKey): bool
    {
        if (empty($cacheKey)) {
            return false;
        }
        if (empty($this->variableStorage)) {
            return false;
        }
        return xarVariableCache::isCached($cacheKey);
    }

    /**
     * Get the value of a cached variable
     */
    public function getVariable(string $cacheKey): string|object
    {
        if (empty($this->variableStorage)) {
            return '';
        }
        return xarVariableCache::getCached($cacheKey);
    }

    /**
     * Set the value of a cached variable
     */
    public function setVariable(?string $cacheKey, string|object $value): void
    {
        if (empty($cacheKey)) {
            return;
        }
        if (empty($this->variableStorage)) {
            return;
        }
        xarVariableCache::setCached($cacheKey, $value);
    }

    /**
     * Delete a cached variable
     */
    public function delVariable(?string $cacheKey): void
    {
        if (empty($cacheKey)) {
            return;
        }
        if (empty($this->variableStorage)) {
            return;
        }
        xarVariableCache::delCached($cacheKey);
    }

    /**
     * Get a storage class instance for some type of cached data
     * @param array<string, mixed> $args
     */
    public function getStorage(array $args = []): ixarCache_Storage
    {
        return xarCache::getStorage($args);
    }
}

/**
 * Access xar*Cache::* Caching methods (getModuleKey, getObjectKey, ...)
 *
 * Available methods:
 * - getModuleKey()
 * - hasModule()
 * - getModule()
 * - setModule()
 * - getBlockKey()
 * - hasBlock()
 * - getBlock()
 * - setBlock()
 * - getObjectKey()
 * - hasObject()
 * - getObject()
 * - setObject()
 * - getVariableKey()
 * - hasVariable()
 * - getVariable()
 * - setVariable()
 * - delVariable()
 * - ...
 *
 */
class CachingService implements CachingInterface
{
    use CachingTrait;

    public function __construct(mixed $parent)
    {
        $this->parent = $parent;

        // Get the caching configuration
        // $config = xarCache::getConfig();

        // Enable output caching if configured
        if (xarCache::$outputCacheIsEnabled) {
            // Note: we don't want to call xarOutputCache::init() here again
            $this->moduleStorage = xarModuleCache::$cacheStorage;
            $this->blockStorage = xarBlockCache::$cacheStorage;
            $this->objectStorage = xarObjectCache::$cacheStorage;
            $this->pageStorage = xarPageCache::$cacheStorage;
        }

        // Enable variable caching if configured
        if (xarCache::$variableCacheIsEnabled) {
            // Note: we don't want to call xarVariableCache::init() here again
            $this->variableStorage = xarVariableCache::$cacheStorage;
        }
    }
}
