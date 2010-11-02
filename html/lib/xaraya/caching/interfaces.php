<?php
/**
 * @package core
 * @subpackage caching
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * 
 * Interfaces for cache storage
 */

interface ixarCache_Storage
{
    public function __construct(Array $args = array());
    public function setNamespace($namespace = '');
    public function setCode($code = '');
    public function getCacheKey($key = '');
    public function setExpire($expire = 0);
    public function getLastModTime();
    public function isCached($key = '', $expire = 0, $log = 1);
    public function getCached($key = '', $output = 0, $expire = 0);
    public function setCached($key = '', $value = '', $expire = 0);
    public function delCached($key = '');
    public function flushCached($key = '');
    public function cleanCached($expire = 0);
    public function doGarbageCollection($expire = 0);
    public function getCacheInfo();
    public function getCacheSize($countitems = false);
    public function getCacheItems();
    public function sizeLimitReached();
    public function logStatus($status = 'MISS', $key = '');
    public function saveFile($key = '', $filename = '');
    public function getCachedList();
    public function getCachedKeys();
}
?>