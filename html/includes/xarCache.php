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
    global $xarBlock_cacheTime;

    if ((@include('var/cache/config.caching.php')) == false) {
        // if there's a parse problem with the config file, turn caching off
        @unlink($cacheDir . '/cache.touch');
        return false;
    }

    $xarOutput_cacheCollection = $cacheDir;
    $xarOutput_cacheTheme = isset($cachingConfiguration['Output.DefaultTheme']) ? $cachingConfiguration['Output.DefaultTheme'] : '';
    $xarOutput_cacheSizeLimit = isset($cachingConfiguration['Output.SizeLimit']) ? $cachingConfiguration['Output.SizeLimit'] : 2097152;
    $xarPage_cacheTime = isset($cachingConfiguration['Page.TimeExpiration']) ? $cachingConfiguration['Page.TimeExpiration'] : 1800;
    $xarPage_cacheDisplay = isset($cachingConfiguration['Page.DisplayView']) ? $cachingConfiguration['Page.DisplayView'] : 0;
    $xarPage_cacheShowTime = isset($cachingConfiguration['Page.ShowTime']) ? $cachingConfiguration['Page.ShowTime'] : 1;
    $xarBlock_cacheTime = isset($cachingConfiguration['Block.TimeExpiration']) ? $cachingConfiguration['Block.TimeExpiration'] : 7200;

    return true;
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
 * xarPageDelCached('MyCache', 'myvar');
 * ...
 * xarPageFlushCached('MyCache');
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
    global $xarOutput_cacheCollection, $xarPage_cacheTime, $xarOutput_cacheTheme, $xarPage_cacheDisplay, $xarPage_cacheCode;

    $xarTpl_themeDir = xarTplGetThemeDir();

    $page = xarServerGetVar('HTTP_HOST') . $xarTpl_themeDir . xarServerGetVar('REQUEST_URI');
    $param = xarServerGetVar('QUERY_STRING');
    if (!empty($param)) {
        $page .= '?' . $param;
    }
    // use this global instead of $cache_file so we can cache several things based on different
    // $cacheKey (and $name if necessary) in one page request - e.g. for module and block caching
    $xarPage_cacheCode = md5($page);

// CHECKME: use $name for something someday ?
    $cache_file = "$xarOutput_cacheCollection/$cacheKey-$xarPage_cacheCode.php";

    if (strstr($cacheKey, '-user-') &&
        !strstr($cacheKey, '-search') &&
        !strstr($cacheKey, '-register') &&
        ((($xarPage_cacheDisplay != 1) && !strstr($cacheKey, '-display')) || ($xarPage_cacheDisplay == 1)) &&
        xarServerGetVar('REQUEST_METHOD') == 'GET' &&
        (empty($xarOutput_cacheTheme) || strstr($xarTpl_themeDir, $xarOutput_cacheTheme)) &&
        file_exists($cache_file) &&
        filesize($cache_file) > 0 &&
        ($xarPage_cacheTime == 0 || filemtime($cache_file) > time() - $xarPage_cacheTime) &&
        !xarUserIsLoggedIn()) {

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
        // Netscape 6.2 doesn't like this one ?
        header("Expires: " . gmdate("D, d M Y H:i:s", $mod + $xarPage_cacheTime) . " GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s", $mod) . " GMT");
        // Netscape 6.2 doesn't like this one either ?
        // FIXME: This belongs in session subsystem, what does it do? Does it need to be here?
        session_cache_limiter('public');
        // end 304 test

        return true;
    } else {
        return false;
    }
}

