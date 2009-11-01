<?php
/**
 * Object gui method caching
 *
 * @package core
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage caching
 * @author mikespub
 * @author jsb
**/

class xarObjectCache extends Object
{
    public static $cacheTime = 7200;
    public static $cacheSizeLimit = 2097152;

    public static $cacheStorage = null;
    public static $cacheCode = '';
    public static $cacheSettings = null;

    public static $noCache    = null;
    public static $pageShared = null;
    public static $userShared = null;
    public static $expireTime = null;

    /**
     * Initialise the object caching options
     *
     * @return bool true on success, false on failure
     */
    public static function init(array $args = array())
    {
        self::$cacheTime = isset($args['Object.TimeExpiration']) ?
            $args['Object.TimeExpiration'] : 7200;
        self::$cacheSizeLimit = isset($args['Object.SizeLimit']) ?
            $args['Object.SizeLimit'] : 2097152;

        $storage = !empty($args['Object.CacheStorage']) ?
            $args['Object.CacheStorage'] : 'filesystem';
        $logfile = !empty($args['Object.LogFile']) ?
            $args['Object.LogFile'] : null;
        sys::import('xaraya.caching.storage');
        self::$cacheStorage = xarCache_Storage::getCacheStorage(array('storage'   => $storage,
                                                                      'type'      => 'object',
                                                                      'cachedir'  => xarOutputCache::$cacheCollection,
                                                                      'expire'    => self::$cacheTime,
                                                                      'sizelimit' => self::$cacheSizeLimit,
                                                                      'logfile'   => $logfile));
        if (empty(self::$cacheStorage)) {
            return false;
        }

        return true;
    }

    /**
     * Get cache settings for the objects
     * @return array
     */
    public static function getCacheSettings()
    {
        if (!isset(self::$cacheSettings)) {
            $settings = array();
            // We need to get it.

            self::$cacheSettings = $settings;
        }
        return self::$cacheSettings;
    }

    /**
     * Check if this object is suitable for object caching
     *
     * @access public
     * @param  string  $cacheKey  the key identifying the particular object you want to access
     * @param  integer $objectid   the current object id
     * @param  array   $objectinfo optional objectinfo when using the object BL tag
     * @returns bool
     * @return true if the object is suitable for caching, false if not
     */
    public static function checkCachingRules($cacheKey, $objectid = 0, $objectinfo = array())
    {
        // CHECKME: watch out for nested objects !
        self::$noCache    = null;
        self::$pageShared = null;
        self::$userShared = null;
        self::$expireTime = null;

        $settings = self::getCacheSettings();

        if (isset($settings[$objectid])) {
            self::$noCache    = $settings[$objectid]['nocache'];
            self::$pageShared = $settings[$objectid]['pageshared'];
            self::$userShared = $settings[$objectid]['usershared'];
            self::$expireTime = $settings[$objectid]['cacheexpire'];

        // CHECKME: cfr. bug 4021 Override caching vars with object BL tag
        } elseif (!empty($objectinfo['content']) && is_array($objectinfo['content'])) {
            if (isset($objectinfo['content']['nocache'])) {
                self::$noCache    = $objectinfo['content']['nocache'];
            }
            if (isset($objectinfo['content']['pageshared'])) {
                self::$pageShared = $objectinfo['content']['pageshared'];
            }
            if (isset($objectinfo['content']['usershared'])) {
                self::$userShared = $objectinfo['content']['usershared'];
            }
            if (isset($objectinfo['content']['cacheexpire'])) {
                self::$expireTime = $objectinfo['content']['cacheexpire'];
            }
        }

        if (empty(self::$noCache)) {
            self::$noCache = 0;
        }
        if (empty(self::$pageShared)) {
            self::$pageShared = 0;
        }
        if (empty(self::$userShared)) {
            self::$userShared = 0;
        }
        if (!isset(self::$expireTime)) {
            self::$expireTime = self::$cacheTime;
        }

        if (!empty(self::$noCache)) {
            return false;
        }

        return true;
    }

