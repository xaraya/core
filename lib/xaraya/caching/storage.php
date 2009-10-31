<?php
/**
 * Base class and factory for the cache storage types
 *
 * @package core
 * @subpackage caching
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @todo get the var directory from the configured sys:varpath(), dont hardcode
**/
class xarCache_Storage extends Object
{
    public $storage    = '';        // filesystem, database, memcached, ...
    public $cachedir   = 'var/cache/output';
    public $type       = '';        // page, block, template, ...
    public $code       = '';        // URL factors et al.
    public $size       = null;
    public $numitems   = 0;
    public $compressed = false;
    public $sizelimit  = 10000000;
    public $reached    = null;
    public $expire     = 0;
    public $logfile    = null;
    public $logsize    = 2000000;   // for each logfile
    public $modtime    = 0;         // last modification time

    /**
     * Factory class method for cache storage (only 'storage' is semi-required)
     * 
     * @param string  $storage the storage you want (filesystem, database or memcached)
     * @param string  $type the type of cached data (page, block, template, ...)
     * @param string  $cachedir the path to the cache directory (for filesystem)
     * @param string  $code the cache code (for URL factors et al.) if it's fixed
     * @param integer $expire the expiration time for this data
     * @param integer $sizelimit the maximum size for the cache storage
     * @param string  $logfile the path to the logfile for HITs and MISSes
     * @param integer $logsize the maximum size of the logfile
     * @return object the specified cache storage
     */
    public static function getCacheStorage(array $args = array())
    {
        if (empty($args['storage'])) {
            $args['storage'] = 'filesystem';
        }
        switch ($args['storage'])
        {
            case 'database':
                sys::import('xaraya.caching.storage.database');
                $classname = 'xarCache_Database_Storage';
                break;

            case 'memcached':
                if (extension_loaded('memcache')) {
                    sys::import('xaraya.caching.storage.memcached');
                    $classname = 'xarCache_MemCached_Storage';
                } else {
                    sys::import('xaraya.caching.storage.filesystem');
                    $classname = 'xarCache_FileSystem_Storage';
                }
                break;

            case 'mmcache':
                if (function_exists('mmcache')) {
                    sys::import('xaraya.caching.storage.mmcache');
                    $classname = 'xarCache_MMCache_Storage';
                } else {
                    sys::import('xaraya.caching.storage.filesystem');
                    $classname = 'xarCache_FileSystem_Storage';
                }
                break;

            case 'eaccelerator':
                if (function_exists('eaccelerator')) {
                    sys::import('xaraya.caching.storage.eaccelarator');
                    $classname = 'xarCache_eAccelerator_Storage';
                } else {
                    sys::import('xaraya.caching.storage.filesystem');
                    $classname = 'xarCache_FileSystem_Storage';
                }
                break;

            case 'xcache':
                if (extension_loaded('xcache')) {
                    sys::import('xaraya.caching.storage.xcache');
                    $classname = 'xarCache_XCache_Storage';
                } else {
                    sys::import('xaraya.caching.storage.filesystem');
                    $classname = 'xarCache_FileSystem_Storage';
                }
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
     * @todo using an args array here is taking the easy way out, lets define a proper interface
     */
    public function __construct(array $args = array())
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
        $this->cachedir = realpath($this->cachedir);
    }

    public function setCode($code = '')
    {
        $this->code = $code;
    }

    public function setExpire($expire = 0)
    {
        $this->expire = $expire;
    }

    public function getLastModTime()
    {
        return $this->modtime;
    }

    public function isCached($key = '', $expire = 0, $log = 1)
    {
        return false;
    }

    public function getCached($key = '', $output = 0, $expire = 0)
    {
        return '';
    }

    public function setCached($key = '', $value = '', $expire = 0)
    {
    }

    public function delCached($key = '')
    {
    }

    public function flushCached($key = '')
    {
    }

    public function cleanCached($expire = 0)
    {
    }

    public function getCacheSize($countitems = false)
    {
        return $this->size;
    }

    public function getCacheItems()
    {
        return $this->numitems;
    }

    public function sizeLimitReached()
    {
        if (isset($this->reached)) {
            return $this->reached;
        }

        $lockfile = $this->cachedir . '/cache.' . $this->type . 'full';
        if (file_exists($lockfile)) {
            $value = true;
        } elseif (mt_rand(1,5) > 1) {
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

    // CHECKME: we don't need this cached variable anymore, do we ?
        if ($value && !xarCore::isCached($this->type . '.Caching', 'cleaned')) {
            $this->cleanCached();
            xarCore::setCached($this->type . '.Caching', 'cleaned', true);
        }
        return $value;
    }

    public function logStatus($status = 'MISS', $key = '')
    {
        if (empty($this->logfile) || empty($_SERVER['HTTP_HOST']) ||
            empty($_SERVER['REQUEST_URI']) || empty($_SERVER['REMOTE_ADDR'])) {
            return;
        }

        $time = time();
        $addr = !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '-';
        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        //$ref = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '-';
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

    public function saveFile($key = '', $filename = '')
    {
    }

    public function getCachedList()
    {
        return array();
    }

    public function getCachedKeys()
    {
        $list = $this->getCachedList();
        $keys = array();
        foreach ($list as $item) {
            if (empty($item['key'])) continue;
            $keys[$item['key']] = 1;
        }
        return array_keys($keys);
    }
}

?>
