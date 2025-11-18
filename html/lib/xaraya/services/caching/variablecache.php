<?php

/**
 * Variable caching (class instance or data)
 *
 * @package core\services
 * @subpackage caching
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services\Caching;

use Xaraya\Services\CachingService;
use Xaraya\Services\ServiceClass;
use ixarCache_Storage;
use Exception;

/**
 * Variable caching (class instance or data)
 */
class VariableCache extends ServiceClass
{
    public const SLICE = 'caching.variable';

    public int $cacheTime = 7200;
    public int $cacheSizeLimit = 2097152;
    /** @var ?array<string, mixed> */
    public $cacheScopes = null;
    public ?ixarCache_Storage $cacheStorage = null;

    /** @var ?array<mixed> */
    public $cacheSettings = null;
    public ?string $cacheDir = null;

    protected CachingService $cache;

    /**
     * Create service class for parent
     */
    public function __construct(mixed $parent)
    {
        $this->parent = $parent;
        $this->cache = $parent->cache();
    }

    /**
     * Initialize service class
     * @param array<string, mixed> $config
     */
    public function init(array $config = []): bool
    {
        $this->cacheTime = $config['Variable.TimeExpiration'] ?? 7200;
        $this->cacheSizeLimit = $config['Variable.SizeLimit'] ?? 2097152;
        $this->cacheScopes = $config['Variable.CacheScopes'] ?? [
            'DataObject.ByName' => 1,
            'DataObjectList.ByName' => 1,
            'DataObject.ById' => 1,
            'DataObjectList.ById' => 1,
            // can't serialize schema with closures
            'GraphQLAPI.Schema' => 0,
            'GraphQLAPI.QueryPlan' => 1,
            'GraphQLAPI.Operation' => 1,
            'RestAPI.Operation' => 1,
            'RestAPI.Objects' => 0,
            'RestAPI.ObjectList' => ['sample' => 1],
        ];
        $this->cacheSettings = $config['Variable.CacheSettings'] ?? $this->cacheScopes;

        $storage = !empty($config['Variable.CacheStorage'])
            ? $config['Variable.CacheStorage'] : 'apcu';
        $provider = !empty($config['Variable.CacheProvider'])
            ? $config['Variable.CacheProvider'] : null;
        $this->cacheDir = $config['Variable.CacheDir'] ?? $this->cache->cacheDir . '/variables';
        // CHECKME: we won't actually support filesystem as storage here for security !?
        if ($storage == 'filesystem') {
            return false;
        }
        $logfile = !empty($config['Variable.LogFile'])
            ? $config['Variable.LogFile'] : null;
        // Note: make sure this isn't used before core loading if we use database storage
        $this->cacheStorage = $this->cache->getStorage([
            'storage'   => $storage,
            'type'      => 'variable',
            'provider'  => $provider,
            // we (won't) store cache files under this
            'cachedir'  => $this->cacheDir,
            'expire'    => $this->cacheTime,
            'sizelimit' => $this->cacheSizeLimit,
            'logfile'   => $logfile,
        ]);
        if (empty($this->cacheStorage)) {
            return false;
        }

        return true;
    }

    public function getCacheKey($scope, $name)
    {
        if (empty($this->cacheStorage)) {
            return;
        }

        if (empty($scope) || empty($name)) {
            return;
        }

        // Check if this variable is suitable for caching
        if (!($this->checkCachingRules($scope, $name))) {
            return;
        }

        // CHECKME: use cacheCode and/or namespace instead ?
        // Answer: unlike for output caching, no external factors should influence this (user, theme, locale, url params, ...)
        //$this->cacheCode = md5($factors);
        //$this->cacheStorage->setCode($this->cacheCode);
        // CHECKME: what about variable scope and/or name that isn't OS compliant ?
        // Answer: this must be handled by the cacheStorage if necessary
        // cache storage typically only works with a single cache namespace, so we add our own scope prefix here
        // Note: the cacheStorage may add its own namespace internally to take into account the host, site, ...
        return $scope . ':' . $name;
    }

    public function getCacheSettings()
    {
        if (!isset($this->cacheSettings)) {
            // TODO: make things configurable in cachemanager
            // Load the caching configuration
            $config = $this->cache->getConfig();
            $settings = $config['Variable.CacheSettings'] ?? [];
            if (empty($settings)) {
                $settings = [];
                // CHECKME: get a list of potential scopes from xarCoreCache as examples?
                //$scopelist = xar::mem()->getCachedScopes();
                //foreach ($scopelist as $scope) {
                //    $settings[$scope] = 0;
                //}
                // CHECKME: we only cache some default scopes for now
                foreach ($this->cacheScopes as $scope => $value) {
                    $settings[$scope] = $value;
                }
            }
            $this->cacheSettings = $settings;
        }
        return $this->cacheSettings;
    }

    public function checkCachingRules($scope, $name)
    {
        $settings = $this->getCacheSettings();

        if (!empty($settings) && !empty($settings[$scope])) {
            // this variable scope is configured for caching
            // TODO: make things configurable in cachemanager
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

    public function isCached($cacheKey)
    {
        if (empty($this->cacheStorage)) {
            return false;
        }
        return $this->cacheStorage->isCached($cacheKey);
    }

    public function getCached($cacheKey)
    {
        if (empty($this->cacheStorage)) {
            return null;
        }
        // get the value from cache
        $value = $this->cacheStorage->getCached($cacheKey);
        // check if we serialized it for storage
        if (!empty($value) && is_string($value) && strpos($value, ':serial:') === 0) {
            try {
                $value = unserialize(substr($value, 8));
            } catch (Exception $e) {
            }
        }
        return $value;
    }

    public function setCached($cacheKey, $value, $expire = null)
    {
        if (empty($this->cacheStorage)) {
            return;
        }
        // serialize the value for storage if necessary
        if (!is_string($value) && !is_numeric($value)) {
            $value = ':serial:' . serialize($value);
        }
        // save the value to cache
        if (isset($expire)) {
            $this->cacheStorage->setCached($cacheKey, $value, $expire);
        } else {
            $this->cacheStorage->setCached($cacheKey, $value);
        }
    }

    public function delCached($cacheKey)
    {
        if (empty($this->cacheStorage)) {
            return;
        }
        // delete the value from cache
        $this->cacheStorage->delCached($cacheKey);
    }

    public function keyCached($cacheKey)
    {
        if (empty($this->cacheStorage)) {
            return null;
        }
        // get the key info from cache
        return $this->cacheStorage->keyInfo($cacheKey);
    }

    public function flushCached($scope)
    {
        if (empty($this->cacheStorage)) {
            return;
        }
        // CHECKME: not all cache storage supports this in the same way !
        $this->cacheStorage->flushCached($scope . ':');
    }
}
