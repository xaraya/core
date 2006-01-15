<?php

/**
 * Cache data using the PHP Memcache extension [http://www.php.net/memcache]
 * and a memcached server [http://www.danga.com/memcached/]
 */

class xarCache_MemCached_Storage extends xarCache_Storage
{
    var $host = 'localhost';
    var $port = 11211;
    var $memcache = null;
    var $persistent = false;

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
        if ($this->persistent) {
            $this->memcache = @memcache_pconnect($this->host, $this->port);
        } else {
            $this->memcache = @memcache_connect($this->host, $this->port);
        }
        $this->storage = 'memcached';
    }

    function isCached($key = '', $expire = 0, $log = 1)
    {
        if (empty($this->memcache)) return false;

        if (empty($expire)) {
            $expire = $this->expire;
        }
        $oldkey = $key;
        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }
        // we actually retrieve the value here too
        $value = $this->memcache->get($key);
        if ($value) {
// FIXME: memcached doesn't keep track of modification times !
            //$this->modtime = 0;
            if ($log) $this->logStatus('HIT', $oldkey);
            return true;
        } else {
            if ($log) $this->logStatus('MISS', $oldkey);
            return false;
        }
    }

    function getCached($key = '', $output = 0, $expire = 0)
    {
        if (empty($this->memcache)) return;

        if (empty($expire)) {
            $expire = $this->expire;
        }
        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }
        $value = $this->memcache->get($key);
        if ($output) {
            // output the value directly to the browser
            echo $value;
            return true;
        } else {
            return $value;
        }
    }

    function setCached($key = '', $value = '', $expire = 0)
    {
        if (empty($this->memcache)) return;

        if (empty($expire)) {
            $expire = $this->expire;
        }
        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }
        if ($this->compressed) {
            $flag = MEMCACHE_COMPRESSED;
        } else {
            $flag = false;
        }
        if (!empty($expire)) {
            $this->memcache->set($key, $value, $flag, $expire);
        } else {
            $this->memcache->set($key, $value, $flag);
        }
    }

    function delCached($key = '')
    {
        if (empty($this->memcache)) return;

        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }
        $this->memcache->delete($key);
    }

    function flushCached($key = '')
    {
    // CHECKME: we can't really flush part of the cache here, unless we
    //          keep track of all cache entries, perhaps ?

        // check the cache size and clear the lockfile set by sizeLimitReached()
        $lockfile = $this->cachedir . '/cache.' . $this->type . 'full';
        if ($this->getCacheSize() < $this->sizelimit && file_exists($lockfile)) {
            @unlink($lockfile);
        }
    }

    function cleanCached($expire = 0)
    {
        // we rely on the expire value here

        // check the cache size and clear the lockfile set by sizeLimitReached()
        $lockfile = $this->cachedir . '/cache.' . $this->type . 'full';
        if ($this->getCacheSize() < $this->sizelimit && file_exists($lockfile)) {
            @unlink($lockfile);
        }
    }

    function getCacheSize($countitems = false)
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

    function saveFile($key = '', $filename = '')
    {
        if (empty($this->memcache)) return;

        if (empty($filename)) return;

        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }
    // FIXME: avoid getting the value for the 2nd/3rd time here
        $value = $this->memcache->get($key);
        if (empty($value)) return;

        $tmp_file = $filename . '.tmp';

        $fp = @fopen($tmp_file, "w");
        if (!empty($fp)) {
            @fwrite($fp, $value);
            @fclose($fp);
            // rename() doesn't overwrite existing files in Windows
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                @copy($tmp_file, $filename);
                @unlink($tmp_file);
            } else {
                @rename($tmp_file, $filename);
            }
        }
    }

    function getCachedList()
    {
        return array();
    }
}

?>
