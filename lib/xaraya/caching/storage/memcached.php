<?php
/**
 * Cache data using the PHP Memcache extension [http://www.php.net/memcache]
 * and a memcached server [http://www.danga.com/memcached/]
 */
class xarCache_MemCached_Storage extends xarCache_Storage
{
    public $host       = 'localhost';
    public $port       = 11211;
    public $memcache   = null;
    public $persistent = false;

    public function __construct(array $args = array())
    {
        parent::__construct($args);

        if (!empty($args['host'])) {
            $this->host = $args['host'];
        }
        if (!empty($args['port'])) {
            $this->port = $args['port'];
        }
        // true or false
        if (isset($args['persistent'])) {
            $this->persistent = $args['persistent'];
        }
        if ($this->persistent) {
            $this->memcache = @memcache_pconnect($this->host, $this->port);
        } else {
            $this->memcache = @memcache_connect($this->host, $this->port);
        }
        $this->storage = 'memcached';
    }

    public function isCached($key = '', $expire = 0, $log = 1)
    {
        if (empty($this->memcache)) return false;

        if (empty($expire)) {
            $expire = $this->expire;
        }
        $cache_key = $this->getCacheKey($key);
        // we actually retrieve the value here too - returns FALSE on failure
        $value = $this->memcache->get($cache_key);
        if (isset($value) && $value !== false) {
            // FIXME: memcached doesn't keep track of modification times !
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
        if (empty($this->memcache)) return;

        if (empty($expire)) {
            $expire = $this->expire;
        }
        $cache_key = $this->getCacheKey($key);
        $value = $this->memcache->get($cache_key);
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
        if (empty($this->memcache)) return;

        if (empty($expire)) {
            $expire = $this->expire;
        }
        $cache_key = $this->getCacheKey($key);
        if ($this->compressed) {
            $flag = MEMCACHE_COMPRESSED;
        } else {
            $flag = false;
        }
        if (!empty($expire)) {
            $this->memcache->set($cache_key, $value, $flag, $expire);
        } else {
            $this->memcache->set($cache_key, $value, $flag);
        }
    }

    public function delCached($key = '')
    {
        if (empty($this->memcache)) return;

        $cache_key = $this->getCacheKey($key);
        $this->memcache->delete($cache_key);
    }

    public function doGarbageCollection($expire = 0)
    {
        // we rely on the built-in garbage collector here
    }

    public function getCacheSize($countitems = false)
    {
        if (empty($this->memcache)) return;

        // this is the size of the whole cache
        $stats = $this->memcache->getstats();

        $this->size = $stats['bytes'];
        if ($countitems) {
            $this->numitems = $stats['curr_items'];
        }
        return $stats['bytes'];
    }

    public function sizeLimitReached()
    {
        return false;
    }

    public function getCachedList()
    {
        return array();
    }
}

?>
