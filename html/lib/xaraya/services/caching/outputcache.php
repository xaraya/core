<?php

/**
 * Output caching
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

/**
 * Output caching
 */
class OutputCache extends ServiceClass
{
    public const SLICE = 'caching.output';

    public string $cacheDir           = 'var/cache/output';
    public string $cacheTheme         = '';
    public int $cacheSizeLimit        = 2097152;
    public string $cacheCookie        = 'XARAYASID';
    public string $cacheLocale        = 'en_US.utf-8';

    public bool $pageCacheIsEnabled   = false;
    public bool $blockCacheIsEnabled  = false;
    public bool $moduleCacheIsEnabled = false;
    public bool $objectCacheIsEnabled = false;

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
        if (empty($config)) {
            return false;
        }
        // avoid loops when trying to load caches inside outputcache
        $this->cache->outputCacheIsEnabled = true;

        // specify the output cache directory
        if (empty($config['Output.CacheDir']) || !is_dir($config['Output.CacheDir'])) {
            $config['Output.CacheDir'] = $this->cache->cacheDir . '/output';
        }
        $this->cacheDir       = realpath($config['Output.CacheDir']);
        $this->cacheTheme     = $config['Output.DefaultTheme'] ?? '';
        $this->cacheSizeLimit = $config['Output.SizeLimit'] ?? 2097152;
        $this->cacheCookie    = $config['Output.CookieName'] ?? 'XARAYASID';
        $this->cacheLocale    = $config['Output.DefaultLocale'] ?? 'en_US.utf-8';

        if (file_exists($this->cacheDir . '/cache.pagelevel')) {
            // Note : we may already exit here if session-less page caching is enabled
            $this->cache->pageCache = new PageCache($this->getParent());
            $this->pageCacheIsEnabled = $this->cache->pageCache->init($config);
        }

        if (file_exists($this->cacheDir . '/cache.blocklevel')) {
            $this->cache->blockCache = new BlockCache($this->getParent());
            $this->blockCacheIsEnabled = $this->cache->blockCache->init($config);
        }

        if (file_exists($this->cacheDir . '/cache.modulelevel')) {
            $this->cache->moduleCache = new ModuleCache($this->getParent());
            $this->moduleCacheIsEnabled = $this->cache->moduleCache->init($config);
        }

        if (file_exists($this->cacheDir . '/cache.objectlevel')) {
            $this->cache->objectCache = new ObjectCache($this->getParent());
            $this->objectCacheIsEnabled = $this->cache->objectCache->init($config);
        }

        return true;
    }

    public function getCacheDir()
    {
        return $this->cacheDir;
    }

    /**
     * @deprecated 2.8.4 use $this->cache()->withPages() etc.
     */
    public function isPageCacheEnabled()
    {
        return $this->pageCacheIsEnabled;
    }

    /**
     * @deprecated 2.8.4 use $this->cache()->withBlocks() etc.
     */
    public function isBlockCacheEnabled()
    {
        return $this->blockCacheIsEnabled;
    }

    /**
     * @deprecated 2.8.4 use $this->cache()->withModules() etc.
     */
    public function isModuleCacheEnabled()
    {
        return $this->moduleCacheIsEnabled;
    }

    /**
     * @deprecated 2.8.4 use $this->cache()->withObjects() etc.
     */
    public function isObjectCacheEnabled()
    {
        return $this->objectCacheIsEnabled;
    }
}
