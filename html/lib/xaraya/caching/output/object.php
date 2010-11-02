<?php
/**
 * Object gui method caching
 *
 * @package core
 * @subpackage caching
 * @copyright see the html/credits.html file in this release
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author mikespub
 * @author jsb
**/

class xarObjectCache extends Object
{
    public static $cacheTime      = 7200;
    public static $cacheSizeLimit = 2097152;
    public static $cacheMethods   = null;
    public static $cacheStorage   = null;

    public static $cacheSettings  = null;
    public static $cacheKey       = null;
    public static $cacheCode      = null;

    public static $noCache        = null;
    public static $userShared     = null;
    public static $expireTime     = null;

    public static $pageTitle      = array();
    public static $styleList      = array();
    public static $scriptList     = array();

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
        self::$cacheStorage = xarCache::getStorage(array('storage'   => $storage,
                                                         'type'      => 'object',
                                                         // we store output cache files under this
                                                         'cachedir'  => xarOutputCache::$cacheDir,
                                                         'expire'    => self::$cacheTime,
                                                         'sizelimit' => self::$cacheSizeLimit,
                                                         'logfile'   => $logfile));
        if (empty(self::$cacheStorage)) {
            return false;
        }

        return true;
    }

    /**
     * Get a cache key if this object method is suitable for output caching
     *
     * @access public
     * @param string $objectName string registered name of object
     * @param string $methodName string specific method to run
     * @param array  $args arguments to pass to the method
     * @return mixed cacheKey to be used with (is|get|set)Cached, or null if not applicable
     */
    public static function getCacheKey($objectName, $methodName = 'view', $args = array())
    {
        if (empty(self::$cacheStorage)) {
            return;
        }

        if (empty($objectName) || empty($methodName)) {
            return;
        }

        // Check if this object method is suitable for object caching
        if (!(self::checkCachingRules($objectName, $methodName, $args))) {
            return;
        }

        if (!empty($args['preview'])) {
            // we don't cache preview
            return false;
        }

        // we should be safe for caching now

        // set the current cacheKey
        self::$cacheKey = $objectName . '-' . $methodName . '-';

    // CHECKME: should we detect the param for the itemid here ?
        if (!empty($args['itemid'])) {
            self::$cacheKey .= $args['itemid'];
        }

        // set the cacheCode for the current cacheKey

        // the output depends on the current host, theme and locale
        $factors = xarServer::getVar('HTTP_HOST') . xarTplGetThemeDir() .
                   xarUserGetNavigationLocale();

        // add group or user identifier if needed
        if (self::$userShared == 2) {
            $factors .= 0;
        } elseif (self::$userShared == 1) {
            $gidlist = xarCache::getParents();
            $factors .= join(';',$gidlist);
        } else {
            $factors .= xarSession::getVar('role_id');
        }

        // add the method args
        $factors .= serialize($args);

        self::$cacheCode = md5($factors);
        self::$cacheStorage->setCode(self::$cacheCode);

        // return the cacheKey
        return self::$cacheKey;
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
     * Check if this object method is suitable for object caching
     *
     * @param string $objectName string registered name of object
     * @param string $methodName string specific method to run
     * @param array  $args arguments to pass to the method
     * @return bool  true if the module function is suitable for caching, false if not
     */
    public static function checkCachingRules($objectName, $methodName = 'view', $args = array())
    {
        // we only cache the top-most object method in case of nested methods
        if (!empty(self::$cacheKey)) {
            return false;
        }

        self::$noCache    = null;
        self::$userShared = null;
        self::$expireTime = null;

    // CHECKME: should we allow POST requests here ?

        $settings = self::getCacheSettings();

        if (!empty($settings[$objectName]) && !empty($settings[$objectName][$methodName])) {
            self::$noCache    = $settings[$objectName][$methodName]['nocache'];
            self::$userShared = $settings[$objectName][$methodName]['usershared'];
            self::$expireTime = $settings[$objectName][$methodName]['cacheexpire'];

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

        return true;
    }

    /**
     * Check whether a object is cached
     *
     * @access public
     * @param  string $cacheKey the key identifying the particular object you want to access
     * @return bool   true if the object is available in cache, false if not
     */
    public static function isCached($cacheKey = null)
    {
        if (empty(self::$cacheStorage)) {
            return false;
        }

        // we only cache the top-most object method in case of nested methods
        if (empty($cacheKey) || $cacheKey != self::$cacheKey) {
            return false;
        }

        // Note: we pass along the expiration time here, because it may be different for each object
        $result = self::$cacheStorage->isCached($cacheKey, self::$expireTime);

        if (empty($result)) {
            // initialize the title, styles and script arrays for the current cacheKey
            self::$pageTitle = null;
            self::$styleList = array();
            self::$scriptList = array();
        }

        return $result;
    }

    /**
     * Get the contents of a object from the cache
     *
     * @access public
     * @param  string $cacheKey the key identifying the particular object you want to access
     * @return string the cached output of the object method
     */
    public static function getCached($cacheKey)
    {
        if (empty(self::$cacheStorage)) {
            return '';
        }

        // we only cache the top-most object method in case of nested methods
        if (empty($cacheKey) || $cacheKey != self::$cacheKey) {
            return 'cacheKey mismatch in xarObjectCache::getCached - please submit a bug report with details of your configuration';
        }

        // Note: we pass along the expiration time here, because it may be different for each object
        $value = self::$cacheStorage->getCached($cacheKey, 0, self::$expireTime);

        // we're done with this cacheKey
        self::$cacheKey = null;

        $content = unserialize($value);
        if (!empty($content['title']) && is_array($content['title'])) {
            xarTplSetPageTitle($content['title'][0], $content['title'][1]);
        }
        if (!empty($content['styles']) && is_array($content['styles'])) {
            foreach ($content['styles'] as $info) {
                xarMod::apiFunc('themes','user','register',$info);
            }
        }
        if (!empty($content['script']) && is_array($content['script'])) {
            foreach ($content['script'] as $info) {
                xarTplAddJavaScript($info[0], $info[1], $info[2], $info[2]);
            }
        }
        return $content['output'];
    }

    /**
     * Set the contents of a object in the cache
     *
     * @access public
     * @param  string $cacheKey the key identifying the particular object you want to access
     * @param  string $value    the new content for that object
     * @return void
     */
    public static function setCached($cacheKey, $value)
    {
        if (empty(self::$cacheStorage)) {
            return;
        }

        // we only cache the top-most object method in case of nested methods
        if (empty($cacheKey) || $cacheKey != self::$cacheKey) {
            return;
        }

        if (// the http request is a GET AND
            xarServer::getVar('REQUEST_METHOD') == 'GET' &&
        // CHECKME: do we really want to check this again, or do we ignore it ?
            // the cache entry doesn't exist or has expired (no log here) AND
            !(self::$cacheStorage->isCached($cacheKey, self::$expireTime, 0)) &&
            // the cache collection directory hasn't reached its size limit...
            !(self::$cacheStorage->sizeLimitReached()) ) {

            // CHECKME: add cacheKey cacheCode in comments if template filenames are already added
            if (xarTpl_outputTemplateFilenames()) {
                // separate with space here - we must avoid issues with double -- !?
                $value = "<!-- start cache: object/" . $cacheKey . ' ' . self::$cacheCode . " -->\n"
                         . $value
                         . "<!-- end cache: object/" . $cacheKey . ' ' . self::$cacheCode . " -->\n";
            }

            $content = array('output' => $value,
                             'link'   => xarServer::getCurrentURL(),
                             'title'  => self::$pageTitle,
                             'styles' => self::$styleList,
                             'script' => self::$scriptList);
            $value = serialize($content);

            // Note: we pass along the expiration time here, because it may be different for each object
            self::$cacheStorage->setCached($cacheKey, $value, self::$expireTime);
        }

        // we're done with this cacheKey
        self::$cacheKey = null;
    }

    /**
     * Flush object cache entries
     * @return void
     */
    public static function flushCached($cacheKey)
    {
        if (empty(self::$cacheStorage)) {
            return;
        }

        self::$cacheStorage->flushCached($cacheKey);
    }

    /**
     * Keep track of some page title for caching - see xarTplSetPageTitle()
     * @return void
     */
    public static function setPageTitle($title = NULL, $module = NULL)
    {
        if (empty(self::$cacheKey)) return;
        self::$pageTitle = array($title, $module);
    }

    /**
     * Keep track of some stylesheet for caching - see xarMod::apiFunc('themes','user','register')
     * @return void
     */
    public static function addStyle($args)
    {
        if (empty(self::$cacheKey)) return;
        self::$styleList[] = $args;
    }

    /**
     * Keep track of some javascript for caching - see xarTplAddJavaScript()
     * @return void
     */
    public static function addJavaScript($position, $type, $data, $index = '')
    {
        if (empty(self::$cacheKey)) return;
        self::$scriptList[] = array($position, $type, $data, $index = '');
    }
}
?>
