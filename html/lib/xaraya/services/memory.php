<?php

/**
 * Core Cache Service for in-memory, request-scoped caching
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use ixarCache_Storage;
use sys;

/**
 * For documentation purposes only
 */
interface MemoryInterface extends ServiceInterface
{
    public const SLICE = 'memory';

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
class MemoryService implements MemoryInterface
{
    use ServiceTrait;

    /** @var array<string, array<string, mixed>> */
    private array $cacheCollection = [];
    private ?ixarCache_Storage $cacheStorage = null;

    /**
     * Initialise the caching options
     *
     * @param array<string, mixed> $config caching configuration from config.caching.php
     * @todo configure optional second-level cache here ?
    **/
    public function init(array $config = []): bool
    {
        $scopes = ['CoreCache.Preload'];
        $this->getContext()[static::SLICE] ??= [];
        // initialize core cache with some values from caching configuration
        foreach ($scopes as $scope) {
            if (!empty($config[$scope])) {
                $this->cacheCollection[$scope] = $config[$scope];
                $this->getContext()[static::SLICE][$scope] = $config[$scope];
            }
        }

        return true;
    }

    public function has(string $scope, string $name): bool
    {
        // initialize cache if necessary
        $this->cacheCollection[$scope] ??= [];
        if (isset($this->cacheCollection[$scope][$name])) {
            return true;

        } elseif ($this->hasPreload($scope, $name) && $this->load($scope, $name)) {
            return true;

            // cache storage typically only works with a single cache namespace, so we add our own scope prefix here
        } elseif (isset($this->cacheStorage) && $this->cacheStorage->isCached($scope . ':' . $name)) {
            // pre-fetch the value from second-level cache here (if we don't load from bulk storage)
            $this->cacheCollection[$scope][$name] = $this->cacheStorage->getCached($scope . ':' . $name);
            return true;
        }
        return false;
    }

    public function get(string $scope, string $name): mixed
    {
        if (!isset($this->cacheCollection[$scope][$name])) {
            // don't fetch the value from second-level cache here
            return null;
        }
        return $this->cacheCollection[$scope][$name];
    }

    public function set(string $scope, string $name, mixed $value): void
    {
        // initialize cache if necessary
        $this->cacheCollection[$scope] ??= [];
        $this->cacheCollection[$scope][$name] = $value;
        if ($this->hasPreload($scope, $name)) {
            $this->saveCached($scope, $name);
        }
        if (isset($this->cacheStorage)) {
            // save the value to second-level cache here
            $this->cacheStorage->setCached($scope . ':' . $name, $value);
        }
    }

    public function del(string $scope, string $name): void
    {
        if (isset($this->cacheCollection[$scope][$name])) {
            unset($this->cacheCollection[$scope][$name]);
        }
        if ($this->hasPreload($scope, $name)) {
            $this->delPreload($scope, $name);
        }
        if (isset($this->cacheStorage)) {
            // delete the value from second-level cache here
            $this->cacheStorage->delCached($scope . ':' . $name);
        }
    }

    public function flush(string $scope): void
    {
        if (isset($this->cacheCollection[$scope])) {
            unset($this->cacheCollection[$scope]);
        }
        if ($this->hasPreload($scope)) {
            $this->delPreload($scope);
        }
        if (isset($this->cacheStorage)) {
            // CHECKME: not all cache storage supports this in the same way !
            $this->cacheStorage->flushCached($scope . ':');
        }
    }

    public function hasPreload(string $scope, ?string $name = null): bool
    {
        if ($scope === 'CoreCache.Preload') {
            return false;
        }
        if (isset($name)) {
            // cache storage typically only works with a single cache namespace, so we add our own scope prefix here
            return $this->has('CoreCache.Preload', $scope . ':' . $name);
        }
        return $this->has('CoreCache.Preload', $scope);
    }

    public function load(string $scope, ?string $name = null): bool
    {
        if (isset($name)) {
            $filepath = sys::varpath() . '/cache/core/' . $scope . '.' . $name . '.php';
            if (!is_file($filepath)) {
                return false;
            }
            // initialize cache if necessary
            $this->cacheCollection[$scope] ??= [];
            // replace value for name in cache scope
            $value = include $filepath;
            $this->cacheCollection[$scope][$name] = $value;
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
        $this->cacheCollection[$scope] ??= [];
        foreach ($values as $name => $value) {
            $this->cacheCollection[$scope][$name] = $value;
        }
        return true;
    }

    public function save(string $scope, ?string $name = null, ?string $source = null): bool
    {
        $source ??= __METHOD__;
        $date = date('c');
        if (isset($name)) {
            if (!$this->has($scope, $name)) {
                return false;
            }
            $filepath = sys::varpath() . '/cache/core/' . $scope . '.' . $name . '.php';
            $value = $this->cacheCollection[$scope][$name];
            $info = '<?php
/**
 * Exported by ' . $source . '
 * Generated: ' . $date . '
 */
$value = ' . var_export($value, true) . ';
return $value;
';
            file_put_contents($filepath, $info);
            return true;
        }
        if (!isset($this->cacheCollection[$scope])) {
            return false;
        }
        $filepath = sys::varpath() . '/cache/core/' . $scope . '.php';
        $values = $this->cacheCollection[$scope];
        $info = '<?php
/**
 * Exported by ' . $source . '
 * Generated: ' . $date . '
 */
$values = ' . var_export($values, true) . ';
return $values;
';
        file_put_contents($filepath, $info);
        return true;
    }

    public function delPreload(string $scope, ?string $name = null)
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

    public function setCacheStorage(ixarCache_Storage $cacheStorage, int $cacheExpire = 0)
    {
        $this->cacheStorage = $cacheStorage;
        $this->cacheStorage->setExpire($cacheExpire);
        // Make sure we use type 'core' for the cache storage here
        if (empty($this->cacheStorage->type) || $this->cacheStorage->type != 'core') {
            $this->cacheStorage->type = 'core';
            // Update the global namespace and prefix of the cache storage
            $this->cacheStorage->setNamespace($this->cacheStorage->namespace);
        }
        // see what's going on in the cache storage ;-)
        //$this->cacheStorage->logfile = sys::varpath() . '/logs/core_cache.txt';
        // FIXME: some in-memory cache storage requires explicit garbage collection !?
    }

    public function getCachedScopes()
    {
        return array_keys($this->cacheCollection);
    }
}
