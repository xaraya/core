<?php

/**
 * Core Cache Service for in-memory, request-scoped caching
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use xarCoreCache;
use sys;

sys::import('xaraya.services.servicetrait');

/**
 * For documentation purposes only
 */
interface CoreCacheInterface extends ServiceInterface
{
    public const SLICE = 'core.cache';

    public function has(string $scope, string $name): bool;
    public function get(string $scope, string $name): mixed;
    public function set(string $scope, string $name, mixed $value): void;
    public function del(string $scope, string $name): void;
    public function flush(string $scope): void;
    public function hasPreload(string $scope, ?string $name = null): bool;
    public function load(string $scope, ?string $name = null): bool;
    public function save(string $scope, ?string $name = null, ?string $source = null): bool;
}

/**
 * Core Cache Service for in-memory, request-scoped caching
 */
class CoreCacheService implements CoreCacheInterface
{
    use ServiceTrait;

    // /** @var array<string, array<string, mixed>> */
    // private array $cacheCollection = [];

    public function has(string $scope, string $name): bool
    {
        // return isset($this->cacheCollection[$scope][$name]);
        // this needs to call the original static method for now
        return xarCoreCache::isCached($scope, $name);
    }

    public function get(string $scope, string $name): mixed
    {
        // return $this->cacheCollection[$scope][$name] ?? null;
        // this needs to call the original static method for now
        return xarCoreCache::getCached($scope, $name);
    }

    public function set(string $scope, string $name, mixed $value): void
    {
        // $this->cacheCollection[$scope][$name] = $value;
        // this needs to call the original static method for now
        xarCoreCache::setCached($scope, $name, $value);
    }

    public function del(string $scope, string $name): void
    {
        // unset($this->cacheCollection[$scope][$name]);
        // this needs to call the original static method for now
        xarCoreCache::delCached($scope, $name);
    }

    public function flush(string $scope): void
    {
        // unset($this->cacheCollection[$scope]);
        // this needs to call the original static method for now
        xarCoreCache::flushCached($scope);
    }

    public function hasPreload(string $scope, ?string $name = null): bool
    {
        // this needs to call the original static method for now
        return xarCoreCache::hasPreload($scope, $name);
    }

    public function load(string $scope, ?string $name = null): bool
    {
        // this needs to call the original static method for now
        return xarCoreCache::loadCached($scope, $name);
    }

    public function save(string $scope, ?string $name = null, ?string $source = null): bool
    {
        // this needs to call the original static method for now
        return xarCoreCache::saveCached($scope, $name, $source);
    }
}
