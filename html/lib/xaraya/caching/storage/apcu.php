<?php
/**
 * @package core\caching
 * @subpackage caching
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

/**
 * Cache data using APC [http://pecl.php.net/apc/]
 */

sys::import('xaraya.caching.storage');
class xarCache_APCu_Storage extends xarCache_Storage implements ixarCache_Storage
{
    public ?string $lastkey = null;
    public mixed $value = null;

    public function __construct(array $args = [])
    {
        parent::__construct($args);
        $this->storage = 'apcu';
    }

    public function isCached($key = '', $expire = 0, $log = 1)
    {
        if (empty($expire)) {
            $expire = $this->expire;
        }
        if ($key == $this->lastkey && isset($this->value)) {
            return true;
        }
        $cache_key = $this->getCacheKey($key);
        // we actually retrieve the value here too - returns FALSE on failure
        $value = apcu_fetch($cache_key);
        if (isset($value) && $value !== false) {
            // FIXME: APC doesn't keep track of modification times !
            //$this->modtime = 0;
            if ($log) {
                $this->logStatus('HIT', $key);
            }
            $this->lastkey = $key;
            $this->value = $value;
            return true;
        } else {
            if ($log) {
                $this->logStatus('MISS', $key);
            }
            return false;
        }
    }

    public function getCached($key = '', $output = 0, $expire = 0)
    {
        if (empty($expire)) {
            $expire = $this->expire;
        }
        if ($key == $this->lastkey && isset($this->value)) {
            $this->lastkey = null;
            return $this->value;
        }
        $cache_key = $this->getCacheKey($key);
        $value = apcu_fetch($cache_key);
        if ($output) {
            // output the value directly to the browser
            echo $value;
            return true;
        } else {
            return $value;
        }
    }

    public function setCached($key = '', $value = '', $expire = 0)
    {
        if (empty($expire)) {
            $expire = $this->expire;
        }
        $cache_key = $this->getCacheKey($key);
        if (!empty($expire)) {
            apcu_store($cache_key, $value, $expire);
        } else {
            apcu_store($cache_key, $value);
        }
        $this->modtime = time();
        $this->lastkey = null;
    }

    public function delCached($key = '')
    {
        $cache_key = $this->getCacheKey($key);
        apcu_delete($cache_key);
        $this->lastkey = null;
    }

    /**
     * Get detailed information about the cache key (not supported by all storage)
     */
    public function keyInfo($key = '')
    {
        $cache_key = $this->getCacheKey($key);
        // filter out the keys that don't start with the right type/namespace prefix
        if (!empty($this->prefix) && strpos($cache_key, $this->prefix) !== 0) {
            return [];
        }
        // CHECKME: this assumes the code is always hashed
        if (preg_match('/^(.*)-(\w*)$/', $cache_key, $matches)) {
            $key = $matches[1];
            $code = $matches[2];
        } else {
            $key = $cache_key;
            $code = '';
        }
        // remove the prefix from the key
        if (!empty($this->prefix)) {
            $key = str_replace($this->prefix, '', $key);
        }
        $info = apcu_key_info($cache_key);
        return ['key'   => $key,
                'code'  => $code,
                'time'  => $info['mtime'],
                'size'  => 0,
                'hits'  => $info['hits'],
                'check' => $info['ttl']];
    }

    public function doGarbageCollection($expire = 0)
    {
        // we rely on the built-in garbage collector here
        /*
        apcu_clear_cache();
        */
    }

    public function getCacheInfo()
    {
        $this->size = 0;

        // this is the info for the whole cache
        $cacheinfo = apcu_cache_info();
        foreach ($cacheinfo['cache_list'] as $k => $v) {
            $this->size += $v['mem_size'];
            if (!empty($v['mtime']) && $v['mtime'] > $this->modtime) {
                $this->modtime = $v['mtime'];
            }
        }
        $this->items = count($cacheinfo['cache_list']);
        $this->hits = $cacheinfo['num_hits'];
        $this->misses = $cacheinfo['num_misses'];

        return ['size'    => $this->size,
                'items'   => $this->items,
                'hits'    => $this->hits,
                'misses'  => $this->misses,
                'modtime' => $this->modtime];
    }

    public function getCachedList()
    {
        $list = [];
        // this is the info for the whole cache
        $cacheinfo = apcu_cache_info();
        foreach ($cacheinfo['cache_list'] as $entry) {
            // filter out the keys that don't start with the right type/namespace prefix
            if (!empty($this->prefix) && strpos($entry['info'], $this->prefix) !== 0) {
                continue;
            }
            // CHECKME: this assumes the code is always hashed
            if (preg_match('/^(.*)-(\w*)$/', $entry['info'], $matches)) {
                $key = $matches[1];
                $code = $matches[2];
            } else {
                $key = $entry['info'];
                $code = '';
            }
            $time = $entry['mtime'];
            $size = $entry['mem_size'];
            $check = $entry['ttl'];
            // remove the prefix from the key
            if (!empty($this->prefix)) {
                $key = str_replace($this->prefix, '', $key);
            }
            $list[] = ['key'   => $key,
                       'code'  => $code,
                       'time'  => $time,
                       'size'  => $size,
                       'check' => $check];
        }
        return $list;
    }
}
