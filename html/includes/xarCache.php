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

    global $xarOutput_cacheCollection;
    global $xarOutput_cacheTheme;
    global $xarOutput_cacheSizeLimit;
    global $xarPage_cacheTime;
    global $xarPage_cacheDisplay;
    global $xarPage_cacheShowTime;
    global $xarPage_cacheExpireHeader;
    global $xarPage_cacheGroups;
    global $xarBlock_cacheTime;

    if (!@include_once('var/cache/config.caching.php')) {
        // if the config file is missing, turn caching off
        @unlink($cacheDir . '/cache.touch');
        return FALSE;
    }

    $xarOutput_cacheCollection  = $cacheDir;
    $xarOutput_cacheTheme       = isset($cachingConfiguration['Output.DefaultTheme']) ?
                                    $cachingConfiguration['Output.DefaultTheme'] : '';
    $xarOutput_cacheSizeLimit   = isset($cachingConfiguration['Output.SizeLimit']) ?
                                    $cachingConfiguration['Output.SizeLimit'] : 2097152;
    $xarPage_cacheTime          = isset($cachingConfiguration['Page.TimeExpiration']) ?
                                    $cachingConfiguration['Page.TimeExpiration'] : 1800;
    $xarPage_cacheDisplay       = isset($cachingConfiguration['Page.DisplayView']) ?
                                    $cachingConfiguration['Page.DisplayView'] : 0;
    $xarPage_cacheShowTime      = isset($cachingConfiguration['Page.ShowTime']) ?
                                    $cachingConfiguration['Page.ShowTime'] : 1;
    $xarPage_cacheExpireHeader  = isset($cachingConfiguration['Page.ExpireHeader']) ?
                                    $cachingConfiguration['Page.ExpireHeader'] : 1;
    $xarPage_cacheGroups        = isset($cachingConfiguration['Page.CacheGroups']) ?
                                    $cachingConfiguration['Page.CacheGroups'] : '';
    $xarBlock_cacheTime         = isset($cachingConfiguration['Block.TimeExpiration']) ?
                                    $cachingConfiguration['Block.TimeExpiration'] : 7200;

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
 * check if the content of a page is available in cache or not
 *
 * @access public
 * @param key the key identifying the particular cache you want to access
 * @param name the name of the page in that particular cache
 * @returns bool
 * @return true if the page is available in cache, false if not
 */
function xarPageIsCached($cacheKey, $name = '')
{
    global $xarOutput_cacheCollection,
           $xarPage_cacheTime,
           $xarOutput_cacheTheme,
           $xarPage_cacheDisplay,
           $xarPage_cacheExpireHeader,
           $xarPage_cacheCode,
           $xarPage_cacheGroups;

    $xarTpl_themeDir = xarTplGetThemeDir();

    $page = xarServerGetVar('HTTP_HOST') . $xarTpl_themeDir .
            xarUserGetNavigationLocale();

    // add user groups as a factor if necessary
    // Note : we don't share the cache between groups or with anonymous here
    if (!empty($xarPage_cacheGroups) && xarUserIsLoggedIn()) {
        $gidlist = xarCache_getParents();
        $page .= join(';',$gidlist);
    }

    $page .= xarServerGetVar('REQUEST_URI');
    $param = xarServerGetVar('QUERY_STRING');
    if (!empty($param)) {
        $page .= '?' . $param;
    }
    // use this global instead of $cache_file so we can cache several things
    // based on different $cacheKey (and $name if necessary) in one page request
    // - e.g. for module and block caching
    $xarPage_cacheCode = md5($page);

    // CHECKME: use $name for something someday ?
    $cache_file = "$xarOutput_cacheCollection/$cacheKey-$xarPage_cacheCode.php";

    if (strpos($cacheKey, '-user-') &&
        !strpos($cacheKey, '-search') &&
        ((($xarPage_cacheDisplay != 1) && !strpos($cacheKey, '-display')) ||
         ($xarPage_cacheDisplay == 1)) &&
        xarServerGetVar('REQUEST_METHOD') == 'GET' &&
        (empty($xarOutput_cacheTheme) ||
         strpos($xarTpl_themeDir, $xarOutput_cacheTheme)) &&
        file_exists($cache_file) &&
        filesize($cache_file) > 0 &&
        ($xarPage_cacheTime == 0 ||
         filemtime($cache_file) > time() - $xarPage_cacheTime) &&
        xarPage_checkUserCaching()) {

        // start 304 test
        $mod = filemtime($cache_file);
        // doesn't seem to be taken into account ?
        $etag = $xarPage_cacheCode . $mod;
        header("ETag: $etag");
        $match = xarServerGetVar('HTTP_IF_NONE_MATCH');
        if (!empty($match) && $match == $etag) {
            header('HTTP/1.0 304');
            exit;
        } else {
            $since = xarServerGetVar('HTTP_IF_MODIFIED_SINCE');
            if (!empty($since) && strtotime($since) >= $mod) {
                header('HTTP/1.0 304');
                exit;
            }
        }
        if (!empty($xarPage_cacheExpireHeader)) {
            // this tells clients and proxies that this file is good until local
            // cache file is due to expire
            header("Expires: " .
                   gmdate("D, d M Y H:i:s", $mod + $xarPage_cacheTime) .
                   " GMT");
        }
        header("Last-Modified: " . gmdate("D, d M Y H:i:s", $mod) . " GMT");
        // we can't use this after session_start()
        //session_cache_limiter('public');
        header("Cache-Control: public, max-age=" . $xarPage_cacheTime);
        // PHP doesn't set the Pragma header when sending back a cookie
        if (isset($_COOKIE['XARAYASID'])) {
            header("Pragma: public");
        } else {
            header("Pragma:");
        }
        // end 304 test

        return true;
    } else {
        return false;
    }
}

