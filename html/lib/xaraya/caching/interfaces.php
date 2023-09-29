<?php
/**
 * @package core\caching
 * @subpackage caching
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * Interfaces for cache storage
 */

/**
 * @property string $type
 * @property string $namespace
 */
interface ixarCache_Storage
{
    /**
     * Constructor
     * @param array<string, mixed> $args
     */
    public function __construct(array $args = []);

    /**
     * Set the current namespace prefix
     * @param string $namespace
     * @return void
     */
    public function setNamespace($namespace = '');

    /**
     * Set the current Doctrine CacheProvider (for doctrine)
     * @param mixed $provider
     * @return void
     */
    public function setProvider($provider = '');

    /**
     * Set the current code suffix
     * @param string $code
     * @return void
     */
    public function setCode($code = '');

    /**
     * Get the actual cache key used for storage (= including namespace and code)
     * @param string $key
     * @return string
     */
    public function getCacheKey($key = '');

    /**
     * Set the current expiration time (not used by all storage)
     * @param int $expire
     * @return void
     */
    public function setExpire($expire = 0);

    /**
     * Get the last modification time (not supported by all storage)
     * @return int
     */
    public function getLastModTime();

    /**
     * Check if the data is cached
     * @param string $key
     * @param int $expire
     * @param int $log
     * @return bool
     */
    public function isCached($key = '', $expire = 0, $log = 1);

    /**
     * Get the cached data
     * @param string $key
     * @param int $output
     * @param int $expire
     * @return mixed
     */
    public function getCached($key = '', $output = 0, $expire = 0);

    /**
     * Set the cached data
     * @param string $key
     * @param mixed $value
     * @param int $expire
     * @return void
     */
    public function setCached($key = '', $value = '', $expire = 0);

    /**
     * Delete the cached data
     * @param string $key
     * @return void
     */
    public function delCached($key = '');

    /**
     * Get detailed information about the cache key (not supported by all storage)
     * @param string $key
     * @return array<string, mixed>
     */
    public function keyInfo($key = '');

    /**
     * Flush all cache keys that start with this key (= for all code suffixes)
     * @param string $key
     * @return void
     */
    public function flushCached($key = '');

    /**
     * Clean up the cache based on expiration time
     * @param int $expire
     * @return void
     */
    public function cleanCached($expire = 0);

    /**
     * Do garbage collection based on expiration time (not supported by all storage)
     * @param int $expire
     * @return void
     */
    public function doGarbageCollection($expire = 0);

    /**
     * Get information about the cache (not supported by all storage)
     * @return array<string, mixed>
     */
    public function getCacheInfo();

    /**
     * Get the current cache size (not supported by all storage)
     * @param bool $countitems
     * @return int
     */
    public function getCacheSize($countitems = false);

    /**
     * Get the number of items in cache (not supported by all storage)
     * @return int
     */
    public function getCacheItems();

    /**
     * Check if we reached the size limit for this cache (not supported by all storage)
     * @return bool
     */
    public function sizeLimitReached();

    /**
     * Log the HIT / MISS status for cache keys
     * @param string $status
     * @param string $key
     * @return void
     */
    public function logStatus($status = 'MISS', $key = '');

    /**
     * Save the cached data to file
     * @param string $key
     * @param string $filename
     * @return void
     */
    public function saveFile($key = '', $filename = '');

    /**
     * Summary of getCachedList
     * @return array<mixed>
     */
    public function getCachedList();

    /**
     * Summary of getCachedKeys
     * @return array<string, int>
     */
    public function getCachedKeys();
}
