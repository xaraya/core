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
    public static $cacheTime      = 7200;
    public static $cacheSizeLimit = 2097152;
    public static $cacheStorage   = null;
    public static $cacheMethods   = null;

    public static $cacheSettings  = null;
    public static $cacheKey       = '';
    public static $cacheCode      = '';

    public static $noCache        = null;
    public static $userShared     = null;
    public static $expireTime     = null;

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
        self::$cacheMethods = isset($args['Object.CacheMethods']) ?
            $args['Object.CacheMethods'] : array('view' => 1, 'display' => 1);

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
            $serialsettings = xarModVars::get('dynamicdata','objectcache_settings');
            if (!empty($serialsettings)) {
                $settings = unserialize($serialsettings);
            }
            self::$cacheSettings = $settings;
        }
        return self::$cacheSettings;
    }

    /**
     * Check if this object is suitable for object caching and return the cacheKey
     *
     * @access public
     * @param  string  $cacheKey   the key identifying the particular object you want to access
     * @param  array   $objectInfo the object info when using a object UI method or BL tag
     * @returns mixed
     * @return cacheKey if the object is suitable for caching, false if not
     */
    public static function checkCachingRules($cacheKey = null, $objectInfo = array())
    {
        // CHECKME: watch out for nested objects !
        self::$cacheKey   = null;
        self::$noCache    = null;
        self::$userShared = null;
        self::$expireTime = null;

        if (!empty($cacheKey)) {
            list($objectname,$method,$itemid) = explode('-', $cacheKey);

        } elseif (!empty($objectInfo) && !empty($objectInfo['object']) && !empty($objectInfo['method'])) {
            $objectname = $objectInfo['object'];
            $method = $objectInfo['method'];
            if (!empty($objectInfo['itemid'])) {
                $itemid = $objectInfo['itemid'];
            } else {
                $itemid = '';
            }
            $cacheKey = $objectname .'-' . $method . '-' . $itemid;

        } else {
            // we have nothing to work with here ?
            return false;
        }

        if (!empty($objectInfo) && !empty($objectInfo['preview'])) {
            // we don't cache preview
            return false;
        }

        $settings = self::getCacheSettings();

        if (!empty($settings[$objectname]) && !empty($settings[$objectname][$method])) {
            self::$noCache    = $settings[$objectname][$method]['nocache'];
            self::$userShared = $settings[$objectname][$method]['usershared'];
            self::$expireTime = $settings[$objectname][$method]['cacheexpire'];

        // CHECKME: cfr. bug 4021 Override caching vars with object BL tag
        } elseif (!empty($objectInfo['content']) && is_array($objectInfo['content'])) {
            if (isset($objectInfo['content']['nocache'])) {
                self::$noCache    = $objectInfo['content']['nocache'];
            }
            if (isset($objectInfo['content']['usershared'])) {
                self::$userShared = $objectInfo['content']['usershared'];
            }
            if (isset($objectInfo['content']['cacheexpire'])) {
                self::$expireTime = $objectInfo['content']['cacheexpire'];
            }

        // check against default methods 
        } elseif (!empty(self::$cacheMethods) && isset(self::$cacheMethods[$method])) {
            // flip from docache in config to nocache in settings
            if (!empty(self::$cacheMethods[$method])) {
                self::$noCache    = 0;
            } else {
                self::$noCache    = 1;
            }

        } else {
            // this object method is not configured for caching
            return false;
        }

        if (!empty(self::$noCache)) {
            // this object method is configured for nocache
            return false;
        } else {
            self::$noCache = 0;
        }
        if (empty(self::$userShared)) {
            self::$userShared = 0;
        }
        if (!isset(self::$expireTime)) {
            self::$expireTime = self::$cacheTime;
        }

        // set the current cacheKey to avoid doing this twice
        self::$cacheKey = $cacheKey;

        // return the cacheKey
        return $cacheKey;
    }

    /**
     * Check whether a object is cached
     *
     * @access public
     * @param  string  $cacheKey   the key identifying the particular object you want to access
     * @param  array   $objectInfo the object info when using a object UI method or BL tag
     * @returns bool
     */
    public static function isCached($cacheKey = null, $objectInfo = array())
    {
        if (empty(self::$cacheStorage)) {
            return false;
        }

        // Check if this object is suitable for object caching if this wasn't done earlier
        if (empty($cacheKey) || empty(self::$cacheKey) || $cacheKey != self::$cacheKey) {
            $cacheKey = self::checkCachingRules($cacheKey, $objectInfo);
        }
        if (empty($cacheKey)) {
            self::$noCache = 1;
            return false;
        }

        $xarTpl_themeDir = xarTplGetThemeDir();

        $factors = xarServer::getVar('HTTP_HOST') . $xarTpl_themeDir .
                   xarUserGetNavigationLocale();

        if (self::$userShared == 2) {
            $factors .= 0;
        } elseif (self::$userShared == 1) {
            $gidlist = xarCache_getParents();
            $factors .= join(';',$gidlist);
        } else {
            $factors .= xarSession::getVar('role_id');
        }

        if (isset($objectInfo)) {
            $factors .= serialize($objectInfo);
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
