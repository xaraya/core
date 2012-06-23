<?php
/**
 * Block caching
 * 
 * @package core
 * @subpackage caching
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author mikespub
 * @author jsb
**/

class xarBlockCache extends Object
{
    public static $cacheTime      = 7200;
    public static $cacheSizeLimit = 2097152;
    public static $cacheStorage   = null;

    public static $cacheSettings  = null;
    public static $cacheKey       = null;
    public static $cacheCode      = null;

    public static $noCache        = null;
    public static $pageShared     = null;
    public static $userShared     = null;
    public static $expireTime     = null;

    /**
     * Initialise the block caching options
     *
     * @return boolean true on success, false on failure
     */
    public static function init(array $args = array())
    {
        self::$cacheTime = isset($args['Block.TimeExpiration']) ?
            $args['Block.TimeExpiration'] : 7200;
        self::$cacheSizeLimit = isset($args['Block.SizeLimit']) ?
            $args['Block.SizeLimit'] : 2097152;

        $storage = !empty($args['Block.CacheStorage']) ?
            $args['Block.CacheStorage'] : 'filesystem';
        $logfile = !empty($args['Block.LogFile']) ?
            $args['Block.LogFile'] : null;
        self::$cacheStorage = xarCache::getStorage(array('storage'   => $storage,
                                                         'type'      => 'block',
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
     * Get a cache key if this block is suitable for output caching
     *
     * @param  array  $blockInfo block information with module, type, id etc.
     * @return mixed  cacheKey to be used with (is|get|set)Cached, or null if not applicable
     */
    public static function getCacheKey($blockInfo)
    {
        if (empty(self::$cacheStorage)) {
            return;
        }

        // Check if this block is suitable for block caching
        if (!(self::checkCachingRules($blockInfo))) {
            return;
        }

        // we should be safe for caching now

        if (empty($blockInfo['name'])) {
            $blockInfo['name'] = '';
        }

        // set the current cacheKey
        self::$cacheKey = $blockInfo['module'] . '-' . $blockInfo['type'] . '-' . $blockInfo['name'];

        // set the cacheCode for the current cacheKey

        // the output depends on the current host, theme and locale
        $factors = xarServer::getVar('HTTP_HOST') . xarTpl::getThemeDir() .
                   xarUserGetNavigationLocale();

        // add page identifier if needed
        if (self::$pageShared == 0) {
            $factors .= xarServer::getVar('REQUEST_URI');
            $param = xarServer::getVar('QUERY_STRING');
            if (!empty($param)) {
                $factors .= '?' . $param;
            }
        }

        // add group or user identifier if needed
        if (self::$userShared == 2) {
            $factors .= 0;
        } elseif (self::$userShared == 1) {
            $gidlist = xarCache::getParents();
            $factors .= join(';',$gidlist);
        } else {
            $factors .= xarSession::getVar('role_id');
        }

        // add block information
        $factors .= serialize($blockInfo);

        self::$cacheCode = md5($factors);
        self::$cacheStorage->setCode(self::$cacheCode);

        // return the cacheKey
        return self::$cacheKey;
    }

    /**
     * Get cache settings for the blocks
     * @return array
     */
    /* As of soloblocks each block carries its own settings which we get from blockinfo
    public static function getCacheSettings()
    {
        if (!isset(self::$cacheSettings)) {
            $settings = array();
            // We need to get it.
            $blocksettings = xarDB::getPrefix() . '_cache_blocks';
            $dbconn = xarDB::getConn();
            $tables = $dbconn->MetaTables();
            if (in_array($blocksettings, $tables)) {
                $query = "SELECT blockinstance_id, nocache,
                                 page, theuser, expire
                         FROM $blocksettings";
                $stmt = $dbconn->prepareStatement($query);
                $result = $stmt->executeQuery();
                if ($result) {
                    while ($result->next()) {
                        list ($bid,
                              $noCache,
                              $pageShared,
                              $userShared,
                              $expireTime) = $result->getRow();
                        $settings[$bid] = array('bid'         => $bid,
                                                'nocache'     => $noCache,
                                                'pageshared'  => $pageShared,
                                                'usershared'  => $userShared,
                                                'cacheexpire' => $expireTime);
                    }
                    $result->close();
                }
            }
            self::$cacheSettings = $settings;
        }
        return self::$cacheSettings;
    }
    */

    /**
     * Check if this block is suitable for block caching
     *
     * @param  array $blockInfo block information with module, type, id etc.
     * @return boolean  true if the block is suitable for caching, false if not
     */
    public static function checkCachingRules($blockInfo = array())
    {
        // we only cache the top-most block in case of nested blocks
        if (!empty(self::$cacheKey)) {
            return false;
        }
        
        if (empty($blockInfo['type']))
            return false;
        /* As of soloblocks we can oly rely on type being present
        if (empty($blockInfo['module']) || empty($blockInfo[''])) {
            return false;
        }
        */

        self::$noCache    = null;
        self::$pageShared = null;
        self::$userShared = null;
        self::$expireTime = null;

        /* As of soloblocks each block carries its own settings which we get from blockinfo
        $settings = self::getCacheSettings();

        $blockid = $blockInfo['bid'];
        
        if (isset($settings[$blockid])) {
            self::$noCache    = $settings[$blockid]['nocache'];
            self::$pageShared = $settings[$blockid]['pageshared'];
            self::$userShared = $settings[$blockid]['usershared'];
            self::$expireTime = $settings[$blockid]['cacheexpire'];

        // CHECKME: cfr. bug 4021 Override caching vars with block BL tag
        } else
        */
        if (!empty($blockInfo['content']) && is_array($blockInfo['content'])) {
            if (isset($blockInfo['content']['nocache'])) {
                self::$noCache    = $blockInfo['content']['nocache'];
            }
            if (isset($blockInfo['content']['pageshared'])) {
                self::$pageShared = $blockInfo['content']['pageshared'];
            }
            if (isset($blockInfo['content']['usershared'])) {
                self::$userShared = $blockInfo['content']['usershared'];
            }
            if (isset($blockInfo['content']['cacheexpire'])) {
                self::$expireTime = $blockInfo['content']['cacheexpire'];
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
     * Check whether a block is cached
     *
     * @param  string $cacheKey the key identifying the particular block you want to access
     * @return boolean   true if the block is available in cache, false if not
     */
    public static function isCached($cacheKey)
    {
        if (empty(self::$cacheStorage)) {
            return false;
        }

        // we only cache the top-most block in case of nested blocks
        if (empty($cacheKey) || $cacheKey != self::$cacheKey) {
            return false;
        }

        // Note: we pass along the expiration time here, because it may be different for each block
        $result = self::$cacheStorage->isCached($cacheKey, self::$expireTime);

        return $result;
    }

    /**
     * Get the contents of a block from the cache
     *
     * @param  string $cacheKey the key identifying the particular block you want to access
     * @return string the cached output of the block
     */
    public static function getCached($cacheKey)
    {
        if (empty(self::$cacheStorage)) {
            return '';
        }

        // we only cache the top-most block in case of nested blocks
        if (empty($cacheKey) || $cacheKey != self::$cacheKey) {
            return '';
        }

        // Note: we pass along the expiration time here, because it may be different for each block
        $value = self::$cacheStorage->getCached($cacheKey, 0, self::$expireTime);

        // we're done with this cacheKey
        self::$cacheKey = null;

        // empty blocks are acceptable here
        if (!empty($value) && $value === 'isEmptyBlock') {
            // the filesystem cache ignores empty files
            $value = '';
        }

        return $value;
    }

    /**
     * Set the contents of a block in the cache
     *
     * @param  string $cacheKey the key identifying the particular block you want to access
     * @param  string $value    the new content for that block
     * @return void
     */
    public static function setCached($cacheKey, $value)
    {
        if (empty(self::$cacheStorage)) {
            return;
        }

        // we only cache the top-most block in case of nested blocks
        if (empty($cacheKey) || $cacheKey != self::$cacheKey) {
            return;
        }

        // empty blocks are acceptable here
        if (empty($value) && $value === '') {
            // the filesystem cache ignores empty files
            $value = 'isEmptyBlock';
        }

        if (// the http request is a GET AND
            xarServer::getVar('REQUEST_METHOD') == 'GET' &&
        // CHECKME: do we really want to check this again, or do we ignore it ?
            // the cache entry doesn't exist or has expired (no log here) AND
            !(self::$cacheStorage->isCached($cacheKey, self::$expireTime, 0)) &&
            // the cache collection directory hasn't reached its size limit...
            !(self::$cacheStorage->sizeLimitReached()) ) {

            // Note: we pass along the expiration time here, because it may be different for each block
            self::$cacheStorage->setCached($cacheKey, $value, self::$expireTime);
        }

        // we're done with this cacheKey
        self::$cacheKey = null;
    }

    /**
     * Flush block cache entries
     * @return void
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
