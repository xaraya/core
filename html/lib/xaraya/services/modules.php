<?php

/**
 * Modules available via methods (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use Xaraya\Context\ContextInterface;
use Xaraya\Modules\ModuleInterface;
use xarClassMap;
use xarMod;
use xarModAlias;
use xarModVars;
use xarModItemVars;
use xarModUserVars;
use xarController;
use xarTpl;
use xarHooks;
use sys;
use Exception;
use FunctionNotFoundException;

sys::import('xaraya.services.servicetrait');

/**
 * For documentation purposes only - available via ModulesTrait
 */
interface ModulesInterface extends ServiceInterface
{
    public const SLICE = 'modules';

    public function getVar(string $varName): mixed;
    public function setVar(string $varName, mixed $value): bool;
    public function delVar(string $varName): bool;
    public function getVarID(string $varName): int;
    public function getUserVar(string $varName, ?int $userId = null): mixed;
    public function setUserVar(string $varName, mixed $value, ?int $userId = null): bool;
    public function delUserVar(string $varName, ?int $userId = null): bool;
    public function getItemVar(string $varName, mixed $itemid = null): mixed;
    public function setItemVar(string $varName, mixed $value, mixed $itemid = null): bool;
    public function delItemVar(string $varName, mixed $itemid = null): bool;
    public function disableOverview(): bool;
    /** @param array<string, mixed> $args */
    public function getURL(string $modType = 'user', string $funcName = 'main', array $args = [], ?string $modName = null): string;
    /** @param array<string, mixed> $tplData */
    public function template(string $funcName, array $tplData = [], ?string $templateName = null): string;
    /**
     * @param array<string, mixed> $tplData
     * @return array<string, mixed>
     */
    public function prepare(array $tplData = []): array;
    public function getName(?int $regID = null): string;
    public function getID(?string $modName = null): ?int;
    public function getRegID(?string $modName = null): int;
    public function getDisplayName(?string $modName = null): string;
    public function getDisplayDescription(?string $modName = null): string;
    /** @return array<string, mixed> */
    public function getFileInfo(?string $modName = null): array;
    /** @return array<string, mixed> */
    public function getBaseInfo(?string $modName = null): array;
    /** @return array<string, mixed> */
    public function getInfo(int $modRegId): array;
    public function setNoCache(bool $noCache): void;
    /** @return array<string, mixed> */
    public function getTables(?string $modName = null): array;
    public function isAvailable(?string $modName = null): bool;
    /** @param array<string, mixed> $args */
    public function apiFunc(?string $modName = null, ?string $modType = null, string $funcName = 'main', array $args = []): mixed;
    public function apiLoad(?string $modName = null, ?string $modType = null): mixed;
    /** @param array<string, mixed> $args */
    public function guiFunc(?string $modName = null, ?string $modType = null, string $funcName = 'main', array $args = []): mixed;
    public function load(?string $modName = null, ?string $modType = null): mixed;
    public function loadDbInfo(?string $modName = null, ?string $modDir = null): mixed;
    public function checkModuleFunction(string $tplmodule = 'dynamicdata', string $type = 'user', string $func = 'display', string $defaultmodule = 'dynamicdata'): string;
    public function getModule(?string $modName = null): ModuleInterface;
    public function getModuleClassMethod(?string $modName = null, ?string $modType = null, string $funcName = 'main', string $callType = 'api'): ?callable;
    /** @param array<string, mixed> $args */
    public function apiMethod(?string $modName = null, ?string $modType = null, string $funcName = 'main', array $args = []): mixed;
    /** @param array<string, mixed> $args */
    public function guiMethod(?string $modName = null, ?string $modType = null, string $funcName = 'main', array $args = []): mixed;
    public function resolveAlias(string $name): string;
    public function isHooked(string $hookModName, ?string $callerModName = null, ?int $callerItemType = null): bool;
    public function callHooks(string $scope, string $action, mixed $itemid, mixed $extraInfo = null, ?string $callerModName = null, ?int $callerItemType = null): mixed;
    /** @param array<string, mixed> $info */
    public function notifyHooks(string $event, array $info = []): mixed;
    public function setCurrentModName(string $modName): void;
}

/**
 * Modules available via methods
 */
