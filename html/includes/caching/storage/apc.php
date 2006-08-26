<?php

/**
 * Cache data using APC [http://pecl.php.net/apc/]
 */

class xarCache_APC_Storage extends xarCache_Storage
{
    public function __construct($args = array())
    {
        parent::__construct($args);
        $this->storage = 'apc';
    }

    public function isCached($key = '', $expire = 0, $log = 1)
    {
        if (empty($expire)) {
            $expire = $this->expire;
        }
        $oldkey = $key;
        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }
        // we actually retrieve the value here too
        $value = apc_fetch($key);
        if ($value) {
            // FIXME: APC doesn't keep track of modification times !
            //$this->modtime = 0;
            if ($log) $this->logStatus('HIT', $oldkey);
            return true;
        } else {
            if ($log) $this->logStatus('MISS', $oldkey);
            return false;
        }
    }

    public function getCached($key = '', $output = 0, $expire = 0)
    {
        if (empty($expire)) {
            $expire = $this->expire;
        }
        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }
        $value = apc_fetch($key);
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
        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }
        if (!empty($expire)) {
            apc_store($key, $value, $expire);
        } else {
            apc_store($key, $value);
        }
    }

    public function delCached($key = '')
    {
        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }
        apc_delete($key);
    }

    public function flushCached($key = '')
    {
        // CHECKME: we can't really flush part of the cache here, unless we
        //          keep track of all cache entries, perhaps ?

        // check the cache size and clear the lockfile set by sizeLimitReached()
        $lockfile = $this->cachedir . '/cache.' . $this->type . 'full';
        if ($this->getCacheSize() < $this->sizelimit && file_exists($lockfile)) {
            @unlink($lockfile);
        }
    }

    public function cleanCached($expire = 0)
    {
        if (empty($expire)) {
            $expire = $this->expire;
        }
        if (empty($expire)) {
            // TODO: delete oldest entries if we're at the size limit ?
            return;
        }

        $touch_file = $this->cachedir . '/cache.' . $this->type . 'level';

        // If the cache type has already been cleaned within the expiration time,
        // don't bother checking again
        if (file_exists($touch_file) && filemtime($touch_file) > time() - $expire) {
            return;
        }
        if (!@touch($touch_file)) {
            // hmm, somthings amiss... better let the administrator know,
            // without disrupting the site
            error_log('Error from Xaraya::xarCache::storage::apc
                      - web process can not touch ' . $touch_file);
        }

        apc_clear_cache();

        // check the cache size and clear the lockfile set by sizeLimitReached()
        $lockfile = $this->cachedir . '/cache.' . $this->type . 'full';
        if ($this->getCacheSize() < $this->sizelimit && file_exists($lockfile)) {
            @unlink($lockfile);
        }
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

    public function saveFile($key = '', $filename = '')
    {
        if (empty($filename)) return;

        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }
        // FIXME: avoid getting the value for the 2nd/3rd time here
        $value = apc_fetch($key);
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

    public function getCachedList()
    {
        return array();
    }
}

?>
