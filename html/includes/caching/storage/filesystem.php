<?php

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

    function isCached($key = '')
    {
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
            ($this->expire == 0 ||
             filemtime($cache_file) > time() - $this->expire)) {

            $this->logStatus('HIT', $oldkey);
            return true;

        } else {
            $this->logStatus('MISS', $oldkey);
            return false;
        }
    }

    function getCached($key = '')
    {
        if (!empty($this->code)) {
            $key .= '-' . $this->code;
        }

        $cache_file = $this->dir . '/' . $key . '.php';

        if ($this->type == 'template') {
            // CHECKME: the file will be included in xarTemplate.php ?
            $data = '';

        } elseif ($this->type == 'page') {
            // output the file directly to the browser
            @readfile($cache_file);
            $data = '';

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

    function setCached($key = '', $value = '')
    {
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
/* CHECKME: leave this in page ?
            // create another copy for session-less page caching if necessary
            if (($this->type == 'page') && (!empty($GLOBALS['xarPage_cacheNoSession']))) {
                $key = 'static';
                $code = md5($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
                $cache_file2 = $this->dir . "/$key-$code.php";
                // Note that if we get here, the first-time visitor will receive a session cookie,
                // so he will no longer benefit from this himself ;-)
                @copy($cache_file, $cache_file2);
            }
*/
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
    }

    function cleanCached()
    {
        if (empty($this->expire)) {
            // TODO: delete oldest entries if we're at the size limit ?
            return;
        }

        $time = time() - ($this->expire + 60); // take some margin here

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

}

?>
