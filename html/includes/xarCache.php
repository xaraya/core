<?php
/**
 * File: $Id$
 * 
 * Xaraya Web Interface Entry Point
 * 
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Page/Block Caching
 * @author mikespub
 */

/**
 * Initialise the caching options
 *
 * @returns true on success, false if trouble is encountered
 * @todo    consider the use of a shutdownhandler for cache maintenance
 */
function xarCache_init($args)
{
    extract($args);

// TODO: clean up all these globals and put them e.g. into a single array

    global $xarOutput_cacheCollection;
    global $xarOutput_cacheTheme;
    global $xarOutput_cacheSizeLimit;
    global $xarPage_cacheTime;
    global $xarPage_cacheDisplay;
    global $xarPage_cacheShowTime;
    global $xarPage_cacheExpireHeader;
    global $xarPage_cacheGroups;
    global $xarPage_cacheHookedOnly;
    global $xarBlock_cacheTime;
    global $xarPage_autoCachePeriod;
    global $xarPage_sessionLess;

    if (!include_once('var/cache/config.caching.php')) {
        // if the config file is missing, turn caching off
        @unlink($cacheDir . '/cache.touch');
        return FALSE;
    }

    $xarOutput_cacheCollection = realpath($cacheDir);
    $xarOutput_cacheTheme = isset($cachingConfiguration['Output.DefaultTheme']) ?
        $cachingConfiguration['Output.DefaultTheme'] : '';
    $xarOutput_cacheSizeLimit = isset($cachingConfiguration['Output.SizeLimit']) ?
        $cachingConfiguration['Output.SizeLimit'] : 2097152;
    $xarPage_cacheTime = isset($cachingConfiguration['Page.TimeExpiration']) ?
        $cachingConfiguration['Page.TimeExpiration'] : 1800;
    $xarPage_cacheDisplay = isset($cachingConfiguration['Page.DisplayView']) ?
        $cachingConfiguration['Page.DisplayView'] : 0;
    $xarPage_cacheShowTime = isset($cachingConfiguration['Page.ShowTime']) ?
        $cachingConfiguration['Page.ShowTime'] : 1;
    $xarPage_cacheExpireHeader = isset($cachingConfiguration['Page.ExpireHeader']) ?
        $cachingConfiguration['Page.ExpireHeader'] : 1;
    $xarPage_cacheGroups = isset($cachingConfiguration['Page.CacheGroups']) ?
        $cachingConfiguration['Page.CacheGroups'] : '';
    $xarPage_cacheHookedOnly = isset($cachingConfiguration['Page.HookedOnly']) ?
        $cachingConfiguration['Page.HookedOnly'] : 0;
    $xarPage_sessionLess = isset($cachingConfiguration['Page.SessionLess']) ?
        $cachingConfiguration['Page.SessionLess'] : '';
    $xarBlock_cacheTime = isset($cachingConfiguration['Block.TimeExpiration']) ?
        $cachingConfiguration['Block.TimeExpiration'] : 7200;
    $xarPage_autoCachePeriod = isset($cachingConfiguration['AutoCache.Period']) ?
        $cachingConfiguration['AutoCache.Period'] : 0;
    
    if (file_exists($cacheDir . '/cache.pagelevel')) {
        define('XARCACHE_PAGE_IS_ENABLED',1);
        require_once('includes/caching/page.php');
        xarPage_sessionLess();
    }

    if (file_exists($cacheDir . '/cache.blocklevel')) {
        define('XARCACHE_BLOCK_IS_ENABLED',1);
        require_once('includes/caching/block.php');
    }

    // Subsystem initialized, register a handler to run when the request is over
    register_shutdown_function ('xarCache__shutdown_handler');
    return true;
}

/**
 * Shutdown handler for xarCache subsystem
 *
 * @access private
 *
 */
function xarCache__shutdown_handler()
{
    //xarLogMessage("xarCache shutdown handler");
}

/**
 * functions providing page caching
 *
 * Example :
 *
 * if (xarPageIsCached('MyCache', 'myvar')) {
 *     $var = xarPageGetCached('MyCache', 'myvar');
 * }
 * ...
 * xarPageSetCached('MyCache', 'myvar', 'this value');
 * ...
 * xarOutputDelCached('MyCache', 'myvar');
 * ...
 * xarOutputFlushCached('MyCache');
 * ...
 *
 */

/**
 * Set the contents of some output in the cache
 *
 * @access public
 * @param  string $cacheKey
 * @param  string $cache_file
 * @param  string $cacheType
 * @param  string $value
 *
 */
