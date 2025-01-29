<?php

/**
 * Make Caching Service available via facade (WIP)
 *
 * Classes that don't use ServicesInterface like xarMod(), xarUser() etc.
 * can more easily replace (most common) static xarCache::* method calls if
 * they use \Xaraya\Facades\xarCache3; instead
 *
 * @package core\facades
 * @subpackage facades
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Facades;

use Xaraya\Services\CachingInterface;
use Xaraya\Services\ServiceFactory;
use sys;

sys::import('xaraya.services.caching');
sys::import('xaraya.services.servicefactory');

/**
 * Make Caching Service available via facade - xarCache3:: static methods
 * similar to traditional xarCache::* method calls
 */
class xarCache3
{
    /** @var ?CachingInterface */
    protected static $xarCache = null;         // Access caching service with instance methods

    public static function getInstance(): CachingInterface
    {
        self::$xarCache ??= ServiceFactory::getCachingService(__METHOD__);
        return self::$xarCache;
    }

    /**
     * Get a cache key for module output caching
     * @param array<string, mixed> $args optional parameters
     * @return string|null cacheKey to be used with $this->cache()->(has|get|set)Module, or null if not applicable
     */
    public static function getModuleKey(string $modName, string $modType = 'user', string $funcName = 'main', array $args = []): string|null
    {
        return self::getInstance()->getModuleKey($modName, $modType, $funcName, $args);
    }

    /**
     * Check if the output of an module function is cached
     */
    public static function hasModule(?string $cacheKey): bool
    {
        return self::getInstance()->hasModule($cacheKey);
    }

    /**
     * Get the output of the module function from cache
     */
    public static function getModule(string $cacheKey): string
    {
        return self::getInstance()->getModule($cacheKey);
    }

    /**
     * Set the output of the module function in cache
     */
    public static function setModule(?string $cacheKey, string $value): void
    {
        self::getInstance()->setModule($cacheKey, $value);
    }

    /**
     * Get a cache key for object output caching
     * @param array<string, mixed> $args optional parameters
     * @return string|null cacheKey to be used with $this->cache()->(has|get|set)Object, or null if not applicable
     */
    public static function getObjectKey(string $objectName, string $methodName = 'view', array $args = []): string|null
    {
        return self::getInstance()->getObjectKey($objectName, $methodName, $args);
    }

    /**
     * Check if the output of an object method is cached
     */
    public static function hasObject(?string $cacheKey): bool
    {
        return self::getInstance()->hasObject($cacheKey);
    }

    /**
     * Get the output of the object method from cache
     */
    public static function getObject(string $cacheKey): string
    {
        return self::getInstance()->getObject($cacheKey);
    }

    /**
     * Set the output of the object method in cache
     */
    public static function setObject(?string $cacheKey, string $value): void
    {
        self::getInstance()->setObject($cacheKey, $value);
    }

    /**
     * Get a cache key for variable value caching
     * @return string|null cacheKey to be used with xarVariableCache::(is|get|set)Cached, or null if not applicable
     */
    public static function getVariableKey(string $scope, string $name): string|null
    {
        return self::getInstance()->getVariableKey($scope, $name);
    }

    /**
     * Check if a variable value is cached
     */
    public static function hasVariable(?string $cacheKey): bool
    {
        return self::getInstance()->hasVariable($cacheKey);
    }

    /**
     * Get the value of a cached variable
     */
    public static function getVariable(string $cacheKey): string
    {
        return self::getInstance()->getVariable($cacheKey);
    }

    /**
     * Set the value of a cached variable
     */
    public static function setVariable(?string $cacheKey, string|object $value): void
    {
        self::getInstance()->setVariable($cacheKey, $value);
    }

    /**
     * Delete a cached variable
     */
    public static function delVariable(?string $cacheKey): void
    {
        self::getInstance()->delVariable($cacheKey);
    }
}
