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

    global $xarPage_cacheCollection;
    global $xarPage_cacheTime;
    global $xarPage_cacheTheme;
    global $xarPage_cacheDisplay;
    global $xarPage_cacheShowTime;
    global $xarOutput_cacheSizeLimit;

    if ((@include('var/cache/config.caching.php')) == false) {
        // if there's a parse problem with the config file, turn caching off
        unlink($cacheDir . '/cache.touch');
        return false;
    }

    // should we set default values, or bail out if something is missing?

    $xarPage_cacheCollection = $cacheDir;
    $xarPage_cacheTime = $cachingConfiguration['Page.TimeExpiration'];
    $xarPage_cacheTheme = $cachingConfiguration['Page.DefaultTheme'];
    $xarPage_cacheDisplay = $cachingConfiguration['Page.DisplayView'];
    $xarPage_cacheShowTime = $cachingConfiguration['Page.ShowTime'];
    $xarOutput_cacheSizeLimit = $cachingConfiguration['Output.SizeLimit'];
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
    global $xarPage_cacheCollection, $xarPage_cacheTime, $xarPage_cacheTheme, $xarPage_cacheDisplay, $xarPage_cacheCode;

    global $xarTpl_themeDir;
    $page = $xarTpl_themeDir . xarServerGetVar('REQUEST_URI');
    $param = xarServerGetVar('QUERY_STRING');
    if (!empty($param)) {
        $page .= '?' . $param;
    }
    // use this global instead of $cache_file so we can cache several things based on different
    // $cacheKey (and $name if necessary) in one page request - e.g. for module and block caching
    $xarPage_cacheCode = md5($page);

// CHECKME: use $name for something someday ?
    $cache_file = "$xarPage_cacheCollection/$cacheKey-$xarPage_cacheCode.php";

    if (preg_match('/-user-/', $cacheKey) &&
        !preg_match('/-search/', $cacheKey) &&
        ((($xarPage_cacheDisplay != 1) && !preg_match('/-display/', $cacheKey)) || ($xarPage_cacheDisplay == 1)) &&
        xarServerGetVar('REQUEST_METHOD') == 'GET' &&
        preg_match('#'.$xarPage_cacheTheme.'#', $xarTpl_themeDir) &&
        file_exists($cache_file) &&
        filesize($cache_file) > 0 &&
        filemtime($cache_file) > time() - $xarPage_cacheTime &&
        !xarUserIsLoggedIn()) {

        // start 304 test
        $mod = filemtime($cache_file);
        // doesn't seem to be taken into account ?
        $etag = $xarPage_cacheCode . $mod;
        header("ETag: $etag");
        $match = xarServerGetVar('HTTP_IF_NONE_MATCH');
        if (!empty($match) && $match == $etag) {
            header('HTTP/1.0 304');
            session_write_close();
            exit;
        } else {
            $since = xarServerGetVar('HTTP_IF_MODIFIED_SINCE');
            if (!empty($since) && strtotime($since) >= $mod) {
                header('HTTP/1.0 304');
                session_write_close();
                exit;
            }
        }
        // Netscape 6.2 doesn't like this one ?
        header("Expires: " . gmdate("D, d M Y H:i:s", $mod + $xarPage_cacheTime) . " GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s", $mod) . " GMT");
        // Netscape 6.2 doesn't like this one either ?
        session_cache_limiter('public');
        // end 304 test

        return true;
    } else {
        return false;
    }
}

