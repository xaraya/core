<?php
/**
 * Xaraya Caching Configuration
 *
 * @package core
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage Page/Block Caching
 * @author mikespub
 * @author jsb
 */

class xarOutputCache extends Object
{
    public static $cacheCollection = '';
    public static $cacheTheme = '';
    public static $cacheSizeLimit = 2097152;
    public static $cacheCookie = 'XARAYASID';
    public static $cacheLocale = 'en_US.utf-8';

    public static $cacheOutputIsEnabled = false;
    public static $cachePageIsEnabled = false;
    public static $cacheBlockIsEnabled = false;
    public static $cacheObjectIsEnabled = false;
    public static $cacheModuleIsEnabled = false;

    /**
     * Initialise the caching options
     *
     * @return bool
     * @todo consider the use of a shutdownhandler for cache maintenance
     * @todo get rid of defines
     */
    public static function init($args = false)
    {
        $cachingConfiguration = array();

        if (!empty($args)) {
            extract($args);
        }

        $xarVarDir = sys::varpath();

        if (!isset($cacheDir)) {
            $cacheDir = $xarVarDir . '/cache/output';
        }

        // load the caching configuration
        try {
            include($xarVarDir . '/cache/config.caching.php');
        } catch (Exception $e) {
            // if the config file is missing, turn caching off
            @unlink($cacheDir . '/cache.touch');
            return false;
        }

        self::$cacheCollection = realpath($cacheDir);
        self::$cacheTheme = isset($cachingConfiguration['Output.DefaultTheme']) ?
            $cachingConfiguration['Output.DefaultTheme'] : '';
        self::$cacheSizeLimit = isset($cachingConfiguration['Output.SizeLimit']) ?
            $cachingConfiguration['Output.SizeLimit'] : 2097152;
        self::$cacheCookie = isset($cachingConfiguration['Output.CookieName']) ?
            $cachingConfiguration['Output.CookieName'] : 'XARAYASID';
        self::$cacheLocale= isset($cachingConfiguration['Output.DefaultLocale']) ?
            $cachingConfiguration['Output.DefaultLocale'] : 'en_US.utf-8';

        if (file_exists($cacheDir . '/cache.pagelevel')) {
            sys::import('xaraya.caching.page');
            // Note : we may already exit here if session-less page caching is enabled
            self::$cachePageIsEnabled = xarPageCache::init($cachingConfiguration);
            if (self::$cachePageIsEnabled) define('XARCACHE_PAGE_IS_ENABLED',1);
        }

        if (file_exists($cacheDir . '/cache.blocklevel')) {
            sys::import('xaraya.caching.block');
            self::$cacheBlockIsEnabled = xarBlockCache::init($cachingConfiguration);
            if (self::$cacheBlockIsEnabled) define('XARCACHE_BLOCK_IS_ENABLED',1);
        }

        if (file_exists($cacheDir . '/cache.objectlevel')) {
            sys::import('xaraya.caching.object');
            self::$cacheObjectIsEnabled = xarObjectCache::init($cachingConfiguration);
        }

        if (file_exists($cacheDir . '/cache.modulelevel')) {
            sys::import('xaraya.caching.module');
            self::$cacheModuleIsEnabled = xarModuleCache::init($cachingConfiguration);
        }

        self::$cacheOutputIsEnabled = true;
        return true;
    }

}

function xarCache_init($args = false)
{
    return xarOutputCache::init($args);
}

/**
 * Set the contents of some output in the cache
 *
 * @access public
 * @param  string $cacheKey
 * @param  string $cache_file
 * @param  string $cacheType
 * @param  string $value
 * @deprec 2005-02-01
 */
function xarOutputSetCached($cacheKey, $cache_file, $cacheType, $value)
{
    if (empty($GLOBALS['xar' . $cacheType . '_cacheStorage'])) {
        return;
    }

    $GLOBALS['xar' . $cacheType . '_cacheStorage']->setCached($cacheKey, $value);
}

/**
 * delete a cached file
 *
 * @access public
 * @param string $cacheKey the key identifying the particular cache you want to
 *                         access
 * @param string $name     the name of the file in that particular cache
 * @returns void
 * @deprec 2005-02-01
 */
