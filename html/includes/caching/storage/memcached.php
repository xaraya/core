<?php

include_once 'includes/caching/storage.php';

class xarCache_MemCached_Storage extends xarCache_Storage
{
    var $host = 'localhost';
    var $port = 11211;
    var $memcache = null;
    var $persistent = false;
    var $expire = 0;

    function xarCache_MemCached_Storage($args = array())
    {
        $this->xarCache_Storage($args);

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
        if (!empty($args['expire'])) {
            $this->expire = $args['expire'];
        }
        if ($this->persistent) {
            $this->memcache = memcache_pconnect($this->host, $this->port);
        } else {
            $this->memcache = memcache_connect($this->host, $this->port);
        }
        $this->storage = 'ms';
    }

    function isCached($key = '')
    {
        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }
        // we actually retrieve the value here too
        $value = $this->memcache->get($key);
        if ($value) {
            return true;
        } else {
            return false;
        }
    }

    function getCached($key = '')
    {
        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }
        $value = $this->memcache->get($key);
        return $value;
    }

    function setCached($key = '', $value = '', $expire = null)
    {
        if (isset($expire)) {
            $this->expire = $expire;
        }
        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }
        if (!empty($this->expire)) {
            $this->memcache->set($key, $value, $this->expire);
        } else {
            $this->memcache->set($key, $value);
        }
    }

    function delCached($key = '')
    {
        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }
        $this->memcache->delete($key);
    }

    function flushCached($key = '')
    {
        // we can't really flush part of the cache here, unless we
        // keep track of all cache entries, perhaps ?
    }

    function cleanCached()
    {
        // we rely on the expire value here
    }

    function getCacheSize()
    {
        // this is the size of the whole cache
        $stats = $this->memcache->getstats();
        return $stats['bytes'];
    }
}

?>