//function xarBlockIsCached($cacheKey, $blockDynamics, $blockPermission, $name = '')
function xarBlockIsCached($args)
{
    global $xarOutput_cacheCollection, $xarBlock_cacheCode, $xarBlock_cacheTime, $blockCacheExpireTime, $xarBlock_noCache;
    
    $xarTpl_themeDir = xarTplGetThemeDir();
    
    extract($args);

    if (xarVarIsCached('Blocks.Caching', 'settings')) {
        $blocks = xarVarGetCached('Blocks.Caching', 'settings');
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
            list ($bid, $noCache, $pageShared, $userShared, $blockCacheExpireTime) = $result->fields;
            $blocks[$bid] = array('bid'         => $bid,
                                  'nocache'     => $noCache,
                                  'pageshared'  => $pageShared,
                                  'usershared'  => $userShared,
                                  'cacheexpire' => $blockCacheExpireTime);
            $result->MoveNext();
        }
        $result->Close();
        xarVarSetCached('Blocks.Caching', 'settings', $blocks);
    }
    if (isset($blocks[$blockid])) {
        $noCache = $blocks[$blockid]['nocache'];
        $pageShared = $blocks[$blockid]['pageshared'];
        $userShared = $blocks[$blockid]['usershared'];
        $blockCacheExpireTime = $blocks[$blockid]['cacheexpire'];
    }

    if (empty($noCache)) {
        $noCache = 0;
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

    if ($noCache == 1) {
        $xarBlock_noCache = 1;
        return false;
    }

    $factors = xarServerGetVar('HTTP_HOST') . $xarTpl_themeDir;

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
        $currentuid = xarSessionGetVar('uid');
        if (xarVarIsCached('User.Variables.'.$currentuid, 'parentlist')) {
            $gids = xarVarGetCached('User.Variables.'.$currentuid, 'parentlist');
        } else {
            $systemPrefix = xarDBGetSystemTablePrefix();
            $rolemembers = $systemPrefix . '_rolemembers';
            $dbconn =& xarDBGetConn();
            $query = "SELECT xar_parentid FROM $rolemembers WHERE xar_uid = $currentuid ";
            $result =& $dbconn->Execute($query);
            if (!$result) return;
            $gids ='';
            while(!$result->EOF) {
                $parentid = $result->GetRowAssoc(false);
                $gids .= $parentid['xar_parentid'];
                $result->MoveNext();
            }
            $result->Close();
            xarVarSetCached('User.Variables.'.$currentuid, 'parentlist',$gids);
        }
        $factors .=$gids;
    } else {
        $factors .= xarSessionGetVar('uid');
    }

    $xarBlock_cacheCode = md5($factors);

    // CHECKME: use $name for something someday ?
    $cache_file = "$xarOutput_cacheCollection/$cacheKey-$xarBlock_cacheCode.php";

    if (
        xarServerGetVar('REQUEST_METHOD') == 'GET' &&
        file_exists($cache_file) &&
        filesize($cache_file) > 0 &&
        ($blockCacheExpireTime == 0 || filemtime($cache_file) > time() - $blockCacheExpireTime)) {
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

function xarBlockGetCached($cacheKey, $name = '')
{
    global $xarOutput_cacheCollection, $xarBlock_cacheCode;
    
    // CHECKME: use $name for something someday ?
    $cache_file = "$xarOutput_cacheCollection/$cacheKey-$xarBlock_cacheCode.php";
    
    //$blockCachedOutput = file_get_contents($cache_file); //ouch, only available in php >= 4.3, bummer 
    
    $blockCachedOutput = '';
    $file = @fopen($cache_file, "rb");
    if ($file) {
        while (!feof($file)) $blockCachedOutput .= fread($file, 1024);
        fclose($file);
    }

    return $blockCachedOutput;
}

/**
 * set the content of a cached page
 *
 * @access public
 * @param key the key identifying the particular cache you want to access
 * @param name the name of the page in that particular cache
 * @param value the new content for that page
 * @returns void
 */
function xarPageSetCached($cacheKey, $name, $value)
{
    global $xarOutput_cacheCollection, $xarPage_cacheTime, $xarOutput_cacheTheme, $xarPage_cacheDisplay, $xarPage_cacheShowTime, $xarOutput_cacheSizeLimit, $xarPage_cacheCode;
    
    $xarTpl_themeDir = xarTplGetThemeDir();

// CHECKME: use $name for something someday ?
    $cache_file = "$xarOutput_cacheCollection/$cacheKey-$xarPage_cacheCode.php";

    if (strstr($cacheKey, '-user-') &&
        !strstr($cacheKey, '-search') &&
        !strstr($cacheKey, '-register') &&
        ((($xarPage_cacheDisplay != 1) && !strstr($cacheKey, '-display')) || ($xarPage_cacheDisplay == 1)) &&
        xarServerGetVar('REQUEST_METHOD') == 'GET' &&
        (empty($xarOutput_cacheTheme) || strstr($xarTpl_themeDir, $xarOutput_cacheTheme)) &&
        (!file_exists($cache_file) ||
        ($xarPage_cacheTime != 0 && filemtime($cache_file) < time() - $xarPage_cacheTime)) &&
        xarCacheDirSize($xarOutput_cacheCollection, 'Page') <= $xarOutput_cacheSizeLimit &&
        !xarUserIsLoggedIn()) {
        $fp = @fopen($cache_file,"w");
        if (!empty($fp)) {
            if ($xarPage_cacheShowTime == 1) {
                $now = xarML('Last updated on #(1)',strftime('%a, %d %B %Y %H:%M:%S %Z',time()));
                $value = preg_replace('#</body>#','<div class="xar-sub" style="text-align: center; padding: 8px; ">'.$now.'</div></body>',$value);
            }
            @fwrite($fp,$value);
            @fclose($fp);
        }
    }
}

function xarBlockSetCached($cacheKey, $name, $value)
{
    global $xarOutput_cacheCollection, $xarOutput_cacheSizeLimit, $xarBlock_cacheCode, $blockCacheExpireTime, $xarBlock_noCache;
    
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
        ($blockCacheExpireTime != 0 && filemtime($cache_file) < time() - $blockCacheExpireTime)) &&
        xarCacheDirSize($xarOutput_cacheCollection, 'Block', $cacheKey) <= $xarOutput_cacheSizeLimit
        ) {
        $fp = @fopen($cache_file,"w");
        if (!empty($fp)) {
            //$value .= 'Cached Block';// This line is used for testing
            @fwrite($fp, $value);
            @fclose($fp);
        }
    }
}

