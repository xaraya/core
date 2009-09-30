<?php

/**
 * Cache data using XCache [http://xcache.lighttpd.net/]
 */

class xarCache_XCache_Storage extends xarCache_Storage
{
    public function __construct(array $args = array())
    {
        parent::__construct($args);
        $this->storage = 'xcache';
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
        if (xcache_isset($key)) {
            // FIXME: xcache doesn't keep track of modification times, except via xcache_list
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
        $value = xcache_get($key);
        // XCache doesn't support caching objects, so we serialize them
        if (!empty($value) && is_array($value) && isset($value['_xcache_class_'])) {
            $classname = $value['_xcache_class_'];
            // Note: this will call __autoload or any spl_autoload_functions() if they are registered
            if (!class_exists($classname)) {
                // FIXME: do something like this in core ? 
                //sys::import('xaraya.autoload');
                //xarAutoload::initialize();
            }
            $value = @unserialize($value['_xcache_value_']);
        }
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
        // XCache doesn't support caching objects, so we serialize them
        if (is_object($value)) {
            $classname = get_class($value);
            $value = array('_xcache_class_' => $classname,
                           '_xcache_value_' => serialize($value));
        // XCache doesn't support caching resources, so we forget about it
        } elseif (is_resource($value)) {
            return;
        }
        if (!empty($expire)) {
            xcache_set($key, $value, $expire);
        } else {
            xcache_set($key, $value);
        }
    }

    public function delCached($key = '')
    {
        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }
        xcache_unset($key);
    }

    public function flushCached($key = '')
    {
        // Note: this isn't quite the same as in filesystem, but it's close enough :-)
        xcache_unset_by_prefix($key);

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
            error_log('Error from Xaraya::xarCache::storage::xcache
                      - web process can not touch ' . $touch_file);
        }

        // we rely on the built-in garbage collector here
        /*
        $vcnt = xcache_count(XC_TYPE_VAR);
        for ($i = 0; $i < $vcnt; $i ++) {
            xcache_clear_cache(XC_TYPE_VAR, $i);
        }
        */

        // check the cache size and clear the lockfile set by sizeLimitReached()
        $lockfile = $this->cachedir . '/cache.' . $this->type . 'full';
        if ($this->getCacheSize() < $this->sizelimit && file_exists($lockfile)) {
            @unlink($lockfile);
        }
    }

    public function getCacheSize($countitems = false)
    {
        // this is the size of the whole cache
        $vcnt = xcache_count(XC_TYPE_VAR);
        $size = 0;
        $numitems = 0;
        for ($i = 0; $i < $vcnt; $i ++) {
            $info = xcache_info(XC_TYPE_VAR, $i);
            $size += ($info['size'] - $info['avail']);
            $numitems += $info['cached'];
        }
        $this->size = $size;
        if ($countitems) {
            $this->numitems = $numitems;
        }
        return $this->size;
    }

    public function saveFile($key = '', $filename = '')
    {
        if (empty($filename)) return;

        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }
        // FIXME: avoid getting the value for the 2nd/3rd time here
        $value = xcache_get($key);
        if (empty($value)) return;

        // CHECKME: don't try to unserialize objects here ?!

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
        $vcnt = xcache_count(XC_TYPE_VAR);
        $list = array();
        for ($i = 0; $i < $vcnt; $i ++) {
            $info = xcache_list(XC_TYPE_VAR, $i);
            foreach ($info['cache_list'] as $entry) {
                if (preg_match('/^(.*)-(\w*)$/',$entry['name'],$matches)) {
                    $key = $matches[1];
                    $code = $matches[2];
                } else {
                    $key = $entry['name'];
                    $code = '';
                }
                $time = $entry['ctime'];
                $size = $entry['size'];
                $check = '';
                $list[] = array('key'   => $key,
                                'code'  => $code,
                                'time'  => $time,
                                'size'  => $size,
                                'check' => $check);
            }
        }
        return $list;
    }
}

?>
