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
 * @author jsb
 */

/**
 * Check whether a block is cached
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
    
    if (isset($blockinfo)) {
        $factors .= md5(serialize($blockinfo));
    }

    $xarBlock_cacheCode = md5($factors);

    // CHECKME: use $name for something someday ?
    $cache_file = "$xarOutput_cacheCollection/block/$cacheKey-$xarBlock_cacheCode.php";

    if (
        file_exists($cache_file) &&
        ($blockCacheExpireTime == 0 ||
         filemtime($cache_file) > time() - $blockCacheExpireTime)) {
        return true;
    } else {
        return false;
    }
}

function xarBlockGetCached($cacheKey, $name = '')
{
    global $xarOutput_cacheCollection, $xarBlock_cacheCode;
    
    // CHECKME: use $name for something someday ?
    $cache_file = "$xarOutput_cacheCollection/block/$cacheKey-$xarBlock_cacheCode.php";
    
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

    // CHECKME: use $name for something someday ?
    $cache_file = "$xarOutput_cacheCollection/block/$cacheKey-$xarBlock_cacheCode.php";
    if (
        xarServerGetVar('REQUEST_METHOD') == 'GET' &&
        (!file_exists($cache_file) ||
        ($blockCacheExpireTime != 0 &&
         filemtime($cache_file) < time() - $blockCacheExpireTime)) &&
        !xarCacheSizeLimit($xarOutput_cacheCollection, 'Block')
        ) {

        xarOutputSetCached($cacheKey, $cache_file, 'Block', $value);

    }
}

?>