function xarBlockIsCached($cacheKey, $name = '')
{
    global $xarPage_cacheCollection, $xarPage_cacheTime, $xarPage_cacheTheme, $xarBlock_cacheCode;
    
    global $xarTpl_themeDir;
    $factors = $xarTpl_themeDir;
    //if (xarBlockDynamic == 1) {
        $factors .= xarServerGetVar('REQUEST_URI');
        $param = xarServerGetVar('QUERY_STRING');
        if (!empty($param)) {
            $factors .= '?' . $param;
        }
    //}
    //if (xarBlockPermissions == 1) {
        $systemPrefix = xarDBGetSystemTablePrefix();
        $rolemembers = $systemPrefix . '_rolemembers';
        $cuid = xarSessionGetVar('uid');
        list($dbconn) = xarDBGetConn();
        $query = "SELECT xar_parentid FROM $rolemembers WHERE xar_uid = $cuid ";
        $result =& $dbconn->Execute($query);
        if (!$result) return;
        $gids ='';
        while(!$result->EOF) {
            $parentid = $result->GetRowAssoc(false);
            $gids .= $parentid['xar_parentid'];
            $result->MoveNext();
        }
        $result->Close();
        $factors .=$gids;
    //} elseif (xarBlockPermission == 2) {
    //    $factors .= xarSessionGetVar('uid');
    //} else {
    //    $factors .= 0;
    //}
    $xarBlock_cacheCode = md5($factors);
    
    // CHECKME: use $name for something someday ?
    $cache_file = "$xarPage_cacheCollection/$cacheKey-$xarBlock_cacheCode.php";
    
    if (
        xarServerGetVar('REQUEST_METHOD') == 'GET' &&
        preg_match('#'.$xarPage_cacheTheme.'#', $xarTpl_themeDir) &&
        file_exists($cache_file) &&
        filesize($cache_file) > 0 &&
        filemtime($cache_file) > time() - $xarPage_cacheTime) {
        
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
    global $xarPage_cacheCollection, $xarPage_cacheCode;

// CHECKME: use $name for something someday ?
    $cache_file = "$xarPage_cacheCollection/$cacheKey-$xarPage_cacheCode.php";
    @readfile($cache_file);

    xarPageCleanCached();
}

function xarBlockGetCached($cacheKey, $name = '')
{
    global $xarPage_cacheCollection, $xarBlock_cacheCode;
    
    // CHECKME: use $name for something someday ?
    $cache_file = "$xarPage_cacheCollection/$cacheKey-$xarBlock_cacheCode.php";
    @readfile($cache_file);
    
    xarPageCleanCached();
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
    global $xarPage_cacheCollection, $xarPage_cacheTime, $xarPage_cacheTheme, $xarPage_cacheDisplay, $xarPage_cacheShowTime, $xarOutput_cacheSizeLimit, $xarPage_cacheCode;
    global $xarTpl_themeDir;

// CHECKME: use $name for something someday ?
    $cache_file = "$xarPage_cacheCollection/$cacheKey-$xarPage_cacheCode.php";

    if (preg_match('/-user-/', $cacheKey) &&
        !preg_match('/-search/', $cacheKey) &&
        ((($xarPage_cacheDisplay != 1) && !preg_match('/-display/', $cacheKey)) || ($xarPage_cacheDisplay == 1)) &&
        xarServerGetVar('REQUEST_METHOD') == 'GET' &&
        preg_match('#'.$xarPage_cacheTheme.'#', $xarTpl_themeDir) &&
        (!file_exists($cache_file) ||
        filemtime($cache_file) < time() - $xarPage_cacheTime) &&
        xarCacheDirSize($xarPage_cacheCollection) <= $xarOutput_cacheSizeLimit &&
        !xarUserIsLoggedIn()) {
        $fp = @fopen($cache_file,"w");
        if (!empty($fp)) {
            $now = xarML('Last updated on #(1)',strftime('%a, %d %B %Y %H:%M:%S %Z',time()));
            if ($xarPage_cacheShowTime == 1) {
                $value = preg_replace('#</body>#','<div class="xar-sub" style="text-align: center; padding: 8px; ">'.$now.'</div></body>',$value);
            } else {
                $value = preg_replace('#</body>#','<!--'.$now.'--></body>',$value);
            }
            @fwrite($fp,$value);
            @fclose($fp);
        }
        xarPageCleanCached();
    }
}

function xarBlockSetCached($cacheKey, $name, $value)
{
    global $xarPage_cacheCollection, $xarPage_cacheTime, $xarPage_cacheTheme, $xarPage_cacheShowTime, $xarOutput_cacheSizeLimit, $xarBlock_cacheCode;
    global $xarTpl_themeDir;
    
    // CHECKME: use $name for something someday ?
    $cache_file = "$xarPage_cacheCollection/$cacheKey-$xarBlock_cacheCode.php";
    if (
        xarServerGetVar('REQUEST_METHOD') == 'GET' &&
        preg_match('#'.$xarPage_cacheTheme.'#', $xarTpl_themeDir) &&
        (!file_exists($cache_file) ||
         filemtime($cache_file) < time() - $xarPage_cacheTime) &&
        xarCacheDirSize($xarPage_cacheCollection) <= $xarOutput_cacheSizeLimit
        ) {
        $fp = @fopen($cache_file,"w");
        if (!empty($fp)) {
            @fwrite($fp,$value);
            @fclose($fp);
        }
        xarPageCleanCached();
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
    global $xarPage_cacheCollection;
    // TODO: check if we don't need to work with $GLOBALS here for some PHP ver
    if (isset($xarPage_cacheCollection[$cacheKey][$name])) {
        unset($xarPage_cacheCollection[$cacheKey][$name]);
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
    global $xarPage_cacheCollection;

    if ($handle = @opendir($xarPage_cacheCollection)) {
        while (($file = readdir($handle)) !== false) {
            if ((preg_match("#$cacheKey#",$file)) && (strstr($file, 'php') !== false)) {
                @unlink($xarPage_cacheCollection . '/' . $file);
            }
        }
        closedir($handle);
    }
}

/**
 * clean the cache of old entries
 *
 * @access public
 * @returns void
 */
function xarPageCleanCached()
{
    global $xarPage_cacheCollection, $xarPage_cacheTime;

    $touch_file = $xarPage_cacheCollection . '/' . 'cache.touch';

    if (file_exists($touch_file) && filemtime($touch_file) > time() - $xarPage_cacheTime) {
        return;
    }
    touch($touch_file);
    if ($handle = @opendir($xarPage_cacheCollection)) {
        while (($file = readdir($handle)) !== false) {
            $cache_file = $xarPage_cacheCollection . '/' . $file;
            if (filemtime($cache_file) < time() - ($xarPage_cacheTime + 60)) {
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
function xarCacheDirSize($dir = FALSE)
{
    global $size, $xarOutput_cacheSizeLimit;
    $size = 0;
    if ($dir && is_dir($dir)) {
        if (substr($dir,-1) != "/") $dir .= "/";
        if ($dirId = opendir($dir)) {
            while (($item = readdir($dirId)) !== FALSE) {
                if ($item != "." && $item != "..") {
                    if (is_dir($dir . $item)) {
                        $size += xarCacheDirSize($dir . $item);
                    } else {
                        $size += filesize($dir . $item);
                    }
                }
            }
            closedir($dirId);
        }
    }
    $size /= 1048576;

    if($size > $xarOutput_cacheSizeLimit) {
        xarPageCleanCached();
        // todo: come up with a good way to determine which cacheKeys are the least important
        // and fush them to make more space.
        //xarPageFlushCached('articles-user-view');
    }

    return $size;
}

?>