<?php

/**
 * Module output caching
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

/**
 * Module output caching
 */
class ModuleCache extends ServiceClass
{
    public const SLICE = 'caching.module';

    public int $cacheTime = 7200;
    public int $cacheSizeLimit = 2097152;
    /** @var ?array<mixed> */
    public $cacheFunctions = null;
    public ?ixarCache_Storage $cacheStorage = null;

    /** @var ?array<mixed> */
    public $cacheSettings     = null;
    public ?string $cacheKey  = null;
    public ?string $cacheCode = null;

    public ?int $noCache      = null;
    public ?int $userShared   = null;
    public ?int $expireTime   = null;
    public string $funcParams = '';

    /** @var ?array<mixed> */
    public $pageTitle         = [];
    /** @var array<mixed> */
    public $styleList         = [];
    /** @var array<mixed> */
    public $scriptList        = [];
    /** @var array<mixed> */
    public $metaList          = [];

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
        $this->cacheTime = $config['Module.TimeExpiration'] ?? 7200;
        $this->cacheSizeLimit = $config['Module.SizeLimit'] ?? 2097152;
        $this->cacheFunctions = $config['Module.CacheFunctions'] ?? [
            'main' => 1,
            'view' => 1,
            'display' => 0,
        ];

        $storage = !empty($config['Module.CacheStorage'])
            ? $config['Module.CacheStorage'] : 'filesystem';
        $provider = !empty($config['Module.CacheProvider'])
            ? $config['Module.CacheProvider'] : null;
        $logfile = !empty($config['Module.LogFile'])
            ? $config['Module.LogFile'] : null;
        $this->cacheStorage = $this->cache->getStorage([
            'storage'   => $storage,
            'type'      => 'module',
            'provider'  => $provider,
            // we store output cache files under this
            'cachedir'  => $this->cache->getOutputCacheDir(),
            'expire'    => $this->cacheTime,
            'sizelimit' => $this->cacheSizeLimit,
            'logfile'   => $logfile,
        ]);
        if (empty($this->cacheStorage)) {
            return false;
        }

