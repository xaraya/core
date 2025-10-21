<?php

/**
 * Make Variables Service available via facade (WIP)
 *
 * Classes that don't use ServicesInterface like xarMod(), xarUser() etc.
 * can more easily replace (most common) static xarVar::* method calls if
 * they use \Xaraya\Facades\xarVar3; instead
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

use Xaraya\Services\VariablesInterface;
use Xaraya\Services\ServiceFactory;
use sys;

sys::import('xaraya.services.variables');
sys::import('xaraya.services.servicefactory');

/**
 * Make Variables Service available via facade - xarVar3:: static methods
 * similar to traditional xarVar::* method calls
 * @deprecated 2.8.2 use xar::var()->* instead
 */
class xarVar3
{
    /** @var ?VariablesInterface */
    protected static $xarVar = null;         // Access variables service with instance methods

    public static function getInstance(): VariablesInterface
    {
        self::$xarVar ??= ServiceFactory::getVariablesService(__METHOD__);
        return self::$xarVar;
    }

    /**
     * Prepare text for operating system path, and convert all special characters
     *
     * @param string ...$args
     * @return mixed
     */
    public static function prepPath(...$args)
    {
        return self::getInstance()->prepPath(...$args);
    }

    public static function isCached(string $scope, string $name): bool
    {
        return self::getInstance()->isCached($scope, $name);
    }

    public static function getCached(string $scope, string $name): mixed
    {
        return self::getInstance()->getCached($scope, $name);
    }

    public static function setCached(string $scope, string $name, mixed $value): void
    {
        self::getInstance()->setCached($scope, $name, $value);
    }

    public static function delCached(string $scope, string $name): void
    {
        self::getInstance()->delCached($scope, $name);
    }

    public static function hasPreload(string $scope, ?string $name = null): bool
    {
        return self::getInstance()->hasPreload($scope, $name);
    }

    public static function loadCached(string $scope, ?string $name = null): bool
    {
        return self::getInstance()->loadCached($scope, $name);
    }

    public static function saveCached(string $scope, ?string $name = null, ?string $source = null): bool
    {
        return self::getInstance()->saveCached($scope, $name, $source);
    }
}
