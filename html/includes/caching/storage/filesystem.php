<?php

/**
 * Cache data on the filesystem (can also be a ramdisk/tmpfs/shmfs/...)
 */

class xarCache_FileSystem_Storage extends xarCache_Storage
{
    var $dir = '';
    var $blksize = 0;
    var $bsknown = FALSE;

    function xarCache_FileSystem_Storage($args = array())
    {
        $this->xarCache_Storage($args);

        if ($this->type == 'template') {
        // CHECKME: this assumes that we create this instance after loading xarTemplate.php
            $this->dir = XAR_TPL_CACHE_DIR;

        } else {
            $this->dir = realpath($this->cachedir . '/' . $this->type);
        }

        $this->storage = 'filesystem';
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

        $cache_file = $this->dir . '/' . $key . '.php';

        if (// the file is present AND
            file_exists($cache_file) &&
            // the file has something in it AND
            filesize($cache_file) > 0 &&
            // (cached files don't expire OR this file hasn't expired yet) AND
            ($expire == 0 ||
             filemtime($cache_file) > time() - $expire)) {

            $this->modtime = filemtime($cache_file);
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

        $cache_file = $this->dir . '/' . $key . '.php';

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

    function setCached($key = '', $value = '', $expire = 0)
    {
        if (empty($expire)) {
            $expire = $this->expire;
        }
        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }

        $tmp_file = $this->dir . '/' . $key; // without extension
        $cache_file = $this->dir . '/' . $key . '.php';

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

    function delCached($key = '')
    {
        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }

        $cache_file = $this->dir . '/' . $key . '.php';

        if (file_exists($cache_file)) {
            @unlink($cache_file);
        }
    }

    function flushCached($key = '')
    {
        $this->_flushDirCached($key, $this->dir);

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
            error_log('Error from Xaraya::xarCache::storage::filesystem
                      - web process can not touch ' . $touch_file);
        }

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

        // check the cache size and clear the lockfile set by sizeLimitReached()
        $lockfile = $this->cachedir . '/cache.' . $this->type . 'full';
        if ($this->getCacheSize() < $this->sizelimit && file_exists($lockfile)) {
            @unlink($lockfile);
        }
    }

    function getCacheSize($countitems = false)
    {
        if (empty($this->blksize)) {
            $dirstat = stat($this->dir);
            // we know the filesystem blocksize, use this to better calc the disk usage
            if ($dirstat['blksize'] > 0) {
                $this->blksize = $dirstat['blksize'] / 8;
                $this->bsknown = TRUE;
            } else { // just count of the used bytes
                $this->blksize = 1;
                $this->bsknown = FALSE;
            }
        }

        if ($countitems) {
            $this->numitems = 0;
        }

        $this->size = $this->_getCacheDirSize($this->dir, $countitems);

        return $this->size;
    }

    function saveFile($key = '', $filename = '')
    {
        if (empty($filename)) return;

        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }

        $cache_file = $this->dir . '/' . $key . '.php';

        if (file_exists($cache_file)) {
            @copy($cache_file, $filename);
        }
    }

    /**
     * private function for use in flushCached()
     */
    function _flushDirCached($key = '', $dir = false)
    {
        if (!$dir || !is_dir($dir)) {
            return;
        }

        if (substr($dir,-1) != "/") $dir .= "/";
        if ($dirId = opendir($dir)) {
            while (($item = readdir($dirId)) !== FALSE) {
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
    function _getCacheDirSize($dir = FALSE, $countitems = false)
    {
        $size = 0;
        $count = 0;

        if ($this->bsknown) {
            if ($dir && is_dir($dir)) {
                if (substr($dir,-1) != "/") $dir .= "/";
                if ($dirId = opendir($dir)) {
                    while (($item = readdir($dirId)) !== FALSE) {
                        if ($item != "." && $item != "..") {
                            $filestat = stat($dir . $item);
                            $size += ($filestat['blocks'] * $this->blksize);
                            if (is_dir($dir . $item)) {
                                $size += $this->_getCacheDirSize($dir . $item, $countitems);
                            } elseif ($countitems) {
                                $count++;
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
                    while (($item = readdir($dirId)) !== FALSE) {
                        if ($item != "." && $item != "..") {
                            if (is_dir($dir . $item)) {
                                $size += $this->_getCacheDirSize($dir . $item, $countitems);
                            } else {
                                $size += filesize($dir . $item);
                                if ($countitems) {
                                    $count++;
                                }
                            }
                        }
                    }
                    closedir($dirId);
                }
            }
        }
        if ($countitems) {
            $this->numitems = $this->numitems + $count;
        }
        return $size;
    }

    function getCachedList()
    {
        $list = array();
        if ($handle = @opendir($this->dir)) {
            while (($file = readdir($handle)) !== false) {
                if (!preg_match('/^(.*)-(\w*)\.php$/',$file,$matches)) {
                    continue;
                }
                $key = $matches[1];
                $code = $matches[2];
                $cache_file = $this->dir . '/' . $file;
                $time = filemtime($cache_file);
                $size = filesize($cache_file);
                $check = '';
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
