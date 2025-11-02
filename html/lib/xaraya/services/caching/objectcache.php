<?php

/**
 * Object output caching
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
 * Object output caching
 */
class ObjectCache extends ServiceClass
{
    public const SLICE = 'caching.object';

    public int $cacheTime = 7200;
    public int $cacheSizeLimit = 2097152;
    /** @var ?array<mixed> */
    public $cacheMethods = null;
    public ?ixarCache_Storage $cacheStorage = null;

    /** @var ?array<mixed> */
    public $cacheSettings     = null;
    public ?string $cacheKey  = null;
    public ?string $cacheCode = null;

    public ?int $noCache      = null;
    public ?int $userShared   = null;
    public ?int $expireTime   = null;

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
        // --- LEGACY METHOD BODY ---
        $this->cacheTime = $config['Object.TimeExpiration'] ?? 7200;
        $this->cacheSizeLimit = $config['Object.SizeLimit'] ?? 2097152;
        $this->cacheMethods = $config['Object.CacheMethods'] ?? ['view' => 1, 'display' => 1];

        $storage = !empty($config['Object.CacheStorage'])
            ? $config['Object.CacheStorage'] : 'filesystem';
        $provider = !empty($config['Object.CacheProvider'])
            ? $config['Object.CacheProvider'] : null;
        $logfile = !empty($config['Object.LogFile'])
            ? $config['Object.LogFile'] : null;
        $this->cacheStorage = $this->cache->getStorage([
            'storage'   => $storage,
            'type'      => 'object',
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
        // --- END LEGACY METHOD BODY ---
    }

    public function getCacheKey($objectName, $methodName = 'view', $args = [])
    {
        // --- LEGACY METHOD BODY ---
        if (empty($this->cacheStorage)) {
            return null;
        }

        if (empty($objectName) || empty($methodName)) {
            return null;
        }

        // Check if this object method is suitable for object caching
        if (!($this->checkCachingRules($objectName, $methodName, $args))) {
            return null;
        }

        if (!empty($args['preview'])) {
            // we don't cache preview
            return false;
        }

        // we should be safe for caching now

        // set the current cacheKey
        $this->cacheKey = $objectName . '-' . $methodName . '-';

        // CHECKME: should we detect the param for the itemid here ?
        if (!empty($args['itemid'])) {
            $this->cacheKey .= $args['itemid'];
        }

        // set the cacheCode for the current cacheKey
        $xar = $this->getParent();

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

        // add the method args
        $factors .= serialize($args);

        $this->cacheCode = md5($factors);
        $this->cacheStorage->setCode($this->cacheCode);

        // return the cacheKey
        return $this->cacheKey;
        // --- END LEGACY METHOD BODY ---
    }

    public function getCacheSettings()
    {
        // --- LEGACY METHOD BODY ---
        if (!isset($this->cacheSettings)) {
            $xar = $this->getParent();
            $settings = [];
            $serialsettings = $xar->mod('dynamicdata')->getVar('objectcache_settings');
            if (!empty($serialsettings)) {
                $settings = unserialize($serialsettings);
            }
            $this->cacheSettings = $settings;
        }
        return $this->cacheSettings;
        // --- END LEGACY METHOD BODY ---
    }

    public function checkCachingRules($objectName, $methodName = 'view', $args = [])
    {
        // --- LEGACY METHOD BODY ---
        // we only cache the top-most object method in case of nested methods
        if (!empty($this->cacheKey)) {
            return false;
        }

        $this->noCache    = null;
        $this->userShared = null;
        $this->expireTime = null;

        // CHECKME: should we allow POST requests here ?

        $settings = $this->getCacheSettings();

        if (!empty($settings[$objectName]) && !empty($settings[$objectName][$methodName])) {
            $this->noCache    = $settings[$objectName][$methodName]['nocache'];
            $this->userShared = $settings[$objectName][$methodName]['usershared'];
            $this->expireTime = $settings[$objectName][$methodName]['cacheexpire'];
        } else {
            // this object method is not configured for caching
            return false;
        }

        if (!empty($this->noCache)) {
            // this object method is configured for nocache
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
        // --- END LEGACY METHOD BODY ---
    }

    public function isCached($cacheKey = null)
    {
        // --- LEGACY METHOD BODY ---
        if (empty($this->cacheStorage)) {
            return false;
        }

        // we only cache the top-most object method in case of nested methods
        if (empty($cacheKey) || $cacheKey != $this->cacheKey) {
            return false;
        }

        // Note: we pass along the expiration time here, because it may be different for each object
        $result = $this->cacheStorage->isCached($cacheKey, $this->expireTime);

        if (empty($result)) {
            // initialize the title, styles and script arrays for the current cacheKey
            $this->pageTitle = null;
            $this->styleList = [];
            $this->scriptList = [];
        }

        return $result;
        // --- END LEGACY METHOD BODY ---
    }

    public function getCached($cacheKey)
    {
        // --- LEGACY METHOD BODY ---
        if (empty($this->cacheStorage)) {
            return '';
        }

        // we only cache the top-most object method in case of nested methods
        if (empty($cacheKey) || $cacheKey != $this->cacheKey) {
            return 'cacheKey mismatch in ObjectCache::getCached - please submit a bug report with details of your configuration';
        }

        // Note: we pass along the expiration time here, because it may be different for each object
        $value = $this->cacheStorage->getCached($cacheKey, 0, $this->expireTime);

        // we're done with this cacheKey
        $this->cacheKey = null;

        $xar = $this->getParent();
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
        // --- END LEGACY METHOD BODY ---
    }

    public function setCached($cacheKey, $value)
    {
        // --- LEGACY METHOD BODY ---
        if (empty($this->cacheStorage)) {
            return;
        }

        // we only cache the top-most object method in case of nested methods
        if (empty($cacheKey) || $cacheKey != $this->cacheKey) {
            return;
        }

        $req = $this->getParent()->req();
        $tpl = $this->getParent()->tpl();

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
                $value = "<!-- start cache: object/" . $cacheKey . ' ' . $this->cacheCode . " -->\n"
                         . $value
                         . "<!-- end cache: object/" . $cacheKey . ' ' . $this->cacheCode . " -->\n";
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

            // Note: we pass along the expiration time here, because it may be different for each object
        }

        // we're done with this cacheKey
        $this->cacheKey = null;
        // --- END LEGACY METHOD BODY ---
    }

    public function flushCached($cacheKey)
    {
        // --- LEGACY METHOD BODY ---
        if (empty($this->cacheStorage)) {
            return;
        }

        $this->cacheStorage->flushCached($cacheKey);
        // --- END LEGACY METHOD BODY ---
    }

    public function setPageTitle($title = null, $module = null)
    {
        // --- LEGACY METHOD BODY ---
        if (empty($this->cacheKey)) {
            return;
        }
        $this->pageTitle = [$title, $module];
        // --- END LEGACY METHOD BODY ---
    }

    public function addStyle(array $args = [])
    {
        // --- LEGACY METHOD BODY ---
        if (empty($this->cacheKey)) {
            return;
        }
        $this->styleList[] = $args;
        // --- END LEGACY METHOD BODY ---
    }

    public function addJavaScript(array $args = [])
    {
        // --- LEGACY METHOD BODY ---
        if (empty($this->cacheKey)) {
            return;
        }
        $this->scriptList[] = $args;
        // --- END LEGACY METHOD BODY ---
    }

    public function addMeta(array $args = [])
    {
        // --- LEGACY METHOD BODY ---
        if (empty($this->cacheKey)) {
            return;
        }
        $this->metaList[] = $args;
        // --- END LEGACY METHOD BODY ---
    }
}
