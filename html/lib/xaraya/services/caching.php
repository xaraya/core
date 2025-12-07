<?php

/**
 * Caching available via methods (WIP)
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
use xarCache_Storage;
use sys;

/**
 * For documentation purposes only - available via CachingTrait
 */
interface CachingInterface extends ServiceInterface
{
    public const SLICE = 'caching';

    public function withOutput(): bool;

    public function withPages(): bool;

    /**
     * Get a cache key for page output caching
     * @param ?string $url optional url to be checked if not the current url
     * @return string|null cacheKey to be used with $this->cache()->(has|get|set)Page, or null if not applicable
     */
    public function getPageKey(?string $url = null): ?string;

    /**
     * Check if the content of a page is available in cache or not
     */
    public function hasPage(?string $cacheKey): bool;

    /**
     * Get the content of a cached page + output to browser
     */
    public function sendPage(?string $cacheKey): ?bool;

    /**
     * Get the content of a cached page
     */
    public function getPage(?string $cacheKey): string;

    /**
     * Set the content of a cached page
     */
    public function setPage(?string $cacheKey, string $value): void;

    /**
     * Flush page cache entries
     */
    public function flushPages(string $cacheScope): void;

    public function withModules(): bool;

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
    public function getModule(?string $cacheKey): string;

    /**
     * Set the output of the module function in cache
     */
    public function setModule(?string $cacheKey, string $value): void;

    /**
     * Flush module cache entries
     */
    public function flushModules(string $cacheScope): void;

    public function withBlocks(): bool;

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
    public function getBlock(?string $cacheKey): string;

    /**
     * Set the output of the block display in cache
     */
    public function setBlock(?string $cacheKey, string $value): void;

    /**
     * Flush block cache entries
     */
    public function flushBlocks(string $cacheScope): void;

    public function withObjects(): bool;

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
    public function getObject(?string $cacheKey): string;

    /**
     * Set the output of the object method in cache
     */
    public function setObject(?string $cacheKey, string $value): void;

    /**
     * Flush object cache entries
     */
    public function flushObjects(string $cacheScope): void;

    public function withVariables(): bool;

    /**
     * Get a cache key for variable instance caching
     * @return string|null cacheKey to be used with $this->cache()->(has|get|set)Variable, or null if not applicable
     */
    public function getVariableKey(string $scope, string $name): ?string;

    /**
     * Check if a variable value is cached
     */
    public function hasVariable(?string $cacheKey): bool;

    /**
     * Get the value of a cached variable
     */
    public function getVariable(?string $cacheKey): string|object;

    /**
     * Set the value of a cached variable
     */
    public function setVariable(?string $cacheKey, string|object $value): void;

    /**
     * Delete a cached variable
     */
    public function delVariable(?string $cacheKey): void;

    /**
     * Get information about a cache key
     * @return array<string, mixed>
     */
    public function keyVariable(?string $cacheKey): array;

    /**
     * Flush a particular cache scope
     */
    public function flushVariables(string $cacheScope): void;

    /**
     * Disable caching of the current output, e.g. when an authid is generated or if we redirect
     */
    public function noCache(): void;

    /**
     * Keep track of some page title for caching - see xar::tpl()->setPageTitle()
     */
    public function setPageTitle(?string $title = null, ?string $module = null): void;

    /**
     * Keep track of some stylesheet for caching - see xar::mod()->apiFunc('themes','user','register')
     * @param array<string, mixed> $args
     */
    public function addStyle(array $args = []): void;

    /**
     * Keep track of some javascript for caching - xar::mod()->apiFunc('themes','user','registerjs')
     * @param array<string, mixed> $args
     */
    public function addJavascript(array $args = []): void;

    /**
     * Keep track of some meta tags for caching - xar::mod()->apiFunc('themes','user','registermeta')
     * @param array<string, mixed> $args
     */
    public function addMeta(array $args = []): void;

    /**
     * Get a storage class instance for some type of cached data
     * @param array<string, mixed> $args
     */
    public function getStorage(array $args = []): ixarCache_Storage;

    /**
     * Get the parent group ids of the current user (with minimal overhead)
     * @return array<mixed> of parent gids
     */
    public function getParents(?int $currentid = null): array;

    /**
     * Get the output cache directory to access stats and items in cache storage even
     * if output caching is disabled (cfr. cachemanager admin stats/view/flushcache)
     */
    public function getOutputCacheDir(): string;
}

