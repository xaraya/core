<?php
/**
 * Base class and factory for the cache storage types
 *
 * @package core\caching
 * @subpackage caching
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @todo get the var directory from the configured sys:varpath(), dont hardcode
**/

sys::import('xaraya.caching.interfaces');

class xarCache_Storage extends xarObject
{
    public string $storage    = '';        // filesystem, database, apcu or doctrine cache
    public string $cachedir   = 'var/cache/output';
    public string $type       = '';        // page, block, object, module, template, core, ...
    public string $code       = '';        // URL factors et al.
    public bool $compressed = false;
    public int $sizelimit  = 10000000;
    public int $expire     = 0;
    public ?string $logfile    = null;
    public int $logsize    = 2000000;   // for each logfile
    public string $namespace  = '';        // optional namespace prefix for the cache keys (= sitename, version, ...)
    public ?object $provider   = null;      // instantiated Doctrine CacheProvider (for doctrine)

    public string $prefix     = '';        // the default prefix for the cache keys will be 'type/namespace' (except in filesystem)

    public int $size       = 0;
    public int $items      = 0;
    public int $hits       = 0;
    public int $misses     = 0;
    public int $modtime    = 0;         // last modification time
    public ?bool $reached    = null;      // result of sizeLimitReached()

    /**
     * Factory class method for cache storage (only 'storage' is semi-required)
     *
     * @param array<string, mixed> $args
     * with
     *     string  $storage the storage you want (filesystem, database, apcu or doctrine)
     *     string  $type the type of cached data (page, block, template, ...)
     *     string  $cachedir the path to the cache directory (for filesystem)
     *     string  $code the cache code (for URL factors et al.) if it's fixed
     *     integer $expire the expiration time for this data
     *     integer $sizelimit the maximum size for the cache storage
     *     string  $logfile the path to the logfile for HITs and MISSes
     *     integer $logsize the maximum size of the logfile
     *     string  $namespace optional namespace prefix for the cache keys
     *     object  $provider an instantiated Doctrine CacheProvider (for doctrine)
     * @return ixarCache_Storage the specified cache storage
     */
    public static function getCacheStorage(array $args = [])
    {
        if (empty($args['storage'])) {
            $args['storage'] = 'filesystem';
        }
        switch ($args['storage']) {
            case 'database':
                sys::import('xaraya.caching.storage.database');
                $classname = 'xarCache_Database_Storage';
                break;

            case 'apc':
            case 'apcu':
                if (function_exists('apcu_fetch')) {
                    sys::import('xaraya.caching.storage.apcu');
                    $classname = 'xarCache_APCu_Storage';
                } elseif (function_exists('apc_fetch')) {
                    sys::import('xaraya.caching.storage.apc');
                    $classname = 'xarCache_APC_Storage';
                } else {
                    sys::import('xaraya.caching.storage.filesystem');
                    $classname = 'xarCache_FileSystem_Storage';
                }
                break;

            case 'doctrine':
                /** @uses \sys::autoload() */
                if (class_exists('Doctrine\\Common\\Cache\\CacheProvider')) {
                    sys::import('xaraya.caching.storage.doctrine');
                    $classname = 'xarCache_Doctrine_Storage';
                } else {
                    sys::import('xaraya.caching.storage.filesystem');
                    $classname = 'xarCache_FileSystem_Storage';
                }
                break;

            case 'memcached':
                if (class_exists('Memcache')) {
                    sys::import('xaraya.caching.storage.memcached');
                    $classname = 'xarCache_MemCached_Storage';
                } else {
                    sys::import('xaraya.caching.storage.filesystem');
                    $classname = 'xarCache_FileSystem_Storage';
                }
                break;

            case 'mmcache':
                if (function_exists('mmcache_get')) {
                    sys::import('xaraya.caching.storage.mmcache');
                    $classname = 'xarCache_MMCache_Storage';
                } else {
                    sys::import('xaraya.caching.storage.filesystem');
                    $classname = 'xarCache_FileSystem_Storage';
                }
                break;

            case 'eaccelerator':
                if (function_exists('eaccelerator_get')) {
                    sys::import('xaraya.caching.storage.eaccelarator');
                    $classname = 'xarCache_eAccelerator_Storage';
                } else {
                    sys::import('xaraya.caching.storage.filesystem');
                    $classname = 'xarCache_FileSystem_Storage';
                }
                break;

            case 'xcache':
                if (function_exists('xcache_get')) {
                    sys::import('xaraya.caching.storage.xcache');
                    $classname = 'xarCache_XCache_Storage';
                } else {
                    sys::import('xaraya.caching.storage.filesystem');
                    $classname = 'xarCache_FileSystem_Storage';
                }
                break;

            case 'dummy':
                sys::import('xaraya.caching.storage.dummy');
                $classname = 'xarCache_Dummy_Storage';
                break;

            case 'filesystem':
            default:
                sys::import('xaraya.caching.storage.filesystem');
                $classname = 'xarCache_FileSystem_Storage';
                break;
        }
        return new $classname($args);
    }