function xarOutputDelCached($cacheKey, $name)
{
}

/**
 * flush a particular cache (e.g. when a new item is created)
 *
 * @access  public
 * @param   string $cacheKey the key identifying the particular cache you want
 *                           to wipe out
 * @returns void
 * @deprec 2005-02-01
 */
function xarOutputFlushCached($cacheKey, $dir = false)
{
    if (empty($dir)) {
        if (function_exists('xarPageFlushCached')) {
            xarPageFlushCached($cacheKey);
        }
        if (function_exists('xarBlockFlushCached')) {
            xarBlockFlushCached($cacheKey);
        }

// TODO: find out where this is called with a directory and replace

    } elseif (preg_match('/page\/?$/',$dir)) {
        if (function_exists('xarPageFlushCached')) {
            xarPageFlushCached($cacheKey);
        }

    } elseif (preg_match('/block\/?$/',$dir)) {
        if (function_exists('xarBlockFlushCached')) {
            xarBlockFlushCached($cacheKey);
        }

    } else {
        if (function_exists('xarPageFlushCached')) {
            xarPageFlushCached($cacheKey);
        }
        if (function_exists('xarBlockFlushCached')) {
            xarBlockFlushCached($cacheKey);
        }
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
 * @deprec 2005-02-01
 */
function xarCache_CleanCached($cacheType)
{
    if (empty($GLOBALS['xar' . $cacheType . '_cacheStorage'])) {
        return;
    }

// CHECKME: see if this is still needed
    // If the cache type is Block, then the cache is full so we flush the blocks
    // to make more room
    if ($cacheType == 'Block') {
        $GLOBALS['xar' . $cacheType . '_cacheStorage']->flushCached('');
    }

    $GLOBALS['xar' . $cacheType . '_cacheStorage']->cleanCached();
}

/**
 * helper function to determine if the cache size limit has been reached
 *
 * @access protected
 * @param  string  $dir
 * @param  string  $cacheType
 * @return boolean
 * @author jsb
 * @deprec 2005-02-01
 */
function xarCache_SizeLimit($dir = false, $cacheType)
{
    if (empty($cacheType) || empty($GLOBALS['xar' . $cacheType . '_cacheStorage'])) {
        return;
    }
    $value = $GLOBALS['xar' . $cacheType . '_cacheStorage']->sizeLimitReached();
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
 * @deprec 2005-02-01
 */
function xarCacheGetDirSize($dir = false)
{
    if (empty($dir)) {
        return 0;

    } elseif (preg_match('/page\/?$/',$dir)) {
        $size = $GLOBALS['xarPage_cacheStorage']->getCacheSize();

    } elseif (preg_match('/block\/?$/',$dir)) {
        $size = $GLOBALS['xarBlock_cacheStorage']->getCacheSize();

    } elseif (preg_match('/mod\/?$/',$dir)) {
        $size = 0;

    } elseif (preg_match('/output\/?$/',$dir)) {
        $size = $GLOBALS['xarPage_cacheStorage']->getCacheSize();
        $size += $GLOBALS['xarBlock_cacheStorage']->getCacheSize();
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
    $currentid = xarSession::getVar('role_id');
    if (xarCore::isCached('User.Variables.'.$currentid, 'parentlist')) {
        return xarCore::getCached('User.Variables.'.$currentid, 'parentlist');
    }
    $rolemembers = xarDB::getPrefix() . '_rolemembers';
    $dbconn = xarDB::getConn();
    $query = "SELECT parentid FROM $rolemembers WHERE id = ?";
    $stmt   = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery(array($currentid));

    $gidlist = array();
    while($result->next()) {
        $gidlist[] = $result->getInt(1);
    }
    $result->Close();
    xarCore::setCached('User.Variables.'.$currentid, 'parentlist',$gidlist);
    return $gidlist;
}

/**
 * Get a storage class instance for some type of cached data
 *
 * @access protected
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
function xarCache_getStorage($args)
{
    sys::import('xaraya.caching.storage');
    return xarCache_Storage::getCacheStorage($args);
}

?>