        return true;
    }

    public function getCacheKey($modName, $modType = 'user', $funcName = 'main', $args = [])
    {
        if (empty($this->cacheStorage)) {
            return null;
        }

        if (empty($modName) || empty($funcName)) {
            return null;
        }

        // Check if this module function is suitable for module caching
        if (!($this->checkCachingRules($modName, $modType, $funcName, $args))) {
            return null;
        }

        // Check the specified function params
        if (empty($this->funcParams)) {
            $params = [];
        } else {
            $params = explode(',', $this->funcParams);
        }
        // add missing function params to $args
        $var = $this->getServicesClass()->var();
        foreach ($params as $param) {
            if (!isset($args[$param])) {
                $var->find($param, $args[$param]);
            }
        }

        if (!empty($args['preview'])) {
            // we don't cache preview
            return false;
        }

        // we should be safe for caching now

        // set the current cacheKey
        $this->cacheKey = $modName . '-' . $funcName . '-';

        // CHECKME: should we detect the param for the itemid here ?
        if (!empty($args['itemid'])) {
            $this->cacheKey .= $args['itemid'];
        }

        // set the cacheCode for the current cacheKey
        $xar = $this->getServicesClass();

        // the output depends on the current host, theme and locale
        $factors = $xar->req()->getHost() . $xar->tpl()->getThemeDir()
                . $xar->user()->getLocale();

        // add group or user identifier if needed
        if ($this->userShared == 2) {
            $factors .= 0;
        } elseif ($this->userShared == 1) {
            $gidlist = $this->cache->getParents();
            $factors .= join(';', $gidlist);
        } else {
            $factors .= $xar->session()->getUserId();
        }

        // add the function params
        $factors .= serialize($args);

        $this->cacheCode = md5($factors);
        $this->cacheStorage->setCode($this->cacheCode);

        // return the cacheKey
        return $this->cacheKey;
    }

    public function getCacheSettings()
    {
        if (!isset($this->cacheSettings)) {
            $xar = $this->getServicesClass();
            $settings = [];
            $serialsettings = $xar->mod('modules')->getVar('modulecache_settings');
            if (!empty($serialsettings)) {
                $settings = unserialize($serialsettings);
            }
            $this->cacheSettings = $settings;
        }
        return $this->cacheSettings;
    }

    public function checkCachingRules($modName, $modType = 'user', $funcName = 'main', $args = [])
    {
        // we only cache the top-most module function in case of nested functions
        if (!empty($this->cacheKey)) {
            return false;
        }

        // we only support user functions here
        if ($modType != 'user') {
            return false;
        }

        $this->noCache    = null;
        $this->userShared = null;
        $this->expireTime = null;
        $this->funcParams = '';

        // CHECKME: should we allow POST requests here ?

        $settings = $this->getCacheSettings();

        if (!empty($settings[$modName]) && !empty($settings[$modName][$funcName])) {
            $this->noCache    = $settings[$modName][$funcName]['nocache'];
            $this->userShared = $settings[$modName][$funcName]['usershared'];
            $this->expireTime = $settings[$modName][$funcName]['cacheexpire'];
            $this->funcParams = $settings[$modName][$funcName]['params'];
        } else {
            // this module function is not configured for caching
            return false;
        }

        if (!empty($this->noCache)) {
            // this module function is configured for nocache
            return false;
        } else {
            $this->noCache = 0;
        }
        if (empty($this->userShared)) {
            $this->userShared = 0;
        }
        if (!isset($this->expireTime)) {
            $this->expireTime = $this->cacheTime;
        }

        return true;
    }

    public function isCached($cacheKey = null)
    {
        if (empty($this->cacheStorage)) {
            return false;
        }

        // we only cache the top-most module function in case of nested functions
        if (empty($cacheKey) || $cacheKey != $this->cacheKey) {
            return false;
        }

        // Note: we pass along the expiration time here, because it may be different for each module
        $result = $this->cacheStorage->isCached($cacheKey, $this->expireTime);

        if (empty($result)) {
            // initialize the title, styles and script arrays for the current cacheKey
            $this->pageTitle = null;
            $this->styleList = [];
            $this->scriptList = [];
        }

        return $result;
    }

    public function getCached($cacheKey)
    {
        if (empty($this->cacheStorage)) {
            return '';
        }

        // we only cache the top-most module function in case of nested functions
        if (empty($cacheKey) || $cacheKey != $this->cacheKey) {
            return 'cacheKey mismatch in ModuleCache::getCached - please submit a bug report with details of your configuration';
        }

        // Note: we pass along the expiration time here, because it may be different for each module
        $value = $this->cacheStorage->getCached($cacheKey, 0, $this->expireTime);

        // we're done with this cacheKey
        $this->cacheKey = null;

        $xar = $this->getServicesClass();
        $content = unserialize((string) $value);
        if (!empty($content['title']) && is_array($content['title'])) {
            $xar->tpl()->setPageTitle($content['title'][0], $content['title'][1]);
        }
        if (!empty($content['styles']) && is_array($content['styles'])) {
            foreach ($content['styles'] as $info) {
                $xar->mod()->apiFunc('themes', 'user', 'register', $info);
            }
        }
        if (!empty($content['script']) && is_array($content['script'])) {
            foreach ($content['script'] as $info) {
                $xar->mod()->apiFunc('themes', 'user', 'registerjs', $info);
            }
        }
        if (!empty($content['meta']) && is_array($content['meta'])) {
            foreach ($content['meta'] as $info) {
                $xar->mod()->apiFunc('themes', 'user', 'registermeta', $info);
            }
        }
        return $content['output'];
    }

    public function setCached($cacheKey, $value)
    {
        if (empty($this->cacheStorage)) {
            return;
        }

        // we only cache the top-most module function in case of nested functions
        if (empty($cacheKey) || $cacheKey != $this->cacheKey) {
            return;
        }

        $req = $this->getServicesClass()->req();
        $tpl = $this->getServicesClass()->tpl();

        if (// the http request is a GET AND
            $req->getMethod() == 'GET'
        // CHECKME: do we really want to check this again, or do we ignore it ?
            // the cache entry doesn't exist or has expired (no log here) AND
            && !($this->cacheStorage->isCached($cacheKey, $this->expireTime, 0))
            // the cache collection directory hasn't reached its size limit...
            && !($this->cacheStorage->sizeLimitReached())) {
            // CHECKME: add cacheKey cacheCode in comments if template filenames are already added
            if ($tpl->outputTemplateFilenames()) {
                // separate with space here - we must avoid issues with double -- !?
                $value = "<!-- start cache: module/" . $cacheKey . ' ' . $this->cacheCode . " -->\n"
                         . $value
                         . "<!-- end cache: module/" . $cacheKey . ' ' . $this->cacheCode . " -->\n";
            }

            $content = [
                'output' => $value,
                'link'   => $req->getURL(),
                'title'  => $this->pageTitle,
                'styles' => $this->styleList,
                'script' => $this->scriptList,
                'meta'   => $this->metaList,
            ];
            $value = serialize($content);

            // Note: we pass along the expiration time here, because it may be different for each module
            $this->cacheStorage->setCached($cacheKey, $value, $this->expireTime);
        }

        // we're done with this cacheKey
        $this->cacheKey = null;
    }

    public function flushCached($cacheKey)
    {
        if (empty($this->cacheStorage)) {
            return;
        }

        $this->cacheStorage->flushCached($cacheKey);
    }

    public function setPageTitle($title = null, $module = null)
    {
        if (empty($this->cacheKey)) {
            return;
        }
        $this->pageTitle = [$title, $module];
    }

    public function addStyle(array $args = [])
    {
        if (empty($this->cacheKey)) {
            return;
        }
        $this->styleList[] = $args;
    }

    public function addJavaScript(array $args = [])
    {
        if (empty($this->cacheKey)) {
            return;
        }
        $this->scriptList[] = $args;
    }

    public function addMeta(array $args = [])
    {
        if (empty($this->cacheKey)) {
            return;
        }
        $this->metaList[] = $args;
    }
}
