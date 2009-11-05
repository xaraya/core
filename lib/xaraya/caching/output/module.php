<?php
/**
 * Module gui function caching
 *
 * @package core
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage caching
 * @author mikespub
 * @author jsb
**/

class xarModuleCache extends Object
{
    public static $cacheTime      = 7200;
    public static $cacheSizeLimit = 2097152;
    public static $cacheStorage   = null;
    public static $cacheFunctions = null;

    public static $cacheSettings  = null;
    public static $cacheKey       = null; // the current cacheKey
    public static $cacheCode      = null;

    public static $noCache        = null;
    public static $userShared     = null;
    public static $expireTime     = null;
    public static $funcParams     = '';

    public static $setTitle       = array();
    public static $addStyles      = array();
    public static $addScript      = array();

    /**
     * Initialise the module caching options
     *
     * @return bool true on success, false on failure
     */
    public static function init(array $args = array())
    {
        self::$cacheTime = isset($args['Module.TimeExpiration']) ?
            $args['Module.TimeExpiration'] : 7200;
        self::$cacheSizeLimit = isset($args['Module.SizeLimit']) ?
            $args['Module.SizeLimit'] : 2097152;
        self::$cacheFunctions = isset($args['Module.CacheFunctions']) ?
            $args['Module.CacheFunctions'] : array('main' => 1, 'view' => 1, 'display' => 0);

        $storage = !empty($args['Module.CacheStorage']) ?
            $args['Module.CacheStorage'] : 'filesystem';
        $logfile = !empty($args['Module.LogFile']) ?
            $args['Module.LogFile'] : null;
        sys::import('xaraya.caching.storage');
        self::$cacheStorage = xarCache_Storage::getCacheStorage(array('storage'   => $storage,
                                                                      'type'      => 'module',
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
     * Get cache settings for the modules
     * @return array
     */
    public static function getCacheSettings()
    {
        if (!isset(self::$cacheSettings)) {
            $settings = array();
            $serialsettings = xarModVars::get('modules','modulecache_settings');
            if (!empty($serialsettings)) {
                $settings = unserialize($serialsettings);
            }
            self::$cacheSettings = $settings;
        }
        return self::$cacheSettings;
    }

    /**
     * Check if this module is suitable for module caching and return the cacheKey
     *
     * @access public
     * @param  string  $cacheKey   the key identifying the particular module you want to access
     * @param  array   $moduleInfo the module info when using a module UI function or BL tag
     * @returns mixed
     * @return cacheKey if the module is suitable for caching, false if not
     */
    public static function checkCachingRules($cacheKey = null, $moduleInfo = array())
    {
        // we only cache the top-most module function in case of nested functions
        if (!empty(self::$cacheKey)) {
            return false;
        }

        self::$noCache    = null;
        self::$userShared = null;
        self::$expireTime = null;
        self::$funcParams = '';

    // CHECKME: should we allow POST requests here ?

        if (!empty($cacheKey)) {
            list($modname,$func,$itemid) = explode('-', $cacheKey);

        } elseif (!empty($moduleInfo) && !empty($moduleInfo['module']) && !empty($moduleInfo['func'])) {
            $modname = $moduleInfo['module'];
            $func = $moduleInfo['func'];
            if (!empty($moduleInfo['itemid'])) {
                $itemid = $moduleInfo['itemid'];
            } else {
                $itemid = '';
            }

        } else {
            // we have nothing to work with here ?
            return false;
        }

        $settings = self::getCacheSettings();

        if (!empty($settings[$modname]) && !empty($settings[$modname][$func])) {
            self::$noCache    = $settings[$modname][$func]['nocache'];
            self::$userShared = $settings[$modname][$func]['usershared'];
            self::$expireTime = $settings[$modname][$func]['cacheexpire'];
            self::$funcParams = $settings[$modname][$func]['params'];

        // CHECKME: cfr. bug 4021 Override caching vars with module BL tag
        } elseif (!empty($moduleInfo['content']) && is_array($moduleInfo['content'])) {
            if (isset($moduleInfo['content']['nocache'])) {
                self::$noCache    = $moduleInfo['content']['nocache'];
            }
            if (isset($moduleInfo['content']['usershared'])) {
                self::$userShared = $moduleInfo['content']['usershared'];
            }
            if (isset($moduleInfo['content']['cacheexpire'])) {
                self::$expireTime = $moduleInfo['content']['cacheexpire'];
            }
            if (isset($moduleInfo['content']['params'])) {
                self::$funcParams = $moduleInfo['content']['params'];
            }

        } else {
            // this module function is not configured for caching
            return false;
        }

        if (!empty(self::$noCache)) {
            // this module function is configured for nocache
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
        if (empty(self::$funcParams)) {
            $params = array();
        } else {
            $params = explode(',',self::$funcParams);
        }

        // add missing function params to moduleInfo
        foreach ($params as $param) {
            if (!isset($moduleInfo[$param])) {
                xarVarFetch($param, 'isset', $moduleInfo[$param], NULL, XARVAR_NOT_REQUIRED);
            }
        }

        if (!empty($moduleInfo) && !empty($moduleInfo['preview'])) {
            // we don't cache preview
            return false;
        }

        // we should be safe for caching now

    // CHECKME: should we detect the param for the itemid here ?
        if (empty($itemid) && !empty($moduleInfo['itemid'])) {
            $itemid = $moduleInfo['itemid'];
        }

        // set the cacheKey and add the itemid if we have it
        self::$cacheKey = $modname . '-' . $func . '-' . $itemid;

        // set the cacheCode for the current cacheKey
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

        if (isset($moduleInfo)) {
            $factors .= serialize($moduleInfo);
        }
        self::$cacheCode = md5($factors);
        self::$cacheStorage->setCode(self::$cacheCode);

        // return the cacheKey
        return self::$cacheKey;
    }

    /**
     * Check whether a module is cached
     *
     * @access public
     * @param  string  $cacheKey   the key identifying the particular module you want to access
     * @returns bool
     */
    public static function isCached($cacheKey = null)
    {
        if (empty(self::$cacheStorage)) {
            return false;
        }

        if (empty($cacheKey)) {
            return false;

        // we only cache the top-most module function in case of nested functions
        } elseif ($cacheKey != self::$cacheKey) {
            return false;
        }

        // Note: we pass along the expiration time here, because it may be different for each module
        $result = self::$cacheStorage->isCached($cacheKey, self::$expireTime);

        if (empty($result)) {
            // initialize the title, styles and script arrays for the current cacheKey
            self::$setTitle = null;
            self::$addStyles = array();
            self::$addScript = array();
        }

        return $result;
    }

    /**
     * Get the contents of a module from the cache
     *
     * @access public
     * @param  string $cacheKey the key identifying the particular module you want to access
     */
    public static function getCached($cacheKey)
    {
        if (empty(self::$cacheStorage)) {
            return '';
        }

        if (empty($cacheKey)) {
            return 'empty cacheKey in xarModuleCache::getCached';

        // we only cache the top-most module function in case of nested functions
        } elseif ($cacheKey != self::$cacheKey) {
            return 'cacheKey mismatch in xarModuleCache::getCached - please submit a bug report with details of your configuration';
        }

        // Note: we pass along the expiration time here, because it may be different for each module
        $value = self::$cacheStorage->getCached($cacheKey, 0, self::$expireTime);

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
     * Set the contents of a module in the cache
     *
     * @access public
     * @param  string $cacheKey the key identifying the particular module you want to access
     * @param  string $value    the new content for that module
     */
    public static function setCached($cacheKey, $value)
    {
        if (empty(self::$cacheStorage)) {
            return;
        }

        if (empty($cacheKey)) {
            return;

        // we only cache the top-most module function in case of nested functions
        } elseif ($cacheKey != self::$cacheKey) {
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
                $value = "<!-- start cache: module/" . $cacheKey . ' ' . self::$cacheCode . " -->\n"
                         . $value
                         . "<!-- end cache: module/" . $cacheKey . ' ' . self::$cacheCode . " -->\n";
            }

            $content = array('output' => $value,
                             'title'  => self::$setTitle,
                             'styles' => self::$addStyles,
                             'script' => self::$addScript);
            $value = serialize($content);

            // Note: we pass along the expiration time here, because it may be different for each module
            self::$cacheStorage->setCached($cacheKey, $value, self::$expireTime);
        }
        // we're done with this cacheKey
        self::$cacheKey = null;
    }

    /**
     * Flush module cache entries
     */
    public static function flushCached($cacheKey)
    {
        if (empty(self::$cacheStorage)) {
            return;
        }

        self::$cacheStorage->flushCached($cacheKey);
    }

    /**
     * The module function set some page title - see xarTplSetPageTitle()
     */
    public static function setTitle($title = NULL, $module = NULL)
    {
        if (empty(self::$cacheKey)) return;
        self::$setTitle = array($title, $module);
    }

    /**
     * The module function added some stylesheet - see xarMod::apiFunc('themes','user','register')
     */
    public static function addStyle($args)
    {
        if (empty(self::$cacheKey)) return;
        self::$addStyles[] = $args;
    }

    /**
     * The module function added some javascript - see xarTplAddJavaScript()
     */
    public static function addScript($position, $type, $data, $index = '')
    {
        if (empty(self::$cacheKey)) return;
        self::$addScript[] = array($position, $type, $data, $index = '');
    }
}
?>