trait ModulesTrait
{
    use ServiceTrait;

    /**
     * Get module variable for this module
     */
    public function getVar(string $varName): mixed
    {
        // use $this->mod($modName)->getVar(...) to use specific module
        $modName = $this->getModName();
        return xarModVars::get($modName, $varName);
    }

    /**
     * Set module variable for this module, or delete if value = null
     */
    public function setVar(string $varName, mixed $value): bool
    {
        $modName = $this->getModName();
        if (is_null($value)) {
            return xarModVars::delete($modName, $varName);
        }
        return xarModVars::set($modName, $varName, $value);
    }

    /**
     * Delete module variable for this module
     */
    public function delVar(string $varName): bool
    {
        $modName = $this->getModName();
        return xarModVars::delete($modName, $varName);
    }

    /**
     * Get module variable ID for this module
     */
    public function getVarID(string $varName): int
    {
        // use $this->mod($modName)->getVarID(...) to use specific module
        $modName = $this->getModName();
        return xarModVars::getID($modName, $varName);
    }

    public function getUserVar(string $varName, ?int $userId = null): mixed
    {
        // use $this->mod($modName)->getUserVar(...) to use specific module
        $modName = $this->getModName();
        return xarModUserVars::get($modName, $varName, $userId);
    }

    public function setUserVar(string $varName, mixed $value, ?int $userId = null): bool
    {
        $modName = $this->getModName();
        return xarModUserVars::set($modName, $varName, $value, $userId);
    }

    public function delUserVar(string $varName, ?int $userId = null): bool
    {
        $modName = $this->getModName();
        return xarModUserVars::delete($modName, $varName, $userId);
    }

    public function getItemVar(string $varName, mixed $itemid = null): mixed
    {
        // use $this->mod($modName)->getItemVar(...) to use specific module
        $modName = $this->getModName();
        return xarModItemVars::get($modName, $varName, $itemid);
    }

    public function setItemVar(string $varName, mixed $value, mixed $itemid = null): bool
    {
        $modName = $this->getModName();
        return xarModItemVars::set($modName, $varName, $value, $itemid);
    }

    public function delItemVar(string $varName, mixed $itemid = null): bool
    {
        $modName = $this->getModName();
        return xarModItemVars::delete($modName, $varName, $itemid);
    }

    /**
     * Utility method to check if module overview is disabled
     */
    public function disableOverview(): bool
    {
        return xarModVars::get('modules', 'disableoverview') ? true : false;
    }

    /**
     * Get url for this module type function
     * @param array<string, mixed> $args
     */
    public function getURL(string $modType = 'user', string $funcName = 'main', array $args = [], ?string $modName = null): string
    {
        $modName ??= $this->getModName();
        return xarController::URL($modName, $modType, $funcName, $args);
    }

    /**
     * Render output with module template
     * @uses xarTpl::module()
     * @param string $funcName
     * @param array<string, mixed> $tplData
     * @param ?string $templateName
     * @return string
     */
    public function template(string $funcName, array $tplData = [], ?string $templateName = null): string
    {
        // Add standard template variables (module, itemtype and context)
        $tplData = $this->prepare($tplData);

        // See if we have a special template to apply
        if (!isset($templateName) && isset($tplData['_bl_template'])) {
            $templateName = (string) $tplData['_bl_template'];
        }

        $modName = $this->getModName();
        // @todo Check if we're called from an api $modType and adapt to gui!?
        $modType = $this->getModType();
        if (str_ends_with($modType, 'api')) {
            $modType = substr($modType, 0, -3);
        }

        // Create the output.
        return xarTpl::module(
            $modName,
            $modType,
            $funcName,
            $tplData,
            $templateName
        );
    }

    /**
     * Add standard template variables (module, itemtype and context)
     * @param array<string, mixed> $tplData
     * @return array<string, mixed>
     */
    public function prepare(array $tplData = []): array
    {
        // Add standard template variables
        $tplData['module'] ??= $this->getModName();
        $tplData['itemtype'] ??= $this->getItemType();
        // Pass along the context for xarTpl::module() if needed
        $tplData['context'] ??= $this->getContext();
        return $tplData;
    }

