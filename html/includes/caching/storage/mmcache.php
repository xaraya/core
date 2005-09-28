<?php

/**
 * Cache data using Turck MMCache [http://turck-mmcache.sourceforge.net/]
 */

class xarCache_MMCache_Storage extends xarCache_Storage
{
    function xarCache_MMCache_Storage($args = array())
    {
        $this->xarCache_Storage($args);

        $this->storage = 'mmcache';
    }

    function isCached($key = '', $expire = 0, $log = 1)
    {
        if (empty($expire)) {
            $expire = $this->expire;
        }
        $oldkey = $key;
        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }
        // we actually retrieve the value here too
        $value = mmcache_get($key);
        if ($value) {
// FIXME: mmcache doesn't keep track of modification times !
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
        if (empty($expire)) {
            $expire = $this->expire;
        }
        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }
        $value = mmcache_get($key);
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
        if (empty($expire)) {
            $expire = $this->expire;
        }
        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }
        if (!empty($expire)) {
            mmcache_put($key, $value, $expire);
        } else {
            mmcache_put($key, $value);
        }
    }

    function delCached($key = '')
    {
        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }
        mmcache_rm($key);
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
            error_log('Error from Xaraya::xarCache::storage::mmcache
                      - web process can not touch ' . $touch_file);
        }

        // we rely on the expire value here
        mmcache_gc();

        // check the cache size and clear the lockfile set by sizeLimitReached()
        $lockfile = $this->cachedir . '/cache.' . $this->type . 'full';
        if ($this->getCacheSize() < $this->sizelimit && file_exists($lockfile)) {
            @unlink($lockfile);
        }
    }

    function getCacheSize($countitems = false)
    {
        // this is the size of the whole cache
        ob_start();
        mmcache();
        $output = ob_get_contents();
        ob_end_clean();
        if (preg_match('/Memory Allocated<.+?>([0-9,]+) Bytes/',$output,$matches)) {
            $this->size = strtr($matches[1],array(',' => ''));
        }
        if ($countitems && preg_match('/Cached Keys<.+?>(\d+)/',$output,$matches)) {
            $this->numitems = $matches[1];
        }
        return $this->size;
    }

    function saveFile($key = '', $filename = '')
    {
        if (empty($filename)) return;

        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }
    // FIXME: avoid getting the value for the 2nd/3rd time here
        $value = mmcache_get($key);
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
