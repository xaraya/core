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
use xarMod;
use sys;

sys::import('xaraya.services.modules');
sys::import('xaraya.services.servicefactory');

/**
 * Make Modules Service available via facade - xarMod3:: static methods
 * similar to traditional xarMod::* method calls - modName is mandatory here
 * @deprecated 2.8.2 use xar::mod()->* instead
 */
class xarMod3
{
    public const STATE_ACTIVE = xarMod::STATE_ACTIVE;

    /** @var ?ModulesInterface */
    protected static $xarMod = null;         // Access modules service with instance methods

    public static function getInstance(): ModulesInterface
    {
        self::$xarMod ??= ServiceFactory::getModulesService(__METHOD__);
        return self::$xarMod;
    }

    /**
     * Get module variable for this module - modName is mandatory here
     */
    public static function getVar(string $varName, string $modName): mixed
    {
        return self::getInstance()->getVar($varName, $modName);
    }

    /**
     * Get url for this module type function - modName is mandatory here
     * @param array<string, mixed> $args
     */
    public static function getURL(string $modType = 'user', string $funcName = 'main', array $args = [], string $modName = 'base'): string
    {
        return self::getInstance()->getURL($modType, $funcName, $args, $modName);
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
    public static function getID(string $modName): ?int
    {
        return self::getInstance()->getID($modName);
    }

    /**
     * Get module registry ID for this module - modName is mandatory here
     */
    public static function getRegID(string $modName): int
    {
        return self::getInstance()->getRegID($modName);
    }

    /**
     * Get display name for this module - modName is mandatory here
     */
    public static function getDisplayName(string $modName): string
    {
        return self::getInstance()->getDisplayName($modName);
    }

    /**
     * Get info from version.php - modName is mandatory here
     * @return array<string, mixed>
     */
    public static function getFileInfo(string $modName): array
    {
        return self::getInstance()->getFileInfo($modName);
    }

    /**
     * Get base information on module - modName is mandatory here
     * @return array<string, mixed>
     */
    public static function getBaseInfo(string $modName): array
    {
        return self::getInstance()->getBaseInfo($modName);
    }

    /**
     * Get information on module by registry ID (fixed)
     * @return array<string, mixed>
     */
    public static function getInfo(int $modRegId): array
    {
        return self::getInstance()->getInfo($modRegId);
    }

    /**
     * Get tables from tables.php - modName is mandatory here
     * @return array<string, mixed>
     */
    public static function getTables(string $modName): array
    {
        return self::getInstance()->getTables($modName);
    }

    /**
     * Check if a module is available - modName is mandatory here - @todo review for module classes
     * @param string $modName
     * @return bool
     */
    public static function isAvailable(string $modName): bool
    {
        return self::getInstance()->isAvailable($modName);
    }

    /**
     * Wrapper for xarMod::apiFunc() - only for migration
     * @param array<string, mixed> $args
     */
    public static function apiFunc(string $modName, string $modType, string $funcName = 'main', array $args = [], mixed $context = null): mixed
    {
        // @todo handle context
        if (!empty($context)) {
            self::getInstance()->setContext($context);
        }
        return self::getInstance()->apiFunc($modName, $modType, $funcName, $args);
    }

    /**
     * Wrapper for xarMod::apiLoad() - only for migration
     */
    public static function apiLoad(string $modName, string $modType = 'user'): mixed
    {
        return self::getInstance()->apiLoad($modName, $modType);
    }

    /**
     * Wrapper for xarMod::guiFunc() - only for migration
     * @param array<string, mixed> $args
     */
    public static function guiFunc(string $modName, string $modType, string $funcName = 'main', array $args = [], mixed $context = null): mixed
    {
        // @todo handle context
        if (!empty($context)) {
            self::getInstance()->setContext($context);
        }
        return self::getInstance()->guiFunc($modName, $modType, $funcName, $args);
    }

    /**
     * Wrapper for xarMod::load() - only for migration
     */
    public static function load(string $modName, string $modType = 'user'): mixed
    {
        return self::getInstance()->load($modName, $modType);
    }

    /**
     * Load DB tables for this module - modName is mandatory here
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
    public static function getModule(string $modName, mixed $context = null): ModuleInterface
    {
        if (!empty($context)) {
            self::getInstance()->setContext($context);
        }
        return self::getInstance()->getModule($modName);
    }

    /**
     * Check if a particular module class method exists, or return null
     * @param string $modName registered name of module -> used to define namespace
     * @param string $modType type of function to run (incl. funcType) -> will be mapped to class type
     * @param string $funcName specific function to run -> find corresponding method
     * @param string $callType is this called as an api function or not -> check against module class
     * @return callable|null
     */
    public static function getModuleClassMethod(string $modName, string $modType, string $funcName = 'main', string $callType = 'api', mixed $context = null): ?callable
    {
        if (!empty($context)) {
            self::getInstance()->setContext($context);
        }
        return self::getInstance()->getModuleClassMethod($modName, $modType, $funcName, $callType);
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

    /**
     * Resolve module alias
     * @param string $name
     * @return string
     */
    public static function resolveAlias(string $name): string
    {
        return self::getInstance()->resolveAlias($name);
    }

    /**
     * See if a hook module (observer) is attached (hooked) to specific module (subject) (+ itemtype)
     */
    public static function isHooked(string $hookModName, string $callerModName, ?int $callerItemType = null): bool
    {
        return self::getInstance()->isHooked($hookModName, $callerModName, $callerItemType);
    }
}
