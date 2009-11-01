<?php
/**
 * Xaraya Caching Configuration
 *
 * @package core
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage caching
 * @author mikespub
 * @author jsb
 */

class xarCache extends Object
{
    public static $coreCacheIsEnabled      = true;
    public static $outputCacheIsEnabled    = false;
    public static $variableCacheIsEnabled  = false;

    /**
     * Initialise the caching options
     *
     * @return void
     */
    public static function init($args = false)
    {
        // Note : we may already exit here if session-less page caching is enabled
        if (file_exists(sys::varpath() . '/cache/output/cache.touch')) {
            sys::import('xaraya.caching.output');
            self::$outputCacheIsEnabled = xarOutputCache::init($args);
        }

        sys::import('xaraya.caching.core');
        self::$coreCacheIsEnabled = xarCoreCache::init($args);

        //sys::import('xaraya.caching.variable');
        //self::$variableCacheIsEnabled = xarVariableCache::init($args);
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
    public static function getStorage(array $args = array())
    {
        sys::import('xaraya.caching.storage');
        return xarCache_Storage::getCacheStorage($args);
    }

    /**
     * Get the parent group ids of the current user (with minimal overhead)
     *
     * @access private
     * @return array of parent gids
     * @todo avoid DB lookup by passing groups via cookies ?
     * @todo Note : don't do this if admins get cached too :)
     */
    public static function getParents()
    {
        $currentid = xarSession::getVar('role_id');
        if (xarCoreCache::isCached('User.Variables.'.$currentid, 'parentlist')) {
            return xarCoreCache::getCached('User.Variables.'.$currentid, 'parentlist');
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
        xarCoreCache::setCached('User.Variables.'.$currentid, 'parentlist',$gidlist);
        return $gidlist;
    }
}

function xarCache_init($args = false)
{
    return xarCache::init($args);
}

function xarCache_getParents()
{
    return xarCache::getParents();
}

function xarCache_getStorage($args)
{
    return xarCache::getStorage($args);
}

?>
