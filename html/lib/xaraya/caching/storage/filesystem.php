<?php
/**
 * @package core
 * @subpackage caching
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */
/**
 * Cache data on the filesystem (can also be a ramdisk/tmpfs/shmfs/...)
 */

class xarCache_FileSystem_Storage extends xarCache_Storage implements ixarCache_Storage
{
    public $dir = '';
    public $blksize = 0;
    public $bsknown = false;

    public function __construct(Array $args = array())
    {
        parent::__construct($args);

        if ($this->type == 'template') {
            // CHECKME: this assumes that we create this instance after loading xarTemplate.php
            $this->dir = sys::varpath() . XARCORE_TPL_CACHEDIR;

        } else {
            $this->dir = realpath($this->cachedir . '/' . $this->type);
        }

        // CHECKME: we don't use 'type/namespace' as prefix for the cache keys here,
        //          because the output cache directories are already split by type
        $this->prefix = $this->namespace;

        $this->storage = 'filesystem';
    }

    public function setNamespace($namespace = '')
    {
        $this->namespace = $namespace;
        // the default prefix for the cache keys will be 'type/namespace', except in filesystem (for now)
        $this->prefix = $this->namespace;
    }

    public function isCached($key = '', $expire = 0, $log = 1)
    {
        if (empty($expire)) {
            $expire = $this->expire;
        }
        $cache_key = $this->getCacheKey($key);

        $cache_file = $this->dir . '/' . $cache_key . '.php';

        if (// the file is present AND
            file_exists($cache_file) &&
            // the file has something in it AND
            filesize($cache_file) > 0 &&
            // (cached files don't expire OR this file hasn't expired yet) AND
            ($expire == 0 ||
             filemtime($cache_file) > time() - $expire)) {

            $this->modtime = filemtime($cache_file);
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

        $cache_file = $this->dir . '/' . $cache_key . '.php';

        if ($this->type == 'template') {
            // CHECKME: the file will be included in xarTemplate.php ?
            $data = '';

        } elseif ($output) {
            // output the file directly to the browser
            @readfile($cache_file);
            return true;

        } elseif (function_exists('file_get_contents')) {
            $data = file_get_contents($cache_file);

        } else {
            $data = '';
            $file = @fopen($cache_file, "rb");
            if ($file) {
                while (!feof($file)) $data .= fread($file, 1024);
                fclose($file);
            }
        }
        return $data;
    }

    public function setCached($key = '', $value = '', $expire = 0)
    {
        if (empty($expire)) {
            $expire = $this->expire;
        }
        $cache_key = $this->getCacheKey($key);

        $tmp_file = $this->dir . '/' . $cache_key; // without extension
        $cache_file = $this->dir . '/' . $cache_key . '.php';

        $fp = @fopen($tmp_file, "w");
        if (!empty($fp)) {
            @fwrite($fp, $value);
            @fclose($fp);
            // rename() doesn't overwrite existing files in Windows
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                @copy($tmp_file, $cache_file);
                @unlink($tmp_file);
            } else {
                @rename($tmp_file, $cache_file);
            }
        }
    }

    public function delCached($key = '')
    {
        $cache_key = $this->getCacheKey($key);

        $cache_file = $this->dir . '/' . $cache_key . '.php';

        if (file_exists($cache_file)) {
            @unlink($cache_file);
        }
    }

    public function flushCached($key = '')
    {
        // add namespace prefix (not the type here)
        if (!empty($this->namespace)) {
            $key = $this->namespace . $key;
        }

        $this->_flushDirCached($key, $this->dir);

        // check the cache size and clear the lockfile set by sizeLimitReached()
        $lockfile = $this->cachedir . '/cache.' . $this->type . 'full';
        if ($this->getCacheSize() < $this->sizelimit && file_exists($lockfile)) {
            @unlink($lockfile);
        }
    }

    public function doGarbageCollection($expire = 0)
    {
        $time = time() - ($expire + 60); // take some margin here

        if ($handle = @opendir($this->dir)) {
            while (($file = readdir($handle)) !== false) {
                $cache_file = $this->dir . '/' . $file;
                if ((filemtime($cache_file) < $time) &&
                    (strpos($file, '.php') !== false)) {
                    @unlink($cache_file);
                }
            }
            closedir($handle);
        }
    }

