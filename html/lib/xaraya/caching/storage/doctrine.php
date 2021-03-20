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
 * Cache data using an instantiated Doctrine CacheProvider
 * https://www.doctrine-project.org/projects/doctrine-cache/en/current/index.html
 */
class xarCache_Doctrine_Storage extends xarCache_Storage implements ixarCache_Storage
{
    public function __construct(array $args = array())
    {
        parent::__construct($args);
        if (empty($this->provider)) {
            throw new EmptyParameterException('provider');
        }
        $this->storage = 'doctrine';
    }

    public function getProvider()
    {
        return $this->provider;
    }

    public function isCached($key = '', $expire = 0, $log = 1)
    {
        if (empty($this->provider)) {
            return false;
        }

        if (empty($expire)) {
            $expire = $this->expire;
        }
        $cache_key = $this->getCacheKey($key);
        if ($this->provider->contains($cache_key)) {
            // FIXME: provider doesn't keep track of modification times !
            //$this->modtime = 0;
            if ($log) {
                $this->logStatus('HIT', $key);
            }
            return true;
        } else {
            if ($log) {
                $this->logStatus('MISS', $key);
            }
            return false;
        }
    }

    public function getCached($key = '', $output = 0, $expire = 0)
    {
        if (empty($this->provider)) {
            return;
        }

        if (empty($expire)) {
            $expire = $this->expire;
        }
        $cache_key = $this->getCacheKey($key);
        $value = $this->provider->fetch($cache_key);
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
        if (empty($this->provider)) {
            return;
        }

        if (empty($expire)) {
            $expire = $this->expire;
        }
        $cache_key = $this->getCacheKey($key);
        if (!empty($expire)) {
            $this->provider->save($cache_key, $value, $expire);
        } else {
            $this->provider->save($cache_key, $value);
        }
        $this->modtime = time();
    }

    public function delCached($key = '')
    {
        if (empty($this->provider)) {
            return;
        }

        $cache_key = $this->getCacheKey($key);
        $this->provider->delete($cache_key);
    }

    public function flushCached($key = '')
    {
        // Note: this isn't quite the same as in filesystem, but it's close enough :-)
        if (!empty($key)) {
            // add the type/namespace prefix if necessary
            if (!empty($this->prefix)) {
                $key = $this->prefix . $key;
            }
            $this->provider->deleteAll();
        } else {
            $this->provider->flushAll();
        }
    }

    public function doGarbageCollection($expire = 0)
    {
        // we rely on the built-in garbage collector here
    }

    public function getCacheInfo()
    {
        if (empty($this->provider)) {
            return;
        }

        // this is the size of the whole cache for the current server
        $stats = $this->provider->getStats();

        $this->size = $stats['memory_usage'];
        $this->items = 0;
        $this->hits = $stats['hits'];
        $this->misses = $stats['misses'];

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
