<?php
/**
 * Block caching
 * 
 * @package core
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage caching
 * @author mikespub
 * @author jsb
**/

class xarBlockCache extends Object
{
    public static $cacheTime = 7200;
    public static $cacheSizeLimit = 2097152;

    public static $cacheStorage = null;
    public static $cacheCode = '';
    public static $noCache = 0;
    public static $expireTime = null;

    /**
     * Initialise the block caching options
     *
     * @return bool true on success, false on failure
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
        sys::import('xaraya.caching.storage');
        self::$cacheStorage = xarCache_Storage::getCacheStorage(array('storage'   => $storage,
                                                                      'type'      => 'block',
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
     * Get cache settings for the blocks
     * @return array
     */
    public static function getSettings()
    {
        if (xarCore::isCached('Blocks.Caching', 'settings')) {
            $blocks = xarCore::getCached('Blocks.Caching', 'settings');
        } else {
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
                    $blocks = array();
                    while ($result->next()) {
                        list ($bid,
                              $noCache,
                              $pageShared,
                              $userShared,
                              $expireTime) = $result->getRow();
                        $blocks[$bid] = array('bid'         => $bid,
                                              'nocache'     => $noCache,
                                              'pageshared'  => $pageShared,
                                              'usershared'  => $userShared,
                                              'cacheexpire' => $expireTime);
                    }
                    $result->close();
                } else {
                    $blocks = 'noSettings';
                }
            } else {
                $blocks = 'noSettings';
            }
            xarCore::setCached('Blocks.Caching', 'settings', $blocks);
        }
        return $blocks;
    }

    /**
     * Check whether a block is cached
     *
     * @access public
     * @param  string $cacheKey the key identifying the particular block you want to access
     * @param  integer $blockid
     * @param  array $blockinfo
     * @return bool
     */
    public static function isCached($cacheKey, $blockid = 0, $blockinfo = array())
    {
        if (empty(self::$cacheStorage)) {
            return false;
        }

        $blocks = self::getSettings();

        if (isset($blocks[$blockid])) {
            $noCache = $blocks[$blockid]['nocache'];
            $pageShared = $blocks[$blockid]['pageshared'];
            $userShared = $blocks[$blockid]['usershared'];
            self::$expireTime = $blocks[$blockid]['cacheexpire'];

        // CHECKME: cfr. bug 4021 Override caching vars with block BL tag
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
                self::$expireTime = $blockinfo['content']['cacheexpire'];
            }
        }

        if (!empty($noCache)) {
            self::$noCache = 1;
            return false;
        }
        if (empty($pageShared)) {
            $pageShared = 0;
        }
        if (empty($userShared)) {
            $userShared = 0;
        }
        if (!isset(self::$expireTime)) {
            self::$expireTime = self::$cacheTime;
        }

        $xarTpl_themeDir = xarTplGetThemeDir();

        $factors = xarServer::getVar('HTTP_HOST') . $xarTpl_themeDir .
                   xarUserGetNavigationLocale();

        if ($pageShared == 0) {
            $factors .= xarServer::getVar('REQUEST_URI');
            $param = xarServer::getVar('QUERY_STRING');
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
            $factors .= xarSessionGetVar('id');
        }

        if (isset($blockinfo)) {
            $factors .= md5(serialize($blockinfo));
        }

        self::$cacheCode = md5($factors);
        self::$cacheStorage->setCode(self::$cacheCode);

        // Note: we pass along the expiration time here, because it may be different for each block
        $result = self::$cacheStorage->isCached($cacheKey, self::$expireTime);

        return $result;
    }

    /**
     * Get the contents of a block from the cache
     *
     * @access public
     * @param  string $cacheKey the key identifying the particular block you want to access
     */
    public static function getCached($cacheKey)
    {
        if (empty(self::$cacheStorage)) {
            return '';
        }

        // Note: we pass along the expiration time here, because it may be different for each block
        $value = self::$cacheStorage->getCached($cacheKey, 0, self::$expireTime);

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
     * @access public
     * @param  string $cacheKey the key identifying the particular block you want to access
     * @param  string $value    the new content for that block
     */
    public static function setCached($cacheKey, $value)
    {
        if (self::$noCache == 1) {
            self::$noCache = '';
            return;
        }

        if (empty(self::$cacheStorage)) {
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
    }

    /**
     * Flush block cache entries
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
