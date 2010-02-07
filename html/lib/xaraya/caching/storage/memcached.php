<?php
/**
 * Cache data using the PHP Memcache extension [http://www.php.net/memcache]
 * and one or more memcached server(s) [http://www.danga.com/memcached/]
 */

class xarCache_MemCached_Storage extends xarCache_Storage implements ixarCache_Storage
{
    public $host       = 'localhost';
    public $port       = 11211; // default values, cfr. http://php.net/manual/en/function.memcache-addserver.php
    public $persistent = true;
    public $weight     = 1;
    public $timeout    = 1;
    public $retry      = 15;

    public $memcache   = null;

    public function __construct(Array $args = array())
    {
        parent::__construct($args);

        if (!empty($args['host'])) {
            $this->host = $args['host'];
        }
        // set to 0 for Unix socket
        if (isset($args['port'])) {
            $this->port = $args['port'];
        }
        // true or false
        if (isset($args['persistent'])) {
            $this->persistent = $args['persistent'];
        }
        if (isset($args['weight'])) {
            $this->weight = $args['weight'];
        }
        if (isset($args['timeout'])) {
            $this->timeout = $args['timeout'];
        }
        if (isset($args['retry'])) {
            $this->retry = $args['retry'];
        }

        // default expiration time is set to 24 hours
        if (empty($this->expire)) {
            $this->expire = 24 * 60 * 60;
        }

        $this->memcache = new Memcache;
        // support pooled connections, cfr. attachment in bug 6315 by Mark Frawley
        if (is_array($this->host)) {
            foreach($this->host as $server) {
                if (is_array($server) && !empty($server['host'])) {
                    if (!isset($server['port'])) {
                        $server['port'] = $this->port;
                    }
                    if (!isset($server['persisten'])) {
                        $server['persistent'] = $this->persistent;
                    }
                    if (!isset($server['weight'])) {
                        $server['weight'] = $this->weight;
                    }
                    if (!isset($server['timeout'])) {
                        $server['timeout'] = $this->timeout;
                    }
                    if (!isset($server['retry'])) {
                        $server['retry'] = $this->retry;
                    }
                    $this->memcache->addServer($server['host'], $server['port'], $server['persistent'], $server['weight'], $server['timeout'], $server['retry']);
                } else {
                    $this->memcache->addServer($server, $this->port, $this->persistent, $this->weight, $this->timeout, $this->retry);
                }
            }
        } else {
            $this->memcache->addServer($this->host, $this->port, $this->persistent, $this->weight, $this->timeout, $this->retry);
        }

        $this->storage = 'memcached';
    }

/*
    public function setNamespace($namespace = '')
    {
        // customize with site prefix, versioned namespaces etc. see bug 6315
        parent::setNamespace($namespace);
    }

    public function getCacheKey($key = '')
    {
        // customize with site prefix, versioned namespaces etc. see bug 6315
        return parent::getCacheKey($key);
    }

    public function flushCached($key = '')
    {
        // customize with site prefix, versioned namespaces etc. see bug 6315
        return parent::flushCached($key);
    }
*/

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
        $this->modtime = time();
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

    public function getCacheInfo()
    {
        if (empty($this->memcache)) return;

        // this is the size of the whole cache for the current server
        $stats = $this->memcache->getStats();

        $this->size = $stats['bytes'];
        $this->items = $stats['curr_items'];
        $this->hits = $stats['get_hits'];
        $this->misses = $stats['get_misses'];

        return array('size'    => $this->size,
                     'items'   => $this->items,
                     'hits'    => $this->hits,
                     'misses'  => $this->misses,
                     'modtime' => $this->modtime);
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