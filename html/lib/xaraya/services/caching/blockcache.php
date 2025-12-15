<?php

/**
 * Block output caching
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
 * Block output caching
 */
class BlockCache extends ServiceClass
{
    public const SLICE = 'caching.block';

    public int $cacheTime = 7200;
    public int $cacheSizeLimit = 2097152;
    public ?ixarCache_Storage $cacheStorage = null;

    /** @var ?array<mixed> */
    public $cacheSettings     = null;
    public ?string $cacheKey  = null;
    public ?string $cacheCode = null;

    public ?int $noCache      = null;
    public ?int $pageShared   = null;
    public ?int $userShared   = null;
    public ?int $expireTime   = null;

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
        $this->cacheTime = $config['Block.TimeExpiration'] ?? 7200;
        $this->cacheSizeLimit = $config['Block.SizeLimit'] ?? 2097152;

        $storage = !empty($config['Block.CacheStorage'])
            ? $config['Block.CacheStorage'] : 'filesystem';
        $provider = !empty($config['Block.CacheProvider'])
            ? $config['Block.CacheProvider'] : null;
        $logfile = !empty($config['Block.LogFile'])
            ? $config['Block.LogFile'] : null;
        $this->cacheStorage = $this->cache->getStorage([
            'storage'   => $storage,
            'type'      => 'block',
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

    public function getCacheKey($blockInfo)
    {
        if (empty($this->cacheStorage)) {
            return null;
        }

        // Check if this block is suitable for block caching
        if (!($this->checkCachingRules($blockInfo))) {
            return null;
        }

        // we should be safe for caching now

        if (empty($blockInfo['name'])) {
            $blockInfo['name'] = '';
        }

        // set the current cacheKey
        $this->cacheKey = $blockInfo['module'] . '-' . $blockInfo['type'] . '-' . $blockInfo['name'];

        // set the cacheCode for the current cacheKey
        $xar = $this->getServicesClass();

        // the output depends on the current host, theme and locale
        $factors = $xar->req()->getHost() . $xar->tpl()->getThemeDir()
                . $xar->user()->getLocale();

        // add page identifier if needed (incl. path + query string)
        if ($this->pageShared == 0) {
            $factors .= $xar->req()->getRequestString();
        }

        // add group or user identifier if needed
        if ($this->userShared == 2) {
            $factors .= 0;
        } elseif ($this->userShared == 1) {
            $gidlist = $this->cache->getParents();
            $factors .= join(';', $gidlist);
        } else {
            $factors .= $xar->session()->getUserId();
        }

        // add block information
        $factors .= serialize($blockInfo);

        $this->cacheCode = md5($factors);
        $this->cacheStorage->setCode($this->cacheCode);

        // return the cacheKey
        return $this->cacheKey;
    }

    public function getCacheSettings()
    {
        return [];
    }

    public function checkCachingRules($blockInfo = [])
    {
        // we only cache the top-most block in case of nested blocks
        if (!empty($this->cacheKey)) {
            return false;
        }

        if (empty($blockInfo['type'])) {
            return false;
        }

        $this->noCache    = null;
        $this->pageShared = null;
        $this->userShared = null;
        $this->expireTime = null;

        /* As of soloblocks each block carries its own settings which we get from blockinfo */
        // $settings = $this->getCacheSettings();

        if (!empty($blockInfo['content']) && is_array($blockInfo['content'])) {
            if (isset($blockInfo['content']['nocache'])) {
                $this->noCache    = $blockInfo['content']['nocache'];
            }
            if (isset($blockInfo['content']['pageshared'])) {
                $this->pageShared = $blockInfo['content']['pageshared'];
            }
            if (isset($blockInfo['content']['usershared'])) {
                $this->userShared = $blockInfo['content']['usershared'];
            }
            if (isset($blockInfo['content']['cacheexpire'])) {
                $this->expireTime = $blockInfo['content']['cacheexpire'];
            }
        }

        if (empty($this->noCache)) {
            $this->noCache = 0;
        }
        if (empty($this->pageShared)) {
            $this->pageShared = 0;
        }
        if (empty($this->userShared)) {
            $this->userShared = 0;
        }
        if (!isset($this->expireTime)) {
            $this->expireTime = $this->cacheTime;
        }

        if (!empty($this->noCache)) {
            return false;
        }

        return true;
    }

    public function isCached($cacheKey)
    {
        if (empty($this->cacheStorage)) {
            return false;
        }

        // we only cache the top-most block in case of nested blocks
        if (empty($cacheKey) || $cacheKey != $this->cacheKey) {
            return false;
        }

        // Note: we pass along the expiration time here, because it may be different for each block
        $result = $this->cacheStorage->isCached($cacheKey, $this->expireTime);

        return $result;
    }

    public function getCached($cacheKey)
    {
        if (empty($this->cacheStorage)) {
            return '';
        }

        // we only cache the top-most block in case of nested blocks
        if (empty($cacheKey) || $cacheKey != $this->cacheKey) {
            return '';
        }

        // Note: we pass along the expiration time here, because it may be different for each block
        $value = $this->cacheStorage->getCached($cacheKey, 0, $this->expireTime);

        // we're done with this cacheKey
        $this->cacheKey = null;

        // empty blocks are acceptable here
        if (!empty($value) && $value === 'isEmptyBlock') {
            // the filesystem cache ignores empty files
            $value = '';
        }

        return $value;
    }

    public function setCached($cacheKey, $value)
    {
        if (empty($this->cacheStorage)) {
            return;
        }

        // we only cache the top-most block in case of nested blocks
        if (empty($cacheKey) || $cacheKey != $this->cacheKey) {
            return;
        }

        // empty blocks are acceptable here
        if (empty($value) && $value === '') {
            // the filesystem cache ignores empty files
            $value = 'isEmptyBlock';
        }

        $req = $this->getServicesClass()->req();

        if (// the http request is a GET AND
            $req->getMethod() == 'GET'
        // CHECKME: do we really want to check this again, or do we ignore it ?
            // the cache entry doesn't exist or has expired (no log here) AND
            && !($this->cacheStorage->isCached($cacheKey, $this->expireTime, 0))
            // the cache collection directory hasn't reached its size limit...
            && !($this->cacheStorage->sizeLimitReached())) {
            // Note: we pass along the expiration time here, because it may be different for each block
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
}