    /**
     * Get module name for this module
     */
    public function getName(?int $regID = null): string
    {
        return xarMod::getName($regID);
    }

    /**
     * Get module system ID for this module (internal)
     */
    public function getID(?string $modName = null): ?int
    {
        $modName ??= $this->getModName();
        return xarMod::getID($modName);
    }

    /**
     * Get module registry ID for this module (fixed)
     */
    public function getRegID(?string $modName = null): int
    {
        $modName ??= $this->getModName();
        // avoid getting module id from xarMod::getRegID() here
        //return xarMod::getRegID($this->getModName());
        $fileInfo = $this->getFileInfo($modName);
        return (int) ($fileInfo['regid'] ?? 0);
    }

    /**
     * Get display name for this module
     */
    public function getDisplayName(?string $modName = null): string
    {
        $modName ??= $this->getModName();
        return xarMod::getDisplayName($modName);
    }

    /**
     * Get the displayable description for modName
     */
    public function getDisplayDescription(string $modName = null): string
    {
        $modName ??= $this->getModName();
        return xarMod::getDisplayDescription($modName);
    }

    /**
     * Get info from version.php
     * @return array<string, mixed>
     */
    public function getFileInfo(?string $modName = null): array
    {
        $modName ??= $this->getModName();
        return xarMod::getFileInfo($modName) ?? [];
    }

    /**
     * Get base information on module
     * @return array<string, mixed>
     */
    public function getBaseInfo(?string $modName = null): array
    {
        $modName ??= $this->getModName();
        return xarMod::getBaseInfo($modName) ?? [];
    }

    /**
     * Get information on module by registry ID (fixed)
     * @return array<string, mixed>
     */
    public function getInfo(int $modRegId): array
    {
        return xarMod::getInfo($modRegId);
    }

    /**
     * Set noCache
     */
    public function setNoCache(bool $noCache): void
    {
        xarMod::setNoCache($noCache);
    }

    /**
     * Get tables from tables.php
     * @todo pass along the DB prefix to $tablefunc
     * @return array<string, mixed>
     */
    public function getTables(?string $modName = null): array
    {
        $modName ??= $this->getModName();
        $result = xarClassMap::findTables($modName);
        if (!empty($result) && class_exists($result['classname'])) {
            $tablesCall = new $result['classname']();
            // @todo pass along the DB prefix to $tablesCall
            return $tablesCall();
        }

        // Load the database definition if required
        try {
            include_once sys::code() . 'modules/' . $modName . '/xartables.php';
        } catch (Exception $e) {
            return [];
        }
        $tablefunc = $modName . '_' . 'xartables';
        if (function_exists($tablefunc)) {
            // @todo pass along the DB prefix to $tablefunc
            return $tablefunc();
        }
        return [];
    }

    /**
     * Check if a module is available - @todo review for module classes
     */
    public function isAvailable(?string $modName = null): bool
    {
        $modName ??= $this->getModName();
        return xarMod::isAvailable($modName) ? true : false;
    }

    /**
     * Wrapper for xarMod::apiFunc() - only for migration
     * @param array<string, mixed> $args
     */
    public function apiFunc(?string $modName = null, ?string $modType = null, string $funcName = 'main', array $args = []): mixed
    {
        $modName ??= $this->getModName();
        $modType ??= $this->getModType();
        // @todo handle context for facades
        return xarMod::apiFunc($modName, $modType, $funcName, $args, $this->getContext());
    }

    /**
     * Wrapper for xarMod::apiLoad() - only for migration
     */
    public function apiLoad(?string $modName = null, ?string $modType = null): mixed
    {
        $modName ??= $this->getModName();
        $modType ??= $this->getModType();
        return xarMod::apiLoad($modName, $modType, xarMod::LOAD_ANYSTATE, $this->getContext());
    }

    /**
     * Wrapper for xarMod::guiFunc() - only for migration
     * @param array<string, mixed> $args
     */
    public function guiFunc(?string $modName = null, ?string $modType = null, string $funcName = 'main', array $args = []): mixed
    {
        $modName ??= $this->getModName();
        $modType ??= $this->getModType();
        // @todo handle context for facades
        return xarMod::guiFunc($modName, $modType, $funcName, $args, $this->getContext());
    }