/**
 * Caching available via methods
 */
trait CachingTrait
{
    use ServiceTrait;

    public string $cacheDir              = '';
    public bool $outputCacheIsEnabled    = false;
    public bool $coreCacheIsEnabled      = true;
    public bool $templateCacheIsEnabled  = true; // currently unused, cfr. xaraya/templates.php
    public bool $variableCacheIsEnabled  = false;
    //public bool $queryCacheIsEnabled     = false;
    public ?Caching\OutputCache $outputCache = null;
    public ?Caching\PageCache $pageCache = null;
    public ?Caching\ModuleCache $moduleCache = null;
    public ?Caching\BlockCache $blockCache = null;
    public ?Caching\ObjectCache $objectCache = null;
    public ?Caching\VariableCache $variableCache = null;
    protected bool $initialized = false;

    /**
     * Initialize service class - delay until we need results
     * @param array<string, mixed> $config
     */
    public function init(array $config = []): bool
    {
        if (empty($config) && $this->initialized) {
            return true;
        }
        $cacheDir = $config['cacheDir'] ?? '';
        if (empty($cacheDir) || !is_dir($cacheDir)) {
            $cacheDir = sys::varpath() . '/cache';
        }
        $this->cacheDir = $cacheDir;

        // Load the caching configuration
        $config = $this->getConfig();

        // Enable output caching
        if (file_exists($this->cacheDir . '/output/cache.touch')) {
            if (!empty($config)) {
                // initialize the output cache
                $this->outputCache = new Caching\OutputCache($this->getParent());
                $this->outputCacheIsEnabled = $this->outputCache->init($config);
                // Note : we may already exit here if session-less page caching is enabled
            } else {
                // if the config file is missing or empty, turn off output caching
                @unlink($this->cacheDir . '/output/cache.touch');
            }
        }

        // Enable core caching in memory
        // @todo verify with StaticServicesClass::__construct()
        $this->coreCacheIsEnabled = $this->getParent()->mem()->init($config);

        // Enable template caching ? Too early in the process here, cfr. xaraya/templates.php

        // Enable variable caching (requires activating autoload for serialized objects et al.)
        if (!empty($config['Variable.CacheIsEnabled'])) {
            $this->variableCache = new Caching\VariableCache($this->getParent());
            $this->variableCacheIsEnabled = $this->variableCache->init($config);
        }
        $this->initialized = true;
        return true;
    }

    /**
     * Get configuration
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        // load the caching configuration
        $cachingConfiguration = [];
        if (file_exists($this->cacheDir . '/config.caching.php')) {
            @include($this->cacheDir . '/config.caching.php');
        }
        return $cachingConfiguration;
    }

    public function isLoaded(): bool
    {
        return $this->initialized;
    }

    public function withOutput(): bool
    {
        return empty($this->outputCache) ? false : true;
    }

    public function withPages(): bool
    {
        return empty($this->pageCache) ? false : true;
    }

    /**
     * Get a cache key for page output caching
     * @param ?string $url optional url to be checked if not the current url
     * @return string|null cacheKey to be used with $this->cache()->(has|get|set)Page, or null if not applicable
     */
    public function getPageKey(?string $url = null): ?string
    {
        if (empty($this->pageCache)) {
            return null;
        }
        return $this->pageCache->getCacheKey($url);
    }

    /**
     * Check if the content of a page is available in cache or not
     */
    public function hasPage(?string $cacheKey): bool
    {
        if (empty($cacheKey)) {
            return false;
        }
        if (empty($this->pageCache)) {
            return false;
        }
        return $this->pageCache->isCached($cacheKey);
    }

    /**
     * Get the content of a cached page + output to browser
     */
    public function sendPage(?string $cacheKey): ?bool
    {
        if (empty($cacheKey)) {
            return false;
        }
        if (empty($this->pageCache)) {
            return null;
        }
        $output = 1;
        return $this->pageCache->getCached($cacheKey, $output);
    }

    /**
     * Get the content of a cached page
     */
    public function getPage(?string $cacheKey): string
    {
        if (empty($cacheKey)) {
            return '';
        }
        if (empty($this->pageCache)) {
            return '';
        }
        $output = 0;
        return $this->pageCache->getCached($cacheKey, $output);
    }

    /**
     * Set the content of a cached page
     */
    public function setPage(?string $cacheKey, string $value): void
    {
        if (empty($cacheKey)) {
            return;
        }
        if (empty($this->pageCache)) {
            return;
        }
        $this->pageCache->setCached($cacheKey, $value);
    }