function xarOutputSetCached($cacheKey, $cache_file, $cacheType, $value)
{
    global $xarOutput_cacheCollection, ${'xar' . $cacheType . '_cacheCode'};

    $tmp_cache_file = tempnam($xarOutput_cacheCollection . '/' . strtolower($cacheType),
                              "$cacheKey-${'xar' . $cacheType . '_cacheCode'}");
    $fp = @fopen($tmp_cache_file, "w");
    if (!empty($fp)) {

        //if ($cacheType == 'Block') { $value .= 'Cached Block'; }// This line is used for testing

        @fwrite($fp, $value);
        @fclose($fp);
        // rename() doesn't overwrite existing files in Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            @copy($tmp_cache_file, $cache_file);
            @unlink($tmp_cache_file);
        } else {
            @rename($tmp_cache_file, $cache_file);
        }
        
        // create another copy for session-less page caching if necessary
        if (($cacheType == 'Page') && (!empty($GLOBALS['xarPage_cacheNoSession']))) {
            $cacheKey = 'static';
            $xarPage_cacheCode = md5($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
            $cache_file2 = "$xarOutput_cacheCollection/page/$cacheKey-$xarPage_cacheCode.php";
        // Note that if we get here, the first-time visitor will receive a session cookie,
        // so he will no longer benefit from this himself ;-)
            @copy($cache_file, $cache_file2);
        }
    }
}

/**
 * delete a cached file
 *
 * @access public
 * @param string $cacheKey the key identifying the particular cache you want to
 *                         access
 * @param string $name     the name of the file in that particular cache
 * @returns void
 */
function xarOutputDelCached($cacheKey, $name)
{
    global $xarOutput_cacheCollection;
    // TODO: check if we don't need to work with $GLOBALS here for some PHP ver
    if (isset($xarOutput_cacheCollection[$cacheKey][$name])) {
        unset($xarOutput_cacheCollection[$cacheKey][$name]);
    }
}

/**
 * flush a particular cache (e.g. when a new item is created)
 *
 * @access  public
 * @param   string $cacheKey the key identifying the particular cache you want
 *                           to wipe out
 * @returns void
 */
function xarOutputFlushCached($cacheKey, $dir = false)
{
    global $xarOutput_cacheCollection, $xarOutput_cacheSizeLimit;
    
    $lockfile = $xarOutput_cacheCollection . '/cache.full';
    
    if (empty($dir)) {
        $dir = $xarOutput_cacheCollection;
    }

    if ($dir && is_dir($dir)) {
        if (substr($dir,-1) != "/") $dir .= "/";
        if ($dirId = opendir($dir)) {
            while (($item = readdir($dirId)) !== FALSE) {
                if ($item[0] != '.') {
                    if (is_dir($dir . $item)) {
                        xarOutputFlushCached($cacheKey, $dir . $item);
                    } else {
                        if ((preg_match("#$cacheKey#", $item)) &&
                            (strpos($item, '.php') !== false)) {
                            @unlink($dir . $item);
                        }
                    }
                }
            }
        }
        closedir($dirId);
    }
    
    if (xarCacheGetDirSize($xarOutput_cacheCollection) < $xarOutput_cacheSizeLimit &&
        file_exists($lockfile)) {
        @unlink($lockfile);
    }
         
}

/**
 * clean the cache of old entries
 * note: for blocks, this only gets called when the cache size limit has been
 *       reached, and when called by blocks, all cached blocks are flushed.
 *
 * @access  protected
 * @param   string $cacheType
 * @returns void
 */