    /**
     * Wrapper for xarMod::load() - only for migration
     */
    public function load(?string $modName = null, ?string $modType = null): mixed
    {
        $modName ??= $this->getModName();
        $modType ??= $this->getModType();
        return xarMod::load($modName, $modType, xarMod::LOAD_ONLYACTIVE, $this->getContext());
    }

    /**
     * Load DB tables for this module
     * @param ?string $modName
     * @param ?string $modDir
     * @return mixed
     */
    public function loadDbInfo(?string $modName = null, ?string $modDir = null): mixed
    {
        $modName ??= $this->getModName();
        // preset module dir == module name here
        $modDir ??= $modName;
        return xarMod::loadDbInfo($modName, $modDir);
    }

    /**
     * Check if a particular module function exists, or default back to 'dynamicdata'
     * @param string $tplmodule optional module where the templates reside
     * @param string $type link type (user, userapi, admin, adminapi, ...)
     * @param string $func link function (display, getitemtypes, ...)
     * @return string tplmodule or 'dynamicdata'
     */
    public function checkModuleFunction(string $tplmodule = 'dynamicdata', string $type = 'user', string $func = 'display', string $defaultmodule = 'dynamicdata'): string
    {
        return xarMod::checkModuleFunction($tplmodule, $type, $func, $defaultmodule);
    }

    /**
     * Get module class for this module (if there is one)
     * @param ?string $modName
     * @return ModuleInterface
     */
    public function getModule(?string $modName = null): ModuleInterface
    {
        $modName ??= $this->getModName();
        return xarMod::getModule($modName, $this->getContext());
    }

    /**
     * Check if a particular module class method exists, or return null
     * @param ?string $modName registered name of module -> used to define namespace
     * @param ?string $modType type of function to run (incl. funcType) -> will be mapped to class type
     * @param string $funcName specific function to run -> find corresponding method
     * @param string $callType is this called as an api function or not -> check against module class
     * @return callable|null
     */
    public function getModuleClassMethod(?string $modName = null, ?string $modType = null, string $funcName = 'main', string $callType = 'api'): ?callable
    {
        $modName ??= $this->getModName();
        $modType ??= $this->getModType();
        return xarMod::getModuleClassMethod($modName, $modType, $funcName, $callType, $this->getContext());
    }

    /**
     * Call module api method for this module (if there is one)
     * @param ?string $modName
     * @param ?string $modType
     * @param string $funcName
     * @param array<string, mixed> $args
     * @throws \FunctionNotFoundException
     * @return mixed
     */
    public function apiMethod(?string $modName = null, ?string $modType = null, string $funcName = 'main', array $args = []): mixed
    {
        $modName ??= $this->getModName();
        $modType ??= $this->getModType();
        if (!str_ends_with($modType, 'api') && !str_ends_with($modType, 'gui')) {
            $modType .= 'api';
        }
        $callable = xarMod::getModuleClassMethod($modName, $modType, $funcName, 'api', $this->getContext());
        if (empty($callable)) {
            throw new FunctionNotFoundException($funcName);
        }
        // this expects an instance in $callable[0]
        if (is_array($callable) && is_a($callable[0] ?? '', ContextInterface::class)) {
            $callable[0]->setContext($this->getContext());
        }
        return $callable($args);
    }

    /**
     * Call module gui method for this module (if there is one)
     * @param ?string $modName
     * @param ?string $modType
     * @param string $funcName
     * @param array<string, mixed> $args
     * @throws \FunctionNotFoundException
     * @return mixed
     */
    public function guiMethod(?string $modName = null, ?string $modType = null, string $funcName = 'main', array $args = []): mixed
    {
        $modName ??= $this->getModName();
        $modType ??= $this->getModType();
        // make sure configure() adds 'modtype' as well as 'modtypegui' to call types
        //if (!str_ends_with($modType, 'api') && !str_ends_with($modType, 'gui')) {
        //    $modType .= 'gui';
        //}
        $callable = xarMod::getModuleClassMethod($modName, $modType, $funcName, 'gui', $this->getContext());
        if (empty($callable)) {
            throw new FunctionNotFoundException($funcName);
        }
        // this expects an instance in $callable[0]
        if (is_array($callable) && is_a($callable[0] ?? '', ContextInterface::class)) {
            $callable[0]->setContext($this->getContext());
        }
        return $callable($args);
    }