    /**
     * Flush page cache entries
     */
    public function flushPages(string $cacheScope): void
    {
        if (empty($this->pageCache)) {
            return;
        }
        $this->pageCache->flushCached($cacheScope);
    }

    public function withModules(): bool
    {
        return empty($this->moduleCache) ? false : true;
    }

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
        if (empty($this->moduleCache)) {
            return null;
        }
        return $this->moduleCache->getCacheKey($modName, $modType, $funcName, $args);
    }

    /**
     * Check if the output of a module function is cached
     */
    public function hasModule(?string $cacheKey): bool
    {
        if (empty($cacheKey)) {
            return false;
        }
        if (empty($this->moduleCache)) {
            return false;
        }
        return $this->moduleCache->isCached($cacheKey);
    }

    /**
     * Get the output of the module function from cache
     */
    public function getModule(?string $cacheKey): string
    {
        if (empty($cacheKey)) {
            return '';
        }
        if (empty($this->moduleCache)) {
            return '';
        }
        return $this->moduleCache->getCached($cacheKey);
    }

    /**
     * Set the output of the module function in cache
     */
    public function setModule(?string $cacheKey, string $value): void
    {
        if (empty($cacheKey)) {
            return;
        }
        if (empty($this->moduleCache)) {
            return;
        }
        $this->moduleCache->setCached($cacheKey, $value);
    }

    /**
     * Flush module cache entries
     */
    public function flushModules(string $cacheScope): void
    {
        if (empty($this->moduleCache)) {
            return;
        }
        $this->moduleCache->flushCached($cacheScope);
    }

    public function withBlocks(): bool
    {
        return empty($this->blockCache) ? false : true;
    }

    /**
     * Get a cache key for block output caching
     * @param array<string, mixed> $blockInfo block information
     * @return string|null cacheKey to be used with $this->cache()->(has|get|set)Block, or null if not applicable
     */
    public function getBlockKey(array $blockInfo = []): ?string
    {
        if (empty($this->blockCache)) {
            return null;
        }
        return $this->blockCache->getCacheKey($blockInfo);
    }

    /**
     * Check if the output of a block display is cached
     */
    public function hasBlock(?string $cacheKey): bool
    {
        if (empty($cacheKey)) {
            return false;
        }
        if (empty($this->blockCache)) {
            return false;
        }
        return $this->blockCache->isCached($cacheKey);
    }

    /**
     * Get the output of the block display from cache
     */
    public function getBlock(?string $cacheKey): string
    {
        if (empty($cacheKey)) {
            return '';
        }
        if (empty($this->blockCache)) {
            return '';
        }
        return $this->blockCache->getCached($cacheKey);
    }

    /**
     * Set the output of the block display in cache
     */
    public function setBlock(?string $cacheKey, string $value): void
    {
        if (empty($cacheKey)) {
            return;
        }
        if (empty($this->blockCache)) {
            return;
        }
        $this->blockCache->setCached($cacheKey, $value);
    }

    /**
     * Flush block cache entries
     */
    public function flushBlocks(string $cacheScope): void
    {
        if (empty($this->blockCache)) {
            return;
        }
        $this->blockCache->flushCached($cacheScope);
    }

    public function withObjects(): bool
    {
        return empty($this->objectCache) ? false : true;
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
        if (empty($this->objectCache)) {
            return null;
        }
        return $this->objectCache->getCacheKey($objectName, $methodName, $args);
    }

    /**
     * Check if the output of an object method is cached
     */
    public function hasObject(?string $cacheKey): bool
    {
        if (empty($cacheKey)) {
            return false;
        }
        if (empty($this->objectCache)) {
            return false;
        }
        return $this->objectCache->isCached($cacheKey);
    }

    /**
     * Get the output of the object method from cache
     */
    public function getObject(?string $cacheKey): string
    {
        if (empty($cacheKey)) {
            return '';
        }
        if (empty($this->objectCache)) {
            return '';
        }
        return $this->objectCache->getCached($cacheKey);
    }

    /**
     * Set the output of the object method in cache
     */
    public function setObject(?string $cacheKey, string $value): void
    {
        if (empty($cacheKey)) {
            return;
        }
        if (empty($this->objectCache)) {
            return;
        }
        $this->objectCache->setCached($cacheKey, $value);
    }

    /**
     * Flush object cache entries
     */
    public function flushObjects(string $cacheScope): void
    {
        if (empty($this->objectCache)) {
            return;
        }
        $this->objectCache->flushCached($cacheScope);
    }

    public function withVariables(): bool
    {
        return empty($this->variableCache) ? false : true;
    }

    /**
     * Get a cache key for variable value caching
     * @return string|null cacheKey to be used with $this->cache()->(has|get|set)Variable, or null if not applicable
     */
    public function getVariableKey(string $scope, string $name): ?string
    {
        /**
        if ($this->isVariableCacheEnabled()) {
            return $this->variableCache->getCacheKey($scope, $name);
        }
        return null;
         */
        if (empty($this->variableCache)) {
            return null;
        }
        return $this->variableCache->getCacheKey($scope, $name);
    }

    /**
     * Check if a variable value is cached
     */
    public function hasVariable(?string $cacheKey): bool
    {
        if (empty($cacheKey)) {
            return false;
        }
        if (empty($this->variableCache)) {
            return false;
        }
        return $this->variableCache->isCached($cacheKey);
    }

    /**
     * Get the value of a cached variable
     */
    public function getVariable(?string $cacheKey): string|object
    {
        if (empty($cacheKey)) {
            return '';
        }
        if (empty($this->variableCache)) {
            return '';
        }
        return $this->variableCache->getCached($cacheKey);
    }

    /**
     * Set the value of a cached variable
     */
    public function setVariable(?string $cacheKey, string|object $value, ?int $expire = null): void
    {
        if (empty($cacheKey)) {
            return;
        }
        if (empty($this->variableCache)) {
            return;
        }
        $this->variableCache->setCached($cacheKey, $value, $expire);
    }

    /**
     * Delete a cached variable
     */
    public function delVariable(?string $cacheKey): void
    {
        if (empty($cacheKey)) {
            return;
        }
        if (empty($this->variableCache)) {
            return;
        }
        $this->variableCache->delCached($cacheKey);
    }

    /**
     * Get information about a cache key
     * @return array<string, mixed>
     */
    public function keyVariable(?string $cacheKey): array
    {
        if (empty($cacheKey)) {
            return [];
        }
        if (empty($this->variableCache)) {
            return [];
        }
        return $this->variableCache->keyCached($cacheKey);
    }

    /**
     * Flush a particular cache scope
     */
    public function flushVariables(string $cacheScope): void
    {
        if (empty($this->variableCache)) {
            return;
        }
        $this->variableCache->flushCached($cacheScope);
    }

    /**
     * Disable caching of the current output, e.g. when an authid is generated or if we redirect
     */
    public function noCache(): void
    {
        if (empty($this->outputCache)) {
            return;
        }
        if (!empty($this->pageCache)) {
            // set the current cacheKey to null
            $this->pageCache->cacheKey = null;
            $this->getParent()->mem()->set('Page.Caching', 'nocache', true);
        }
        if (!empty($this->blockCache)) {
            // set the current cacheKey to null
            $this->blockCache->cacheKey = null;
        }
        if (!empty($this->moduleCache)) {
            // set the current cacheKey to null
            $this->moduleCache->cacheKey = null;
        }
        if (!empty($this->objectCache)) {
            // set the current cacheKey to null
            $this->objectCache->cacheKey = null;
        }
    }

    /**
     * Keep track of some page title for caching - see xar::tpl()->setPageTitle()
     */
    public function setPageTitle(?string $title = null, ?string $module = null): void
    {
        if (empty($this->outputCache)) {
            return;
        }
        // TODO: refactor common code ?
        if (!empty($this->moduleCache)) {
            // set page title for module output
            $this->moduleCache->setPageTitle($title, $module);
        }
        if (!empty($this->objectCache)) {
            // set page title for object output
            $this->objectCache->setPageTitle($title, $module);
        }
    }

    /**
     * Keep track of some stylesheet for caching - see xar::mod()->apiFunc('themes','user','register')
     * @param array<string, mixed> $args
     */
    public function addStyle(array $args = []): void
    {
        if (empty($this->outputCache)) {
            return;
        }
        // TODO: refactor common code ?
        if (!empty($this->moduleCache)) {
            // add stylesheet for module output
            $this->moduleCache->addStyle($args);
        }
        if (!empty($this->objectCache)) {
            // add stylesheet for object output
            $this->objectCache->addStyle($args);
        }
    }

    /**
     * Keep track of some javascript for caching - xar::mod()->apiFunc('themes','user','registerjs')
     * @param array<string, mixed> $args
     */
    public function addJavascript(array $args = []): void
    {
        if (empty($this->outputCache)) {
            return;
        }
        // TODO: refactor common code ?
        if (!empty($this->moduleCache)) {
            // add javascript for module output
            $this->moduleCache->addJavaScript($args);
        }
        if (!empty($this->objectCache)) {
            // add javascript for object output
            $this->objectCache->addJavaScript($args);
        }
    }

    /**
     * Keep track of some meta tags for caching - xar::mod()->apiFunc('themes','user','registermeta')
     * @param array<string, mixed> $args
     */
    public function addMeta(array $args = []): void
    {
        if (empty($this->outputCache)) {
            return;
        }
        // TODO: refactor common code ?
        if (!empty($this->moduleCache)) {
            // add javascript for module output
            $this->moduleCache->addMeta($args);
        }
        if (!empty($this->objectCache)) {
            // add javascript for object output
            $this->objectCache->addMeta($args);
        }
    }

    /**
     * Get a storage class instance for some type of cached data
     * @param array<string, mixed> $args
     */
    public function getStorage(array $args = []): ixarCache_Storage
    {
        return xarCache_Storage::getCacheStorage($args, $this->getParent());
    }

    /**
     * Get the parent group ids of the current user (with minimal overhead)
     * @return array<mixed> of parent gids
     */
    public function getParents(?int $currentid = null): array
    {
        $mem = $this->getParent()->mem();
        if (empty($currentid)) {
            $currentid = $this->getParent()->session()->getUserId();
        }
        if ($mem->has('User.Variables.' . $currentid, 'parentlist')) {
            return $mem->get('User.Variables.' . $currentid, 'parentlist');
        }
        $gidlist = [];
        // load Database Service on demand here for caching
        try {
            // @todo do we want to call xar::db()->init() here first?
            $xarDB = $this->getParent()->db();
        } catch (\Throwable $e) {
            error_log('Unable to load database service in xarCache: ' . $e->getMessage());
            $mem->set('User.Variables.' . $currentid, 'parentlist', $gidlist);
            return $gidlist;
        }
        $rolemembers = $xarDB->getPrefix() . '_rolemembers';
        $dbconn = $xarDB->getConn();
        $query = "SELECT parent_id FROM $rolemembers WHERE role_id = ?";
        $stmt   = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery([$currentid]);

        while ($result->next()) {
            $gidlist[] = $result->getInt(1);
        }
        $result->close();
        $mem->set('User.Variables.' . $currentid, 'parentlist', $gidlist);
        return $gidlist;
    }

    /**
     * Get the output cache directory to access stats and items in cache storage even
     * if output caching is disabled (cfr. cachemanager admin stats/view/flushcache)
     */
    public function getOutputCacheDir(): string
    {
        // make sure OutputCache is initialized
        if (!$this->outputCacheIsEnabled) {
            // get the caching configuration
            $config = $this->getConfig();
            // initialize the output cache
            $this->outputCache = new Caching\OutputCache($this->getParent());
            //$this->outputCacheIsEnabled = $this->outputCache->init($config);
            $this->outputCache->init($config);
            // reset after output cache init()
            $this->outputCacheIsEnabled = false;
            // make sure we don't cache here
            $this->noCache();
        }
        return $this->outputCache->getCacheDir();
    }

    /**
     * @deprecated 2.8.4 use $this->cache()->withOutput() etc.
     */
    public function isOutputCacheEnabled()
    {
        return $this->outputCacheIsEnabled;
    }

    /**
     * @deprecated 2.8.4 not used
     */
    public function isCoreCacheEnabled()
    {
        return $this->coreCacheIsEnabled;
    }

    /**
     * @deprecated 2.8.4 not used
     */
    public function isTemplateCacheEnabled()
    {
        return $this->templateCacheIsEnabled;
    }

    /**
     * @deprecated 2.8.4 use $this->cache()->withVariables()
     */
    public function isVariableCacheEnabled()
    {
        return $this->variableCacheIsEnabled;
    }
}

/**
 * Access xar*Cache::* Caching methods (getModuleKey, getObjectKey, ...)
 *
 * Available methods:
 * - getPageKey()
 * - hasPage()
 * - sendPage() - output to browser
 * - getPage()
 * - setPage()
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
        // $config = $this->getConfig();
    }
}