    /**
     * Constructor
     *
     * @param array<string, mixed> $args
     * @todo using an args array here is taking the easy way out, lets define a proper interface
     */
    public function __construct(array $args = [])
    {
        if (!empty($args['type'])) {
            $this->type = strtolower($args['type']);
        }
        if (!empty($args['cachedir'])) {
            $this->cachedir = $args['cachedir'];
        }
        if (!empty($args['code'])) {
            $this->code = $args['code'];
        }
        if (!empty($args['expire'])) {
            $this->expire = $args['expire'];
        }
        if (!empty($args['sizelimit'])) {
            $this->sizelimit = $args['sizelimit'];
        }
        if (!empty($args['logfile'])) {
            // CHECKME: this will return false if the file doesn't exist yet - is that what we want here ?
            $this->logfile = realpath($args['logfile']);
        }
        if (!empty($args['logsize'])) {
            $this->logsize = $args['logsize'];
        }
        if (!empty($args['provider'])) {
            $this->provider = $args['provider'];
        }
        // the namespace must be usable as a filename prefix here !
        if (!empty($args['namespace']) && preg_match('/^[a-zA-Z0-9 _.-\/]+$/', $args['namespace'])) {
            $this->namespace = $args['namespace'];
        }
        // the default prefix for the cache keys will be 'type/namespace', except in filesystem (for now)
        $this->prefix = $this->type . '/' . $this->namespace;

        $this->cachedir = realpath($this->cachedir);
    }

    /**
     * Set the current namespace prefix
     * @param string $namespace
     * @return void
     */
    public function setNamespace($namespace = '')
    {
        $this->namespace = $namespace;
        // the default prefix for the cache keys will be 'type/namespace', except in filesystem (for now)
        $this->prefix = $this->type . '/' . $this->namespace;
    }

    /**
     * Set the current Doctrine CacheProvider (for doctrine)
     * @param mixed $provider
     * @return void
     */
    public function setProvider($provider = '')
    {
        $this->provider = $provider;
    }

    /**
     * Set the current code suffix
     * @param string $code
     * @return void
     */
    public function setCode($code = '')
    {
        $this->code = $code;
    }

