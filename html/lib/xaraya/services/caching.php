<?php

/**
 * Caching available via methods (TODO)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use xarCache;
use xarModuleCache;
use xarObjectCache;
use sys;

sys::import('xaraya.services.servicetrait');

/**
 * For documentation purposes only - available via CachingTrait
 */
interface CachingInterface extends ServiceInterface
{
    /**
     * Get a cache key for object output caching
     * @param array<string, mixed> $args optional parameters
     * @return string|null cacheKey to be used with $this->cache()->(has|get|set)Object, or null if not applicable
     */
    public function getObjectKey(string $objectName, string $methodName = 'view', array $args = []): string|null;

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
}

/**
 * Caching available via methods
 * @template TParent of ServicesInterface
 */
trait CachingTrait
{
    /** @use ServiceTrait<TParent> */
    use ServiceTrait;

    /**
     * Get a cache key for object output caching
     * @param array<string, mixed> $args optional parameters
     * @return string|null cacheKey to be used with $this->cache()->(has|get|set)Object, or null if not applicable
     */
    public function getObjectKey(string $objectName, string $methodName = 'view', array $args = []): string|null
    {
        if (empty($objectName)) {
            return null;
        }
        return xarCache::getObjectKey($objectName, $methodName, $args);
    }

    /**
     * Check if the output of an object method is cached
     */
    public function hasObject(?string $cacheKey): bool
    {
        if (empty($cacheKey)) {
            return false;
        }
        return xarObjectCache::isCached($cacheKey);
    }

    /**
     * Get the output of the object method from cache
     */
    public function getObject(string $cacheKey): string
    {
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
        xarObjectCache::setCached($cacheKey, $value);
    }
}

/**
 * Access xar*Cache::* Caching methods (getModuleKey, getObjectKey, ...)
 *
 * Available methods:
 * - getObjectKey()
 * - hasObject()
 * - getObject()
 * - setObject()
 * - ...
 *
 * @template TParent of ServicesInterface
 */
class CachingService implements CachingInterface
{
    /** @use CachingTrait<TParent> */
    use CachingTrait;
}
