<?php
/**
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
 * Initialise the block caching options
 *
 * @returns bool
 * @return true on success, false on failure
 */
function xarBlockCache_init($args = array())
{
// TODO: clean up all these globals and put them e.g. into a single array
    global $xarBlock_cacheTime;

    $xarBlock_cacheTime = isset($args['Block.TimeExpiration']) ?
        $args['Block.TimeExpiration'] : 7200;
    $xarBlock_cacheSizeLimit = isset($args['Block.SizeLimit']) ?
        $args['Block.SizeLimit'] : 2097152;

    global $xarOutput_cacheCollection;

    $storage = !empty($args['Block.CacheStorage']) ?
        $args['Block.CacheStorage'] : 'filesystem';
    $logfile = !empty($args['Block.LogFile']) ?
        $args['Block.LogFile'] : null;
    $GLOBALS['xarBlock_cacheStorage'] = xarCache_getStorage(array('storage'   => $storage,
                                                                  'type'      => 'block',
                                                                  'cachedir'  => $xarOutput_cacheCollection,
                                                                  'expire'    => $xarBlock_cacheTime,
                                                                  'sizelimit' => $xarBlock_cacheSizeLimit,
                                                                  'logfile'   => $logfile));
    if (empty($GLOBALS['xarBlock_cacheStorage'])) {
        return false;
    }

    return true;
}

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
        if ($result) {
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
        } else {
            $blocks = 'noSettings';
        }
        xarCore_SetCached('Blocks.Caching', 'settings', $blocks);
    }
    if (isset($blocks[$blockid])) {
        $noCache = $blocks[$blockid]['nocache'];
        $pageShared = $blocks[$blockid]['pageshared'];
        $userShared = $blocks[$blockid]['usershared'];
        $blockCacheExpireTime = $blocks[$blockid]['cacheexpire'];

    // cfr. bug 4021
    } elseif (!empty($blockinfo['content']) && is_array($blockinfo['content'])) {
        if (isset($blockinfo['content']['nocache'])) {
            $noCache = $blockinfo['content']['nocache'];
        }
        if (isset($blockinfo['content']['pageshared'])) {
            $pageShared = $blockinfo['content']['pageshared'];
        }
        if (isset($blockinfo['content']['usershared'])) {
            $userShared = $blockinfo['content']['usershared'];
        }
        if (isset($blockinfo['content']['cacheexpire'])) {
            $blockCacheExpireTime = $blockinfo['content']['cacheexpire'];
        }
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
    $GLOBALS['xarBlock_cacheStorage']->setCode($xarBlock_cacheCode);

    // Note: we pass along the expiration time here, because it may be different for each block
    $result = $GLOBALS['xarBlock_cacheStorage']->isCached($cacheKey, $blockCacheExpireTime);

    return $result;
}

function xarBlockGetCached($cacheKey, $name = '')
{
    if (empty($GLOBALS['xarBlock_cacheStorage'])) {
        return '';
    }

    global $blockCacheExpireTime;

    // Note: we pass along the expiration time here, because it may be different for each block
    return $GLOBALS['xarBlock_cacheStorage']->getCached($cacheKey, 0, $blockCacheExpireTime);
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
    global $xarBlock_cacheTime,
           $blockCacheExpireTime,
           $xarBlock_noCache;
    
    if ($xarBlock_noCache == 1) {
        $xarBlock_noCache = '';
        return;
    }

    if (// the http request is a GET AND
        xarServerGetVar('REQUEST_METHOD') == 'GET' &&
    // CHECKME: do we really want to check this again, or do we ignore it ?
        // the cache entry doesn't exist or has expired (no log here) AND
        !($GLOBALS['xarBlock_cacheStorage']->isCached($cacheKey, $blockCacheExpireTime, 0)) &&
        // the cache collection directory hasn't reached its size limit...
        !($GLOBALS['xarBlock_cacheStorage']->sizeLimitReached()) ) {

        // Note: we pass along the expiration time here, because it may be different for each block
        $GLOBALS['xarBlock_cacheStorage']->setCached($cacheKey, $value, $blockCacheExpireTime);
    }
}

/**
 * Flush block cache entries
 */
function xarBlockFlushCached($cacheKey)
{
    if (empty($GLOBALS['xarBlock_cacheStorage'])) {
        return;
    }

    $GLOBALS['xarBlock_cacheStorage']->flushCached($cacheKey);
}

?>