/**
 * Check wheter a block is cached
 *
 * @access public
 * @param  array $args($cacheKey,$blockDynamics, $blockPermissions, $name = '')
 * @return bool
 */
function xarBlockIsCached($args)
{
    global $xarOutput_cacheCollection,
           $xarBlock_cacheCode,
           $xarBlock_cacheTime,
           $blockCacheExpireTime,
           $xarBlock_noCache;
    
    $xarTpl_themeDir = xarTplGetThemeDir();
    
    extract($args);

    if (xarCore_IsCached('Blocks.Caching', 'settings')) {
        $blocks = xarCore_GetCached('Blocks.Caching', 'settings');
    } else {
        $systemPrefix = xarDBGetSystemTablePrefix();
        $blocksettings = $systemPrefix . '_cache_blocks';
        $dbconn =& xarDBGetConn();
        $query = "SELECT xar_bid,
                         xar_nocache,
                         xar_page,
                         xar_user,
                         xar_expire
                 FROM $blocksettings";
        $result =& $dbconn->Execute($query);
        if (!$result) return;
        $blocks = array();
        while (!$result->EOF) {
            list ($bid,
                  $noCache,
                  $pageShared,
                  $userShared,
                  $blockCacheExpireTime) = $result->fields;
            $blocks[$bid] = array('bid'         => $bid,
                                  'nocache'     => $noCache,
                                  'pageshared'  => $pageShared,
                                  'usershared'  => $userShared,
                                  'cacheexpire' => $blockCacheExpireTime);
            $result->MoveNext();
        }
        $result->Close();
        xarCore_SetCached('Blocks.Caching', 'settings', $blocks);
    }
    if (isset($blocks[$blockid])) {
        $noCache = $blocks[$blockid]['nocache'];
        $pageShared = $blocks[$blockid]['pageshared'];
        $userShared = $blocks[$blockid]['usershared'];
        $blockCacheExpireTime = $blocks[$blockid]['cacheexpire'];
    }

    if (!empty($noCache)) {
        $xarBlock_noCache = 1;
        return false;
    }
    if (empty($pageShared)) {
    	$pageShared = 0;
    }
    if (empty($userShared)) {
    	$userShared = 0;
    }
    if (!isset($blockCacheExpireTime)) {
        $blockCacheExpireTime = $xarBlock_cacheTime;
    }

    $factors = xarServerGetVar('HTTP_HOST') . $xarTpl_themeDir .
               xarUserGetNavigationLocale();

    if ($pageShared == 0) {
        $factors .= xarServerGetVar('REQUEST_URI');
        $param = xarServerGetVar('QUERY_STRING');
        if (!empty($param)) {
            $factors .= '?' . $param;
        }
    }

    if ($userShared == 2) {
        $factors .= 0;
    } elseif ($userShared == 1) {
        $gidlist = xarCache_getParents();
        $factors .= join(';',$gidlist);
    } else {
        $factors .= xarSessionGetVar('uid');
    }

    $xarBlock_cacheCode = md5($factors);

    // CHECKME: use $name for something someday ?
    $cache_file = "$xarOutput_cacheCollection/$cacheKey-$xarBlock_cacheCode.php";

    if (
        file_exists($cache_file) &&
        ($blockCacheExpireTime == 0 ||
         filemtime($cache_file) > time() - $blockCacheExpireTime)) {
        return true;
    } else {
        return false;
    }
}

/**
 * get the content of a cached page
 *
 * @access public
 * @param key the key identifying the particular cache you want to access
 * @param name the name of the page in that particular cache
 * @returns mixed
 * @return content of the page, or void if page isn't cached
 */