    /**
     * Resolve module alias
     * @param string $name
     * @return string
     */
    public function resolveAlias(string $name): string
    {
        return xarModAlias::resolve($name);
    }

    /**
     * See if a hook module (observer) is attached (hooked) to specific module (subject) (+ itemtype)
     */
    public function isHooked(string $hookModName, ?string $callerModName = null, ?int $callerItemType = null): bool
    {
        $callerModName ??= $this->getModName();
        $callerItemType ??= $this->getItemType();
        return xarHooks::isAttached($hookModName, $callerModName, $callerItemType);
    }

    /**
     * Wrapper for xarModHooks::call() - only for migration
     * @see \xarModHooks::call()
     * @return mixed output from hooks, or null if there are no hooks
     */
    public function callHooks(string $scope, string $action, mixed $itemid, mixed $extraInfo = null, ?string $callerModName = null, ?int $callerItemType = null): mixed
    {
        $callerModName ??= $this->getModName();
        $callerItemType ??= $this->getItemType();
        //return xarModHooks::call($scope, $action, $itemid, $extraInfo, $this->getModName(), $this->getItemType(), $this->getContext());
        // scope and action are concatenated to form the name of the hook event
        $event = ucfirst($scope) . ucfirst($action);
        $extraInfo ??= [];
        $extraInfo['itemid'] ??= $itemid;
        $extraInfo['module'] ??= $callerModName;
        $extraInfo['itemtype'] ??= $callerItemType;
        // skip legacy format here - handled by HookSubject if needed
        return $this->notifyHooks($event, $extraInfo);
    }

    /**
     * Wrapper for xarHooks::notify() - only for migration
     * @see \xarHooks::notify()
     * @param array<string, mixed> $info
     * @return mixed output from hooks, or null if there are no hooks
     */
    public function notifyHooks(string $event, array $info = []): mixed
    {
        $info['itemid'] ??= null;
        $info['module'] ??= $this->getModName();
        $info['itemtype'] ??= $this->getItemType();
        return xarHooks::notify($event, $info, $this->getContext());
    }
}

/**
 * Access xarMod*::* Modules methods (getVar, setVar, ...)
 *
 * Available methods:
 * - getVar()
 * - setVar()
 * - delVar()
 * - getVarID()
 * - getUserVar()
 * - setUserVar()
 * - delUserVar()
 * - getItemVar()
 * - setItemVar()
 * - delItemVar()
 * - disableOverview()
 * - getURL() for current module - or use ctl()->getModuleURL() in general with modName
 * - template() for current module type - or use tpl()->module() in general with modName modType
 * - prepare() for current module itemtype
 * - getName()
 * - getID()
 * - getRegID()
 * - getFileInfo()
 * - getInfo()
 * - getTables()
 * - isAvailable()
 * - loadDbInfo()
 * - getModule() - for modules using module classes
 * - apiMethod()
 * - guiMethod()
 * - resolveAlias()
 * - isHooked() for current module itemtype if not specified
 * - callHooks() for current module itemtype if not specified
 * - notifyHooks() for current module itemtype if not specified
 * - ...
 *
 * Required methods in parent:
 * - getModName()
 * - getItemType() for mod()->prepare(), mod()->isHooked() and mod()->callHooks()
 * - getModType() for mod()->template()
 *
 */
class ModulesService implements ModulesInterface
{
    use ModulesTrait;

    protected ?string $currentModName = null;

    /**
     * Get name of the module from parent
     */
    public function getModName(): string
    {
        return $this->currentModName ?? $this->getParent()->getModName();
    }

    /**
     * Get item type from parent
     */
    public function getItemType(): int
    {
        return $this->getParent()->getItemType();
    }

    /**
     * Get module type (user, admin, ...) from parent
     */
    public function getModType(): string
    {
        return $this->getParent()->getModType();
    }

    /**
     * Override parent modName when called as $this->mod($modName)->...
     * @param string $modName
     * @return void
     */
    public function setCurrentModName(string $modName): void
    {
        $this->currentModName = $modName;
    }

    public function __clone()
    {
        $this->currentModName = null;
    }
}
