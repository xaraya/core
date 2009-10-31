<?php
/**
 * Cache data using APC [http://pecl.php.net/apc/]
 */
class xarCache_APC_Storage extends xarCache_Storage
{
    public function __construct(array $args = array())
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

    public function getCacheSize($countitems = false)
    {
        $cacheinfo = apc_cache_info();
        
        $this->numitems = count($cacheinfo['cache_list']);
        
        $size = 0;
        foreach ($cacheinfo['cache_list'] as $k => $v) {
            $size += $v['mem_size'];
        }

        $this->size = $size;
        return $this->size;
    }

    public function getCachedList()
    {
        return array();
    }
}

?>