function xarCache_CleanCached($cacheType)
{
    global $xarOutput_cacheCollection, $xarOutput_cacheSizeLimit, ${'xar' . $cacheType . '_cacheTime'};

    $sl_cacheType = strtolower($cacheType);
    $cacheOutputTypeDir = $xarOutput_cacheCollection . '/' . $sl_cacheType;
    $touch_file = $xarOutput_cacheCollection . '/cache.' . $sl_cacheType . 'level';
    $lockfile = $xarOutput_cacheCollection . '/cache.full';

    // If the cache type is Block, then the cache is full so we flush the blocks
    // to make more room
    if ($cacheType == 'Block') {
        xarOutputFlushCached('', $cacheOutputTypeDir);
    }

    // If the cache type has already been cleaned within the expiration time,
    // don't bother checking again
    if (${'xar' . $cacheType . '_cacheTime'} == 0 ||
        (file_exists($touch_file) &&
         filemtime($touch_file) > time() - ${'xar' . $cacheType . '_cacheTime'})
        ) {
        return;
    }
    if (!@touch($touch_file)) {
        // hmm, somthings amiss... better let the administrator know,
        // without disrupting the site
        error_log('Error from Xaraya::xarCache::xarCache_CleanCached
                  - web process can not touch ' . $touch_file);
    }

    if ($handle = @opendir($cacheOutputTypeDir)) {
        while (($file = readdir($handle)) !== false) {
            $cache_file = $cacheOutputTypeDir . '/' . $file;
            if (filemtime($cache_file) < time() - (${'xar' . $cacheType . '_cacheTime'} + 60) &&
                (strpos($file, '.php') !== false)) {
                @unlink($cache_file);
            }
        }
        closedir($handle);
    }

    if (xarCacheGetDirSize($xarOutput_cacheCollection) < $xarOutput_cacheSizeLimit &&
        file_exists($lockfile)) {
        @unlink($lockfile);
    }
}

/**
 * helper function to determine if the cache size limit has been reached
 *
 * @access protected
 * @param  string  $dir
 * @param  string  $cacheType
 * @return boolean
 * @author jsb
 */
function xarCache_SizeLimit($dir = FALSE, $cacheType)
{
    global $xarOutput_cacheCollection, $xarOutput_cacheSizeLimit;
    
    if (empty($dir)) {
       $dir = $xarOutput_cacheCollection;
    }
    
    static $value = NULL;
    $lockfile = $xarOutput_cacheCollection . '/cache.full';
    
    if (!isset($value)) {
        if (file_exists($lockfile)) {
            $value = TRUE;
        } elseif (mt_rand(1,5) > 1) {
            // on average, 4 out of 5 pages go by without checking
            $value = FALSE;
        } else {
    
            static $size = 0;
            
            if (empty($size)) {
                $size = xarCacheGetDirSize($dir);
            }
            if($size >= $xarOutput_cacheSizeLimit) {
                $value = TRUE;
                @touch($lockfile);
            } else {
                $value = FALSE;
            }
        }
    }
    if ($value && !xarCore_IsCached($cacheType . '.Caching', 'cleaned')) {
        xarCache_CleanCached($cacheType);
        xarCore_SetCached($cacheType . '.Caching', 'cleaned', TRUE);
    }
    return $value;
}

/**
 * calculate the size of the cache
 *
 * @access public
 * @param  string  $dir
 * @param  string  $cacheType
 * @return float
 * @author nospam@jusunlee.com 
 * @author laurie@oneuponedown.com 
 * @author jsb
 * @todo   $dir changes type
 */
function xarCacheGetDirSize($dir = FALSE)
{
    static $blksize = 0;
    static $bsknown = FALSE;
    $size = 0;

    if (empty($blksize)) {
        $dirstat = stat($dir);
        // we know the filesystem blocksize, use this to better calc the disk usage
        if ($dirstat['blksize'] > 0) {
            $blksize = $dirstat['blksize'] / 8;
            $bsknown = TRUE;
        } else { // just count of the used bytes
            $blksize = 1;
            $bsknown = FALSE;
        }
    }

    if($bsknown) {
        if ($dir && is_dir($dir)) {
            if (substr($dir,-1) != "/") $dir .= "/";
            if ($dirId = opendir($dir)) {
                while (($item = readdir($dirId)) !== FALSE) {
                    if ($item != "." && $item != "..") {
                            $filestat = stat($dir . $item);
                            $size += ($filestat['blocks'] * $blksize);
                        if (is_dir($dir . $item)) {
                            $size += xarCacheGetDirSize($dir . $item);
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
                            $size += xarCacheGetDirSize($dir . $item);
                        } else {
                            $size += filesize($dir . $item);
                        }
                    }
                }
                closedir($dirId);
            }
        }
    }

    return $size;
}

/**
 * get the parent group ids of the current user (with minimal overhead)
 *
 * @access private
 * @return array of parent gids
 * @todo avoid DB lookup by passing groups via cookies ?
 * @todo Note : don't do this if admins get cached too :)
 */
function xarCache_getParents()
{
    $currentuid = xarSessionGetVar('uid');
    if (xarCore_IsCached('User.Variables.'.$currentuid, 'parentlist')) {
        return xarCore_GetCached('User.Variables.'.$currentuid, 'parentlist');
    }
    $systemPrefix = xarDBGetSystemTablePrefix();
    $rolemembers = $systemPrefix . '_rolemembers';
    $dbconn =& xarDBGetConn();
    $query = "SELECT xar_parentid FROM $rolemembers WHERE xar_uid = ?";
    $result =& $dbconn->Execute($query,array($currentuid));
    if (!$result) return;
    $gidlist = array();
    while(!$result->EOF) {
        list($parentid) = $result->fields;
        $gidlist[] = $parentid;
        $result->MoveNext();
    }
    $result->Close();
    xarCore_SetCached('User.Variables.'.$currentuid, 'parentlist',$gidlist);
    return $gidlist;
}

?>
