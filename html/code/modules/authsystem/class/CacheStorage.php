<?php

/**
 * Authsystem Module
 *
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.9.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/42.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\Authentication;

use Xaraya\Context\Context;
use Xaraya\Context\RequestContext;
use Xaraya\Services\WithServicesTrait;
use Xaraya\Services\xar;
use ixarCache_Storage;

/**
 * Cache Storage
 */
class CacheStorage
{
    use WithServicesTrait;

    public static string $storageType = 'apcu';  // database or apcu
    public static string $cacheType = 'OVERRIDE';
    public static int $cacheExpire = 12 * 60 * 60;  // 12 hours
    public static int $cacheSize = 10000000;  // 10 MB
    public static ?ixarCache_Storage $cacheStorage = null;
    public static string $fieldName = 'userId';

    public function __construct($xar = null)
    {
        $this->setServicesClass($xar);
    }

    /**
     * Summary of getUserId
     * @param string $id
     * @return int|null
     */
    public function getUserId($id)
    {
        $userInfo = $this->getUserInfo($id);
        if (empty($userInfo) || empty($userInfo[static::$fieldName])) {
            return null;
        }
        return intval($userInfo[static::$fieldName]);
    }

    /**
     * Summary of getItem
     * @param string $id
     * @return array<string, mixed>|null
     */
    public function getUserInfo($id)
    {
        if (empty($id) || !($this->getCacheStorage()->isCached($id))) {
            return null;
        }
        $item = $this->getCacheStorage()->getCached($id);
        if (empty($item)) {
            return null;
        }
        $item = json_decode($item, true, 512, JSON_THROW_ON_ERROR);
        if (!empty($item[static::$fieldName]) && ($item['created'] > (time() - static::$cacheExpire))) {
            return $item;
        }
        return null;
    }

    /**
     * Summary of createItem
     * @param array<string, mixed> $item
     * @return string
     */
    public function createItem($item, $id = null)
    {
        $id ??= bin2hex(random_bytes(16));
        $item['created'] = time();
        $item['updated'] = $item['created'];
        // @checkme clean up cachestorage occasionally based on size
        $this->getCacheStorage()->sizeLimitReached();
        $this->getCacheStorage()->setCached($id, json_encode($item));
        return $id;
    }

    /**
     * Summary of updateItem
     * @param string $id
     * @param array<string, mixed> $item
     * @return void
     */
    public function updateItem($id, $item)
    {
        $item['updated'] = time();
        $this->getCacheStorage()->setCached($id, json_encode($item));
    }

    /**
     * Summary of deleteItem
     * @param string $id
     * @return void
     */
    public function deleteItem($id)
    {
        if (empty($id) || !($this->getCacheStorage()->isCached($id))) {
            return;
        }
        $this->getCacheStorage()->delCached($id);
    }

    /**
     * Summary of getCacheStorage
     * @uses xar::cache()->getStorage()
     * @return ixarCache_Storage
     */
    public function getCacheStorage()
    {
        if (!isset(static::$cacheStorage)) {
            $xar = $this->getServicesClass();
            //static::loadConfig();
            // @checkme access cachestorage directly here
            static::$cacheStorage = $xar->cache()->getStorage([
                'storage' => static::$storageType,
                'type' => static::$cacheType,
                'expire' => static::$cacheExpire,
                'sizelimit' => static::$cacheSize,
            ]);
        }
        return static::$cacheStorage;
    }
}
