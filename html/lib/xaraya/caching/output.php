<?php
/**
 * Xaraya Output Cache
 *
 * @package core\caching
 * @subpackage caching
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub
 * @author jsb
 */

class xarOutputCache extends xarObject
{
    public static string $cacheDir             = 'var/cache/output';
    public static string $cacheTheme           = '';
    public static int $cacheSizeLimit       = 2097152;
    public static string $cacheCookie          = 'XARAYASID';
    public static string $cacheLocale          = 'en_US.utf-8';

    public static bool $pageCacheIsEnabled   = false;
    public static bool $blockCacheIsEnabled  = false;
    public static bool $moduleCacheIsEnabled = false;
    public static bool $objectCacheIsEnabled = false;

    /**
     * Initialise the caching options
     *
     * @param array<string, mixed> $config caching configuration from config.caching.php
     * @return boolean
     * @todo consider the use of a shutdownhandler for cache maintenance
     */
    public static function init(array $config = [])
    {
        if (empty($config)) {
            return false;
        }

        // specify the output cache directory
        if (empty($config['Output.CacheDir']) || !is_dir($config['Output.CacheDir'])) {
            $config['Output.CacheDir'] = xarCache::$cacheDir . '/output';
        }
        self::$cacheDir       = realpath($config['Output.CacheDir']);
        self::$cacheTheme     = $config['Output.DefaultTheme'] ?? '';
        self::$cacheSizeLimit = $config['Output.SizeLimit'] ?? 2097152;
        self::$cacheCookie    = $config['Output.CookieName'] ?? 'XARAYASID';
        self::$cacheLocale    = $config['Output.DefaultLocale'] ?? 'en_US.utf-8';

        if (file_exists(self::$cacheDir . '/cache.pagelevel')) {
            sys::import('xaraya.caching.output.page');
            // Note : we may already exit here if session-less page caching is enabled
            self::$pageCacheIsEnabled = xarPageCache::init($config);
        }

        if (file_exists(self::$cacheDir . '/cache.blocklevel')) {
            sys::import('xaraya.caching.output.block');
            self::$blockCacheIsEnabled = xarBlockCache::init($config);
        }

        if (file_exists(self::$cacheDir . '/cache.modulelevel')) {
            sys::import('xaraya.caching.output.module');
            self::$moduleCacheIsEnabled = xarModuleCache::init($config);
        }

        if (file_exists(self::$cacheDir . '/cache.objectlevel')) {
            sys::import('xaraya.caching.output.object');
            self::$objectCacheIsEnabled = xarObjectCache::init($config);
        }

        return true;
    }
}