    /**
     * Check whether a object is cached
     *
     * @access public
     * @param  string  $cacheKey  the key identifying the particular object you want to access
     * @param  integer $objectid   the current object id
     * @param  array   $objectinfo optional objectinfo when using the object BL tag
     * @return bool
     */
    public static function isCached($cacheKey, $objectid = 0, $objectinfo = array())
    {
        if (empty(self::$cacheStorage)) {
            return false;
        }

        // Check if this object is suitable for object caching
        if (!(self::checkCachingRules($cacheKey, $objectid, $objectinfo))) {
            self::$noCache = 1;
            return false;
        }

        $xarTpl_themeDir = xarTplGetThemeDir();

        $factors = xarServer::getVar('HTTP_HOST') . $xarTpl_themeDir .
                   xarUserGetNavigationLocale();

        if (self::$pageShared == 0) {
            $factors .= xarServer::getVar('REQUEST_URI');
            $param = xarServer::getVar('QUERY_STRING');
            if (!empty($param)) {
                $factors .= '?' . $param;
            }
        }

        if (self::$userShared == 2) {
            $factors .= 0;
        } elseif (self::$userShared == 1) {
            $gidlist = xarCache_getParents();
            $factors .= join(';',$gidlist);
        } else {
            $factors .= xarSession::getVar('role_id');
        }

        if (isset($objectinfo)) {
            $factors .= md5(serialize($objectinfo));
        }

        self::$cacheCode = md5($factors);
        self::$cacheStorage->setCode(self::$cacheCode);

        // Note: we pass along the expiration time here, because it may be different for each object
        $result = self::$cacheStorage->isCached($cacheKey, self::$expireTime);

        return $result;
    }

    /**
     * Get the contents of a object from the cache
     *
     * @access public
     * @param  string $cacheKey the key identifying the particular object you want to access
     */
    public static function getCached($cacheKey)
    {
        if (empty(self::$cacheStorage)) {
            return '';
        }

        // Note: we pass along the expiration time here, because it may be different for each object
        $value = self::$cacheStorage->getCached($cacheKey, 0, self::$expireTime);

        // empty object output is acceptable here ?
        if (!empty($value) && $value === 'isEmptyObject') {
            // the filesystem cache ignores empty files
            $value = '';
        }

        return $value;
    }

    /**
     * Set the contents of a object in the cache
     *
     * @access public
     * @param  string $cacheKey the key identifying the particular object you want to access
     * @param  string $value    the new content for that object
     */
    public static function setCached($cacheKey, $value)
    {
        // CHECKME: watch out for nested objects !
        if (self::$noCache == 1) {
            self::$noCache = '';
            return;
        }

        if (empty(self::$cacheStorage)) {
            return;
        }

        // empty object output is acceptable here ?
        if (empty($value) && $value === '') {
            // the filesystem cache ignores empty files
            $value = 'isEmptyObject';
        }

        if (// the http request is a GET AND
            xarServer::getVar('REQUEST_METHOD') == 'GET' &&
        // CHECKME: do we really want to check this again, or do we ignore it ?
            // the cache entry doesn't exist or has expired (no log here) AND
            !(self::$cacheStorage->isCached($cacheKey, self::$expireTime, 0)) &&
            // the cache collection directory hasn't reached its size limit...
            !(self::$cacheStorage->sizeLimitReached()) ) {

            // Note: we pass along the expiration time here, because it may be different for each object
            self::$cacheStorage->setCached($cacheKey, $value, self::$expireTime);
        }
    }

    /**
     * Flush object cache entries
     */
    public static function flushCached($cacheKey)
    {
        if (empty(self::$cacheStorage)) {
            return;
        }

        self::$cacheStorage->flushCached($cacheKey);
    }

}
?>