    public function getCacheInfo()
    {
        if (empty($this->blksize)) {
            $dirstat = stat($this->dir);
            // we know the filesystem blocksize, use this to better calc the disk usage
            if ($dirstat['blksize'] > 0) {
                $this->blksize = $dirstat['blksize'] / 8;
                $this->bsknown = true;
            } else { // just count of the used bytes
                $this->blksize = 1;
                $this->bsknown = false;
            }
        }

        $this->size = 0;
        $this->items = 0;
        $this->modtime = 0;
        $this->size = $this->_getCacheDirSize($this->dir, true);

        return array('size'    => $this->size,
                     'items'   => $this->items,
                     'hits'    => $this->hits,
                     'misses'  => $this->misses,
                     'modtime' => $this->modtime);
    }

    public function saveFile($key = '', $filename = '')
    {
        if (empty($filename)) return;

        $cache_key = $this->getCacheKey($key);

        $cache_file = $this->dir . '/' . $cache_key . '.php';

        // we use a direct file copy here, instead of getting the value again (cfr. session-less page caching)
        if (file_exists($cache_file)) {
            @copy($cache_file, $filename);
        }
    }

    /**
     * private function for use in flushCached()
     */
    private function _flushDirCached($key = '', $dir = false)
    {
        if (!$dir || !is_dir($dir)) {
            return;
        }

        if (substr($dir,-1) != "/") $dir .= "/";
        if ($dirId = opendir($dir)) {
            while (($item = readdir($dirId)) !== false) {
                if ($item[0] != '.') {
                    if (is_dir($dir . $item)) {
                        $this->_flushDirCached($key, $dir . $item);
                    } else {
                        if ((preg_match("#$key#", $item)) &&
                            (strpos($item, '.php') !== false)) {
                            @unlink($dir . $item);
                        }
                    }
                }
            }
        }
        closedir($dirId);
    }

    /**
     * private function for use in getCacheSize()
     */
    private function _getCacheDirSize($dir = false, $countitems = false)
    {
        $size = 0;
        $count = 0;

        if ($this->bsknown) {
            if ($dir && is_dir($dir)) {
                if (substr($dir,-1) != "/") $dir .= "/";
                if ($dirId = opendir($dir)) {
                    while (($item = readdir($dirId)) !== false) {
                        if ($item != "." && $item != "..") {
                            $filestat = stat($dir . $item);
                            $size += ($filestat['blocks'] * $this->blksize);
                            if (is_dir($dir . $item)) {
                                $size += $this->_getCacheDirSize($dir . $item, $countitems);
                            } elseif ($countitems) {
                                $count++;
                                if ($this->modtime < $filestat['mtime']) {
                                    $this->modtime = $filestat['mtime'];
                                }
                            }
                        }
                    }
                    closedir($dirId);
                }
            }
        } else {
            if ($dir && is_dir($dir)) {
                if (substr($dir,-1) != "/") $dir .= "/";
                if ($dirId = opendir($dir)) {
                    while (($item = readdir($dirId)) !== false) {
                        if ($item != "." && $item != "..") {
                            if (is_dir($dir . $item)) {
                                $size += $this->_getCacheDirSize($dir . $item, $countitems);
                            } else {
                                $size += filesize($dir . $item);
                                if ($countitems) {
                                    $count++;
                                    $time = filemtime($dir . $item);
                                    if ($this->modtime < $time) {
                                        $this->modtime = $time;
                                    }
                                }
                            }
                        }
                    }
                    closedir($dirId);
                }
            }
        }
        if ($countitems) {
            $this->items = $this->items + $count;
        }
        return $size;
    }

    public function getCachedList()
    {
        $list = array();
        if ($handle = @opendir($this->dir)) {
            while (($file = readdir($handle)) !== false) {
                // filter out the keys that don't start with the right type/namespace prefix
                if (!empty($this->prefix) && strpos($file, $this->prefix) !== 0) continue;
            // CHECKME: this assumes the code is always hashed
                if (!preg_match('/^(.*)-(\w*)\.php$/',$file,$matches)) {
                    continue;
                }
                $key = $matches[1];
                $code = $matches[2];
                $cache_file = $this->dir . '/' . $file;
                $time = filemtime($cache_file);
                $size = filesize($cache_file);
                $check = '';
                // remove the prefix from the key
                if (!empty($this->prefix)) $key = str_replace($this->prefix,'',$key);
                $list[] = array('key'   => $key,
                                'code'  => $code,
                                'time'  => $time,
                                'size'  => $size,
                                'check' => $check);
            }
            closedir($handle);
        }
        return $list;
    }

}

?>