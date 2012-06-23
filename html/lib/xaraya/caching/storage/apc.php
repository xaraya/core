<?php
/**
 * @package core
 * @subpackage caching
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 */

/**
 * Cache data using APC [http://pecl.php.net/apc/]
 */

sys::import('xaraya.caching.storage');
class xarCache_APC_Storage extends xarCache_Storage implements ixarCache_Storage
{
    public function __construct(Array $args = array())
    {
        parent::__construct($args);
        $this->storage = 'apc';
    }

    public function isCached($key = '', $expire = 0, $log = 1)
    {
        if (empty($expire)) {
            $expire = $this->expire;
        }
        $cache_key = $this->getCacheKey($key);
        // we actually retrieve the value here too - returns FALSE on failure
        $value = apc_fetch($cache_key);
        if (isset($value) && $value !== false) {
            // FIXME: APC doesn't keep track of modification times !
            //$this->modtime = 0;
            if ($log) $this->logStatus('HIT', $key);
            return true;
        } else {
            if ($log) $this->logStatus('MISS', $key);
            return false;
        }
    }

    public function getCached($key = '', $output = 0, $expire = 0)
    {
        if (empty($expire)) {
            $expire = $this->expire;
        }
        $cache_key = $this->getCacheKey($key);
        $value = apc_fetch($cache_key);
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
            apc_store($cache_key, $value, $expire);
        } else {
            apc_store($cache_key, $value);
        }
        $this->modtime = time();
    }

    public function delCached($key = '')
    {
        $cache_key = $this->getCacheKey($key);
        apc_delete($cache_key);
    }

    public function doGarbageCollection($expire = 0)
    {
        // we rely on the built-in garbage collector here
        /*
        apc_clear_cache('user');
        */
    }

    public function getCacheInfo()
    {
        $this->size = 0;

        // this is the info for the whole cache
        $cacheinfo = apc_cache_info('user');
        foreach ($cacheinfo['cache_list'] as $k => $v) {
            $this->size += $v['mem_size'];
            if (!empty($v['mtime']) && $v['mtime'] > $this->modtime) {
                $this->modtime = $v['mtime'];
            }
        }
        $this->items = count($cacheinfo['cache_list']);
        $this->hits = $cacheinfo['num_hits'];
        $this->misses = $cacheinfo['num_misses'];

        return array('size'    => $this->size,
                     'items'   => $this->items,
                     'hits'    => $this->hits,
                     'misses'  => $this->misses,
                     'modtime' => $this->modtime);
    }

    public function getCachedList()
    {
        return array();
    }
}

?>