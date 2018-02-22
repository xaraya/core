<?php
/**
 * @package core\caching
 * @subpackage caching
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */
/**
 * Cache data using Turck MMCache [http://turck-mmcache.sourceforge.net/]
 */

class xarCache_MMCache_Storage extends xarCache_Storage implements ixarCache_Storage
{
    public function __construct(Array $args = array())
    {
        parent::__construct($args);
        $this->storage = 'mmcache';
    }

    public function isCached($key = '', $expire = 0, $log = 1)
    {
        if (empty($expire)) {
            $expire = $this->expire;
        }
        $cache_key = $this->getCacheKey($key);
        // we actually retrieve the value here too - returns NULL on failure
        $value = mmcache_get($cache_key);
        if (isset($value)) {
            // FIXME: mmcache doesn't keep track of modification times !
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
        $value = mmcache_get($cache_key);
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
            mmcache_put($cache_key, $value, $expire);
        } else {
            mmcache_put($cache_key, $value);
        }
        $this->modtime = time();
    }

    public function delCached($key = '')
    {
        $cache_key = $this->getCacheKey($key);
        mmcache_rm($cache_key);
    }

    public function doGarbageCollection($expire = 0)
    {
        // we rely on the expire value here
        mmcache_gc();
    }

    public function getCacheInfo()
    {
        // this is the size of the whole cache
        ob_start();
        mmcache();
        $output = ob_get_contents();
        ob_end_clean();
        if (preg_match('/Memory Allocated<.+?>([0-9,]+) Bytes/',$output,$matches)) {
            $this->size = strtr($matches[1],array(',' => ''));
        }
        if (preg_match('/Cached Keys<.+?>(\d+)/',$output,$matches)) {
            $this->items = $matches[1];
        }
        // TODO: extract other values

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