    /**
     * Get the actual cache key used for storage (= including namespace and code)
     * @param string $key
     * @return string
     */
    public function getCacheKey($key = '')
    {
        // add the type/namespace prefix
        if (!empty($this->prefix)) {
            $key = $this->prefix . $key;
        }
        // add the code suffix
        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }
        return $key;
    }

    /**
     * Set the current expiration time (not used by all storage)
     * @param int $expire
     * @return void
     */
    public function setExpire($expire = 0)
    {
        $this->expire = $expire;
    }

    /**
     * Get the last modification time (not supported by all storage)
     * @return int
     */
    public function getLastModTime()
    {
        return $this->modtime;
    }

    /**
     * Check if the data is cached
     * @param string $key
     * @param int $expire
     * @param int $log
     * @return bool
     */
    public function isCached($key = '', $expire = 0, $log = 1)
    {
        $cache_key = $this->getCacheKey($key);
        return false;
    }

    /**
     * Get the cached data
     * @param string $key
     * @param int $output
     * @param int $expire
     * @return mixed
     */
    public function getCached($key = '', $output = 0, $expire = 0)
    {
        $cache_key = $this->getCacheKey($key);
        return '';
    }

    /**
     * Set the cached data
     * @param string $key
     * @param mixed $value
     * @param int $expire
     * @return void
     */
    public function setCached($key = '', $value = '', $expire = 0)
    {
        $cache_key = $this->getCacheKey($key);
    }

    /**
     * Delete the cached data
     * @param string $key
     * @return void
     */
    public function delCached($key = '')
    {
        $cache_key = $this->getCacheKey($key);
    }

    /**
     * Get detailed information about the cache key (not supported by all storage)
     * @param string $key
     * @return array<string, mixed>
     */
    public function keyInfo($key = '')
    {
        $cache_key = $this->getCacheKey($key);
        // filter out the keys that don't start with the right type/namespace prefix
        if (!empty($this->prefix) && strpos($cache_key, $this->prefix) !== 0) {
            return [];
        }
        // CHECKME: this assumes the code is always hashed
        if (preg_match('/^(.*)-(\w*)$/', $cache_key, $matches)) {
            $key = $matches[1];
            $code = $matches[2];
        } else {
            $key = $cache_key;
            $code = '';
        }
        // remove the prefix from the key
        if (!empty($this->prefix)) {
            $key = str_replace($this->prefix, '', $key);
        }
        return ['key'   => $key,
                'code'  => $code,
                'time'  => time(),
                'size'  => 0,
                'check' => ''];
    }

    /**
     * Flush all cache keys that start with this key (= for all code suffixes)
     * @param string $key
     * @return void
     */
    public function flushCached($key = '')
    {
        $list = $this->getCachedList();
        foreach ($list as $item) {
            if (empty($item['key'])) {
                continue;
            }
            // check if this cache entry starts with the key
            if (!empty($key) && strpos($item['key'], $key) !== 0) {
                continue;
            }
            // set the current code suffix
            $this->setCode($item['code']);
            // delete the cache entry
            $this->delCached($item['key']);
        }

        // check the cache size and clear the lockfile set by sizeLimitReached()
        $lockfile = $this->cachedir . '/cache.' . $this->type . 'full';
        if ($this->getCacheSize() < $this->sizelimit && file_exists($lockfile)) {
            @unlink($lockfile);
        }
    }

    /**
     * Clean up the cache based on expiration time
     * @param int $expire
     * @return void
     */
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
            error_log('Error from xaraya.caching.storage - web process can not touch ' . $touch_file);
        }

        // do whatever the storage supports here
        $this->doGarbageCollection($expire);

        // check the cache size and clear the lockfile set by sizeLimitReached()
        $lockfile = $this->cachedir . '/cache.' . $this->type . 'full';
        if ($this->getCacheSize() < $this->sizelimit && file_exists($lockfile)) {
            @unlink($lockfile);
        }
    }

    /**
     * Do garbage collection based on expiration time (not supported by all storage)
     * @param int $expire
     * @return void
     */
    public function doGarbageCollection($expire = 0)
    {
        // we rely on the built-in garbage collector here
    }

    /**
     * Get information about the cache (not supported by all storage)
     * @return array<string, mixed>
     */
    public function getCacheInfo()
    {
        return ['size'    => $this->size,
                'items'   => $this->items,
                'hits'    => $this->hits,
                'misses'  => $this->misses,
                'modtime' => $this->modtime];
    }

    /**
     * Get the current cache size (not supported by all storage)
     * @param bool $countitems
     * @return int
     */
    public function getCacheSize($countitems = false)
    {
        $this->getCacheInfo();
        return $this->size;
    }

    /**
     * Get the number of items in cache (not supported by all storage)
     * @return int
     */
    public function getCacheItems()
    {
        return $this->items;
    }

    /**
     * Check if we reached the size limit for this cache (not supported by all storage)
     * @return bool
     */
    public function sizeLimitReached()
    {
        if (isset($this->reached)) {
            return $this->reached;
        }

        $lockfile = $this->cachedir . '/cache.' . $this->type . 'full';
        if (file_exists($lockfile)) {
            $value = true;
        } elseif (mt_rand(1, 5) > 1) {
            // on average, 4 out of 5 pages go by without checking
            $value = false;
        } else {
            $size = $this->getCacheSize();
            if ($size >= $this->sizelimit) {
                $value = true;
                @touch($lockfile);
            } else {
                $value = false;
            }
        }
        $this->reached = $value;

        if (!empty($value)) {
            $this->cleanCached();
        }
        return $value;
    }

    /**
     * Log the HIT / MISS status for cache keys
     * @param string $status
     * @param string $key
     * @return void
     */
    public function logStatus($status = 'MISS', $key = '')
    {
        if (empty($this->logfile) || empty(xarServer::getVar('HTTP_HOST')) ||
            empty(xarServer::getVar('REQUEST_URI')) || empty(xarServer::getVar('REMOTE_ADDR'))) {
            return;
        }

        $time = time();
        $addr = !empty(xarServer::getVar('REMOTE_ADDR')) ? xarServer::getVar('REMOTE_ADDR') : '-';
        $url = 'http://'.xarServer::getVar('HTTP_HOST').xarServer::getVar('REQUEST_URI');
        //$ref = !empty(xarServer::getVar('HTTP_REFERER')) ? xarServer::getVar('HTTP_REFERER') : '-';
        $type = $this->type;
        $code = $this->code;

        if (file_exists($this->logfile) && filesize($this->logfile) > $this->logsize) {
            $fp = @fopen($this->logfile, 'w');
        } else {
            $fp = @fopen($this->logfile, 'a');
        }
        if ($fp) {
            @fwrite($fp, "$time $status $type $key $code $addr $url\n");
            @fclose($fp);
        }
    }

    /**
     * Save the cached data to file
     * @param string $key
     * @param string $filename
     * @return void
     */
    public function saveFile($key = '', $filename = '')
    {
        if (empty($filename)) {
            return;
        }

        // FIXME: avoid getting the value for the 2nd/3rd time here
        $value = $this->getCached($key);
        if (empty($value)) {
            return;
        }

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

    /**
     * Summary of getCachedList
     * @return array<mixed>
     */
    public function getCachedList()
    {
        $list = [];
        //$items = ... get list of items in cache ...
        //foreach ($items as $item) {
        //    // ... get information about this item ...
        //    // filter out the keys that don't start with the right type/namespace prefix
        //    if (!empty($this->prefix) && strpos($key, $this->prefix) !== 0) continue;
        //    // remove the prefix from the key
        //    if (!empty($this->prefix)) $key = str_replace($this->prefix,'',$key);
        //    $list[] = array('key'   => $key,
        //                    'code'  => $code,
        //                    'time'  => $time,
        //                    'size'  => $size,
        //                    'check' => $check);
        //}
        return $list;
    }

    /**
     * Summary of getCachedKeys
     * @return array<string, int>
     */
    public function getCachedKeys()
    {
        $list = $this->getCachedList();
        $keys = [];
        foreach ($list as $item) {
            if (empty($item['key'])) {
                continue;
            }
            // filter out the keys that don't start with the right type/namespace prefix - if this wasn't done already
            //if (!empty($this->prefix) && strpos($item['key'], $this->prefix) !== 0) continue;
            $keys[$item['key']] ??= 0;
            $keys[$item['key']] += 1;
        }
        return $keys;
    }
}