function xarPageGetCached($cacheKey, $name = '')
{
    global $xarOutput_cacheCollection, $xarPage_cacheCode;

    // CHECKME: use $name for something someday ?
    $cache_file = "$xarOutput_cacheCollection/$cacheKey-$xarPage_cacheCode.php";
    @readfile($cache_file);

    xarOutputCleanCached('Page');
}

/**
 * Get the output of a cached block
 *
 * @access public
 * @param  string $cacheKey
 * @param  string $name
 */
function xarBlockGetCached($cacheKey, $name = '')
{
    global $xarOutput_cacheCollection, $xarBlock_cacheCode;
    
    // CHECKME: use $name for something someday ?
    $cache_file = "$xarOutput_cacheCollection/$cacheKey-$xarBlock_cacheCode.php";
    
    if (function_exists('file_get_contents')) {
    	$blockCachedOutput = file_get_contents($cache_file);
    } else {
        $blockCachedOutput = '';
        $file = @fopen($cache_file, "rb");
        if ($file) {
            while (!feof($file)) $blockCachedOutput .= fread($file, 1024);
            fclose($file);
        }
    }

    return $blockCachedOutput;
}

/**
 * set the content of a cached page
 *
 * @access public
 * @param string $cacheKey the key identifying the particular cache you want to
 *                         access
 * @param string $name     the name of the page in that particular cache
 * @param string $value    value the new content for that page
 * @returns void
 */
function xarPageSetCached($cacheKey, $name, $value)
{
    global $xarOutput_cacheCollection,
           $xarPage_cacheTime,
           $xarOutput_cacheTheme,
           $xarPage_cacheDisplay,
           $xarPage_cacheShowTime,
           $xarOutput_cacheSizeLimit,
           $xarPage_cacheCode;
    
    $xarTpl_themeDir = xarTplGetThemeDir();
    
    if (xarCore_IsCached('Page.Caching', 'nocache')) { return; }

    // CHECKME: use $name for something someday ?
    $cache_file = "$xarOutput_cacheCollection/$cacheKey-$xarPage_cacheCode.php";

    if (strpos($cacheKey, '-user-') &&
        !strpos($cacheKey, '-search') &&
        ((($xarPage_cacheDisplay != 1) && !strpos($cacheKey, '-display')) ||
         ($xarPage_cacheDisplay == 1)) &&
        xarServerGetVar('REQUEST_METHOD') == 'GET' &&
        (empty($xarOutput_cacheTheme) ||
         strpos($xarTpl_themeDir, $xarOutput_cacheTheme)) &&
        (!file_exists($cache_file) ||
        ($xarPage_cacheTime != 0 &&
         filemtime($cache_file) < time() - $xarPage_cacheTime)) &&
        xarCacheDirSize($xarOutput_cacheCollection, 'Page') <= $xarOutput_cacheSizeLimit &&
        xarPage_checkUserCaching()) {
        $tmp_cache_file = $cache_file . '.' . getmypid();
        $fp = @fopen($tmp_cache_file, "w");
        if (!empty($fp)) {
            if ($xarPage_cacheShowTime == 1) {
                $now = xarML('Last updated on #(1)',
                             strftime('%a, %d %B %Y %H:%M:%S %Z', time()));
                $value = preg_replace('#</body>#',
                                      // TODO: set this up to be templated
                                      '<div class="xar-sub" style="text-align: center; padding: 8px; ">'.$now.'</div></body>',
                                      $value);
            }
            @fwrite($fp,$value);
            @fclose($fp);
            @rename($tmp_cache_file, $cache_file);
        }
    }
}

/**
 * Set the contents of a block in the cache
 *
 * @access public
 * @param  string $cacheKey
 * @param  string $name
 * @param  string $value
 *
 */