/**
 * delete a cached page
 *
 * @access public
 * @param key the key identifying the particular cache you want to access
 * @param name the name of the page in that particular cache
 * @returns void
 */
function xarPageDelCached($cacheKey, $name)
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
 * @access public
 * @param key the key identifying the particular cache you want to wipe out
 * @returns void
 */
function xarPageFlushCached($cacheKey)
{
    global $xarOutput_cacheCollection;

    if ($handle = @opendir($xarOutput_cacheCollection)) {
        while (($file = readdir($handle)) !== false) {
            if ((preg_match("#$cacheKey#", $file)) && (strstr($file, '.php') !== false)) {
                @unlink($xarOutput_cacheCollection . '/' . $file);
            }
        }
        closedir($handle);
    }
}

/**
 * clean the cache of old entries
 * note: for blocks, this only gets called when the cache size limit has been reached,
 *       and when called by blocks, the global cache timeout takes precedents 
 *
 * @access public
 * @returns void
 */
function xarOutputCleanCached($type, $cacheKey = '')
{
    global $xarOutput_cacheCollection, ${'xar' . $type . '_cacheTime'};

    $touch_file = $xarOutput_cacheCollection . '/' . 'cache.touch';

    if (${'xar' . $type . '_cacheTime'} == 0 || (file_exists($touch_file) && filemtime($touch_file) > time() - ${'xar' . $type . '_cacheTime'})) {
        return;
    }
    touch($touch_file);
    if ($handle = @opendir($xarOutput_cacheCollection)) {
        while (($file = readdir($handle)) !== false) {
            $cache_file = $xarOutput_cacheCollection . '/' . $file;
            if (filemtime($cache_file) < time() - (${'xar' . $type . '_cacheTime'} + 60) &&
                (strstr($file, '.php') !== false) &&
                ($type == 'Block' || 
                ($type == 'Page' && strstr($file, 'block') == false))) {
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
 * @returns float
 * @author nospam@jusunlee.com | laurie@oneuponedown.com | jsb
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
        // todo: come up with a good way to determine which cacheKeys are the least important
        // and fush them to make more space.
        //xarPageFlushCached('articles-user-view');
    }

    return $size;
}

?>
