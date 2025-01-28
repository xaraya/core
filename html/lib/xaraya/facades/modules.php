<?php

/**
 * Make Modules Service available via facade (WIP)
 *
 * Classes that don't use ServicesInterface like xarMod(), xarUser() etc.
 * can more easily replace (most common) static xarMod::* method calls if
 * they use \Xaraya\Facades\xarMod3; instead
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

use Xaraya\Services\ModulesInterface;
use Xaraya\Services\ServiceFactory;
use Xaraya\Modules\ModuleInterface;
use sys;

sys::import('xaraya.services.modules');
sys::import('xaraya.services.servicefactory');

/**
 * Make Modules Service available via facade - xarMod3:: static methods
 * similar to traditional xarMod::* method calls
 */
class xarMod3
{
    /** @var ?ModulesInterface */
    protected static $xarMod = null;         // Access modules service with instance methods

    public static function getInstance(): ModulesInterface
    {
        self::$xarMod ??= ServiceFactory::getModulesService(__METHOD__);
        return self::$xarMod;
    }

    /**
     * Get module name for this module
     */
    public static function getName(?int $regID = null): string
    {
        return self::getInstance()->getName($regID);
    }

    /**
     * Get module system ID for this module
     */
    public static function getID(string $modName): int|null
    {
        return self::getInstance()->getID($modName);
    }

    /**
     * Get module registry ID for this module
     */
    public static function getRegID(string $modName): int
    {
        return self::getInstance()->getRegID($modName);
    }

    /**
     * Get info from xarversion.php
     * @return array<string, mixed>
     */
    public static function getInfo(string $modName): array
    {
        return self::getInstance()->getInfo($modName);
    }

    /**
     * Get tables from xartables.php
     * @return array<string, mixed>
     */
    public static function getTables(string $modName): array
    {
        return self::getInstance()->getTables($modName);
    }

    /**
     * Check if a module is available - @todo review for module classes
     * @param string $modName
     * @return bool
     */
    public static function isAvailable(string $modName): bool
    {
        return self::getInstance()->isAvailable($modName);
    }

    /**
     * Wrapper for xarMod::apiFunc() - only for migration
     * @param string $modName
     * @param string $modType
     * @param string $funcName
     * @param array<string, mixed> $args
     * @return mixed
     */
    public static function apiFunc($modName, $modType, $funcName = 'main', $args = [])
    {
        // @todo handle context
        return self::getInstance()->apiFunc($modName, $modType, $funcName, $args);
    }

    /**
     * Wrapper for xarMod::apiLoad() - only for migration
     * @param string $modName
     * @param string $modType
     * @return mixed
     */
    public static function apiLoad($modName, $modType)
    {
        return self::getInstance()->apiLoad($modName, $modType);
    }

    /**
     * Wrapper for xarMod::guiFunc() - only for migration
     * @param string $modName
     * @param string $modType
     * @param string $funcName
     * @param array<string, mixed> $args
     * @return mixed
     */
    public static function guiFunc($modName, $modType, $funcName = 'main', $args = [])
    {
        // @todo handle context
        return self::getInstance()->guiFunc($modName, $modType, $funcName, $args);
    }

    /**
     * Wrapper for xarMod::load() - only for migration
     * @param string $modName
     * @param string $modType
     * @return mixed
     */
    public static function load($modName, $modType)
    {
        return self::getInstance()->load($modName, $modType);
    }

    /**
     * Load DB tables for this module
     * @param string $modName
     * @param ?string $modDir
     * @return mixed
     */
    public static function loadDbInfo(string $modName, ?string $modDir = null): mixed
    {
        return self::getInstance()->loadDbInfo($modName, $modDir);
    }

    /**
     * Get module class for this module (if there is one)
     * @param string $modName
     * @return ModuleInterface
     */
    public static function getModule(string $modName): ModuleInterface
    {
        return self::getInstance()->getModule($modName);
    }

    /**
     * Call module api method for this module (if there is one)
     * @param string $modName
     * @param string $modType
     * @param string $funcName
     * @param array<string, mixed> $args
     * @throws \FunctionNotFoundException
     * @return mixed
     */
    public static function apiMethod(string $modName, string $modType, string $funcName = 'main', array $args = []): mixed
    {
        // @todo handle context
        return self::getInstance()->apiMethod($modName, $modType, $funcName, $args);
    }

    /**
     * Call module gui method for this module (if there is one)
     * @param string $modName
     * @param string $modType
     * @param string $funcName
     * @param array<string, mixed> $args
     * @throws \FunctionNotFoundException
     * @return mixed
     */
    public static function guiMethod(string $modName, string $modType, string $funcName = 'main', array $args = []): mixed
    {
        // @todo handle context
        return self::getInstance()->guiMethod($modName, $modType, $funcName, $args);
    }
}