function xarBlockSetCached($cacheKey, $name, $value)
{
    global $xarOutput_cacheCollection,
           $xarOutput_cacheSizeLimit,
           $xarBlock_cacheCode,
           $blockCacheExpireTime,
           $xarBlock_noCache;
    
    if ($xarBlock_noCache == 1) {
        $xarBlock_noCache = '';
        return;
    }

    $xarTpl_themeDir = xarTplGetThemeDir();

    // CHECKME: use $name for something someday ?
    $cache_file = "$xarOutput_cacheCollection/$cacheKey-$xarBlock_cacheCode.php";
    if (
        xarServerGetVar('REQUEST_METHOD') == 'GET' &&
        (!file_exists($cache_file) ||
        ($blockCacheExpireTime != 0 &&
         filemtime($cache_file) < time() - $blockCacheExpireTime)) &&
        xarCacheDirSize($xarOutput_cacheCollection, 'Block', $cacheKey) <= $xarOutput_cacheSizeLimit
        ) {
        $tmp_cache_file = $cache_file . '.' . getmypid();
        $fp = @fopen($tmp_cache_file, "w");
        if (!empty($fp)) {
            //$value .= 'Cached Block';// This line is used for testing
            @fwrite($fp, $value);
            @fclose($fp);
            @rename($tmp_cache_file, $cache_file);
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
 * aliased depricated function
 */
function xarPageDelCached($cacheKey, $name)
{
	xarOutputDelCached($cacheKey, $name);
}

/**
 * flush a particular cache (e.g. when a new item is created)
 *
 * @access  public
 * @param   string $cacheKey the key identifying the particular cache you want
 *                           to wipe out
 * @returns void
 */
function xarOutputFlushCached($cacheKey)
{
    global $xarOutput_cacheCollection;

    if ($handle = @opendir($xarOutput_cacheCollection)) {
        while (($file = readdir($handle)) !== false) {
            if ((preg_match("#$cacheKey#", $file)) &&
                (strpos($file, '.php') !== false)) {
                @unlink($xarOutput_cacheCollection . '/' . $file);
            }
        }
        closedir($handle);
    }
}
/**
 * aliased depricated function
 */
function xarPageFlushCached($cacheKey)
{
	xarOutputFlushCached($cacheKey);
}

/**
 * clean the cache of old entries
 * note: for blocks, this only gets called when the cache size limit has been
 *       reached, and when called by blocks, the global cache timeout takes
 *       precedents 
 *
 * @access  public
 * @param   string $type
 * @param   string $cacheKey
 * @returns void
 */
function xarOutputCleanCached($type, $cacheKey = '')
{
    global $xarOutput_cacheCollection, ${'xar' . $type . '_cacheTime'};

    $touch_file = $xarOutput_cacheCollection . '/' . 'cache.touch';

    if (${'xar' . $type . '_cacheTime'} == 0 ||
        (file_exists($touch_file) &&
         filemtime($touch_file) > time() - ${'xar' . $type . '_cacheTime'})
        ) {
        return;
    }
    if (!@touch($touch_file)) {
        // hmm, somthings amiss... better let the administrator know,
        // without disrupting the site
    	error_log('Error from Xaraya::xarCache::xarOutputCleanCached
                  - web process can not touch ' . $touch_file);
    }
    if ($handle = @opendir($xarOutput_cacheCollection)) {
        while (($file = readdir($handle)) !== false) {
            $cache_file = $xarOutput_cacheCollection . '/' . $file;
            if (filemtime($cache_file) < time() - (${'xar' . $type . '_cacheTime'} + 60) &&
                (strpos($file, '.php') !== false) &&
                ($type == 'Block' || 
                ($type == 'Page' && strpos($file, 'block') === false))) {
                @unlink($cache_file);
            }
        }
        closedir($handle);
    }
}

/**
 * check the size of the cache
 *
 * @access public
 * @param  string  $dir
 * @param  string  $type
 * @param  string  $cacheKey
 * @return float
 * @author nospam@jusunlee.com 
 * @author laurie@oneuponedown.com 
 * @author jsb
 * @todo   $dir changes type
 * @todo   come up with a good way to determine which cacheKeys are the least
 *         important and flush them to make more space.  atime would be a
 *         possibility, but is often disabled at the filesystem
 */
function xarCacheDirSize($dir = FALSE, $type, $cacheKey = '')
{
    global $xarOutput_cacheSizeLimit;
    $size = 0;
    if ($dir && is_dir($dir)) {
        if (substr($dir,-1) != "/") $dir .= "/";
        if ($dirId = opendir($dir)) {
            while (($item = readdir($dirId)) !== FALSE) {
                if ($item != "." && $item != "..") {
                    if (is_dir($dir . $item)) {
                        $size += xarCacheDirSize($dir . $item, $type);
                    } else {
                        $size += filesize($dir . $item);
                    }
                }
            }
            closedir($dirId);
        }
    }

    if($size > $xarOutput_cacheSizeLimit) {
        xarOutputCleanCached($type);
        //xarOutputFlushCached('articles-user-view');
    }

    return $size;
}

/**
 * check if the user can benefit from page caching
 *
 * @access private
 * @return bool
 * @todo avoid DB lookup by passing group via cookies ?
 * @todo Note : don't do this if admins get cached too :)
 */
function xarPage_checkUserCaching()
{
    global $xarPage_cacheGroups;

    if (!xarUserIsLoggedIn()) {
        // always allow caching for anonymous users
        return true;
    } elseif (empty($xarPage_cacheGroups)) {
        // if no other cache groups are defined
        return false;
    }

    $gidlist = xarCache_getParents();

    $groups = explode(';',$xarPage_cacheGroups);
    foreach ($groups as $groupid) {
        if (in_array($groupid,$gidlist)) {
            return true;
        }
    }
    return false;
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
