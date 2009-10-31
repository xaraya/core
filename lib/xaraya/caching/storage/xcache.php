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
        $cache_key = $this->getCacheKey($key);
        if (xcache_isset($cache_key)) {
            // FIXME: xcache doesn't keep track of modification times, except via xcache_list
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
        $value = xcache_get($cache_key);
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
        $cache_key = $this->getCacheKey($key);
        // XCache doesn't support caching objects, so we serialize them
        $classname = '';
        if (is_object($value)) {
            $classname = get_class($value);
        } elseif (is_array($value) && count($value) > 0) {
            // check if the array values are objects
            foreach ($value as $idx => $val) {
                if (isset($val) && is_object($val)) {
                    $classname = get_class($val);
                }
                // we've seen enough
                break;
            }
        // XCache doesn't support caching resources, so we forget about it
        } elseif (is_resource($value)) {
            return;
        }
        if (!empty($classname)) {
            $value = array('_xcache_class_' => $classname,
                           '_xcache_value_' => serialize($value));
        }
        if (!empty($expire)) {
            xcache_set($cache_key, $value, $expire);
        } else {
            xcache_set($cache_key, $value);
        }
    }

    public function delCached($key = '')
    {
        $cache_key = $this->getCacheKey($key);
        xcache_unset($cache_key);
    }

    public function flushCached($key = '')
    {
        // add the type/namespace prefix
        if (!empty($this->prefix)) {
            $key = $this->prefix . $key;
        }

        // Note: this isn't quite the same as in filesystem, but it's close enough :-)
        xcache_unset_by_prefix($key);

        // check the cache size and clear the lockfile set by sizeLimitReached()
        $lockfile = $this->cachedir . '/cache.' . $this->type . 'full';
        if ($this->getCacheSize() < $this->sizelimit && file_exists($lockfile)) {
            @unlink($lockfile);
        }
    }

    public function doGarbageCollection($expire = 0)
    {
        // we rely on the built-in garbage collector here
        /*
        $vcnt = xcache_count(XC_TYPE_VAR);
        for ($i = 0; $i < $vcnt; $i ++) {
            xcache_clear_cache(XC_TYPE_VAR, $i);
        }
        */
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

    public function getCachedList()
    {
        $vcnt = xcache_count(XC_TYPE_VAR);
        $list = array();
        for ($i = 0; $i < $vcnt; $i ++) {
            $info = xcache_list(XC_TYPE_VAR, $i);
            foreach ($info['cache_list'] as $entry) {
                // filter out the keys that don't start with the right type/namespace prefix
                if (!empty($this->prefix) && strpos($entry['name'], $this->prefix) !== 0) continue;
            // CHECKME: this assumes the code is always hashed
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
