<?php
/**
 * Xaraya Output Cache
 *
 * @package core
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage caching
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

    public static $pageCacheIsEnabled   = false;
    public static $blockCacheIsEnabled  = false;
    public static $moduleCacheIsEnabled = false;
    public static $objectCacheIsEnabled = false;

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
            sys::import('xaraya.caching.output.page');
            // Note : we may already exit here if session-less page caching is enabled
            self::$pageCacheIsEnabled = xarPageCache::init($cachingConfiguration);
        }

        if (file_exists($cacheDir . '/cache.blocklevel')) {
            sys::import('xaraya.caching.output.block');
            self::$blockCacheIsEnabled = xarBlockCache::init($cachingConfiguration);
        }

        if (file_exists($cacheDir . '/cache.modulelevel')) {
            sys::import('xaraya.caching.output.module');
            self::$moduleCacheIsEnabled = xarModuleCache::init($cachingConfiguration);
        }

        if (file_exists($cacheDir . '/cache.objectlevel')) {
            sys::import('xaraya.caching.output.object');
            self::$objectCacheIsEnabled = xarObjectCache::init($cachingConfiguration);
        }

        return true;
    }

}

?>
