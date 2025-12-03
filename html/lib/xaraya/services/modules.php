<?php

/**
 * Modules available via methods (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use Xaraya\Modules\ModuleInterface;
use Xaraya\Modules\ModuleClassInterface;
use Xaraya\Modules\UserApiInterface;
use Xaraya\Modules\UserGuiInterface;
use ixarMod;
use xarRoles;
use xarSecurity;
use BadParameterException;
use FunctionNotFoundException;

/**
 * For documentation purposes only - available via ModulesTrait
 */
interface ModulesInterface extends ServiceInterface
{
    public const SLICE = 'modules';

    public function getVar(string $varName, mixed $default = null): mixed;
    public function setVar(string $varName, mixed $value): bool;
    public function delVar(string $varName): bool;
    public function flushVars(): bool;
    public function cacheVars(?string $source = null): void;
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
    public function parseFileInfo($version, $name = ''): array;
    /** @return array<string, mixed> */
    public function getBaseInfo(?string $modName = null): array;
    /** @return array<string, mixed> */
    public function getInfo(int $modRegId): array;
    public function getNoCache(): bool;
    public function setNoCache(bool $noCache): void;
    /** @return array<string, mixed> */
    public function getTables(?string $modName = null): array;
    public function isAvailable(?string $modName = null): bool;
    /** @param array<string, mixed> $args */
    public function apiFunc(?string $modName = null, ?string $modType = null, string $funcName = 'main', array $args = []): mixed;
    public function apiLoad(?string $modName = null, ?string $modType = null, int $flags = ixarMod::LOAD_ANYSTATE): mixed;
    /** @param array<string, mixed> $args */
    public function guiFunc(?string $modName = null, ?string $modType = null, string $funcName = 'main', array $args = []): mixed;
    public function load(?string $modName = null, ?string $modType = null, int $flags = ixarMod::LOAD_ONLYACTIVE): mixed;
    public function loadDbInfo(?string $modName = null, ?string $modDir = null): mixed;
    public function userapi(?string $modName = null): ?UserApiInterface;
    public function usergui(?string $modName = null): ?UserGuiInterface;
    public function checkModuleFunction(string $tplmodule = 'dynamicdata', string $type = 'userapi', string $func = 'getitemtypes', string $defaultmodule = 'dynamicdata'): string;
    public function getModule(?string $modName = null): ModuleInterface;
    public function getModuleClass(?string $modName = null, ?string $modType = null): ?ModuleClassInterface;
    public function getModuleClassMethod(?string $modName = null, ?string $modType = null, string $funcName = 'main', string $callType = 'api'): ?callable;
    /** @param array<string, mixed> $args */
    public function apiMethod(?string $modName = null, ?string $modType = null, string $funcName = 'main', array $args = []): mixed;
    /** @param array<string, mixed> $args */
    public function guiMethod(?string $modName = null, ?string $modType = null, string $funcName = 'main', array $args = []): mixed;
    public function resolveAlias(string $name): string;
    public function defineAlias(string $alias, string $modName): mixed;
    public function removeAlias(string $alias, string $modName): mixed;
    public function isHooked(string $hookModName, ?string $callerModName = null, ?int $callerItemType = null): bool;
    public function callHooks(string $scope, string $action, mixed $itemid, mixed $extraInfo = null, ?string $callerModName = null, ?int $callerItemType = null): mixed;
    /** @param array<string, mixed> $info */
    public function notifyHooks(string $event, array $info = []): mixed;
    public function checkAccess(?string $modName = null, string $action = '', ?int $roleid = null): bool;
    public function setCurrentModName(string $modName): void;
}

/**
 * Modules available via methods
 */
trait ModulesTrait
{
    use ServiceTrait;

    public $genShortUrls = false;
    public $genXmlUrls   = true;
    protected bool $initialized = false;
    private ?Modules\VarsHelper $varsHelper = null;
    private ?Modules\UserVarsHelper $userVarsHelper = null;
    private ?Modules\ItemVarsHelper $itemVarsHelper = null;
    private ?Modules\InfoHelper $infoHelper = null;
    private ?Modules\ExecHelper $execHelper = null;
    private ?Modules\HooksHelper $hooksHelper = null;
    private ?Modules\AliasHelper $aliasHelper = null;

    private function getVarsHelper(): Modules\VarsHelper
    {
        $this->varsHelper ??= $this->getParent()->service('modules.vars');
        return $this->varsHelper;
    }

    private function getUserVarsHelper(): Modules\UserVarsHelper
    {
        $this->userVarsHelper ??= $this->getParent()->service('modules.user');
        return $this->userVarsHelper;
    }

    private function getItemVarsHelper(): Modules\ItemVarsHelper
    {
        $this->itemVarsHelper ??= $this->getParent()->service('modules.item');
        return $this->itemVarsHelper;
    }

    public function getInfoHelper(): Modules\InfoHelper
    {
        $this->infoHelper ??= $this->getParent()->service('modules.info');
        return $this->infoHelper;
    }

    public function getExecHelper(): Modules\ExecHelper
    {
        $this->execHelper ??= $this->getParent()->service('modules.exec');
        return $this->execHelper;
    }

    private function getHooksHelper(): Modules\HooksHelper
    {
        $this->hooksHelper ??= $this->getParent()->service('modules.hooks');
        return $this->hooksHelper;
    }

    private function getAliasHelper(): Modules\AliasHelper
    {
        $this->aliasHelper ??= $this->getParent()->service('modules.alias');
        return $this->aliasHelper;
    }

    private function resetHelpers(): void
    {
        $this->varsHelper = null;
        $this->userVarsHelper = null;
        $this->itemVarsHelper = null;
        $this->infoHelper = null;
        $this->execHelper = null;
        $this->hooksHelper = null;
        $this->aliasHelper = null;
    }

    /**
     * Initialize service class
     * @param array<string, mixed> $config
     */
    public function init(array $config = []): bool
    {
        if (empty($config)) {
            if ($this->initialized) {
                return true;
            }
            $config = $this->getConfig();
        }
        $this->genShortUrls = $config['enableShortURLsSupport'];
        $this->genXmlUrls   = $config['generateXMLURLs'];

        $xar = $this->getParent();
        // Modules Support Tables
        $prefix = $xar->db()->getPrefix();

        // How we want it
        $tables['modules']         = $prefix . '_modules';
        $tables['module_vars']     = $prefix . '_module_vars';
        $tables['module_itemvars'] = $prefix . '_module_itemvars';
        $tables['hooks']           = $prefix . '_hooks';
        $tables['themes']          = $prefix . '_themes';

        $xar->db()->importTables($tables);
        $this->initialized = true;
        return true;
    }

    /**
     * Get configuration
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        $xar = $this->getParent();
        $systemArgs = [
            'enableShortURLsSupport' => $xar->config()->getVar('Site.Core.EnableShortURLsSupport'),
            'generateXMLURLs'        => true,
        ];
        return $systemArgs;
    }

    public function isLoaded(): bool
    {
        return $this->initialized;
    }

    /**
     * Get module variable for this module
     */
    public function getVar(string $varName, mixed $default = null): mixed
    {
        $modName = $this->getModName();
        return $this->getVarsHelper()->get($modName, $varName) ?? $default;
    }

    /**
     * Set module variable for this module, or delete if value = null
     */
    public function setVar(string $varName, mixed $value): bool
    {
        $modName = $this->getModName();
        return $this->getVarsHelper()->set($modName, $varName, $value);
    }

    /**
     * Delete module variable for this module
     */
    public function delVar(string $varName): bool
    {
        $modName = $this->getModName();
        return $this->getVarsHelper()->delete($modName, $varName);
    }

    /**
     * Delete all module variables for this module
     */
    public function flushVars(): bool
    {
        $modName = $this->getModName();
        return $this->getVarsHelper()->flush($modName);
    }

    /**
     * Cache all module variables for this module (if CoreCache.Preload is enabled for it)
     */
    public function cacheVars(?string $source = null): void
    {
        $modName = $this->getModName();
        $this->getVarsHelper()->cache($modName, $source);
    }

    /**
     * Get module variable ID for this module
     */
    public function getVarID(string $varName): int
    {
        $modName = $this->getModName();
        return $this->getVarsHelper()->getID($modName, $varName);
    }

    public function getUserVar(string $varName, ?int $userId = null): mixed
    {
        $modName = $this->getModName();
        return $this->getUserVarsHelper()->get($modName, $varName, $userId);
    }

    public function setUserVar(string $varName, mixed $value, ?int $userId = null): bool
    {
        $modName = $this->getModName();
        return $this->getUserVarsHelper()->set($modName, $varName, $value, $userId);
    }

    public function delUserVar(string $varName, ?int $userId = null): bool
    {
        $modName = $this->getModName();
        return $this->getUserVarsHelper()->delete($modName, $varName, $userId);
    }

    public function getItemVar(string $varName, mixed $itemid = null): mixed
    {
        $modName = $this->getModName();
        return $this->getItemVarsHelper()->get($modName, $varName, $itemid);
    }

    public function setItemVar(string $varName, mixed $value, mixed $itemid = null): bool
    {
        $modName = $this->getModName();
        return $this->getItemVarsHelper()->set($modName, $varName, $value, $itemid);
    }

    public function delItemVar(string $varName, mixed $itemid = null): bool
    {
        $modName = $this->getModName();
        return $this->getItemVarsHelper()->delete($modName, $varName, $itemid);
    }

    /**
     * Utility method to check if module overview is disabled
     */
    public function disableOverview(): bool
    {
        return $this->getVarsHelper()->disableOverview();
    }

    /**
     * Get url for this module type function
     * @param array<string, mixed> $args
     */
    public function getURL(string $modType = 'user', string $funcName = 'main', array $args = [], ?string $modName = null): string
    {
        $modName ??= $this->getModName();
        $xar = $this->getParent();
        return $xar->ctl()->getModuleURL($modName, $modType, $funcName, $args);
    }

    /**
     * Render output with module template
     * @uses xar::tpl()->module()
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

        /** @var ServicesInterface $xar */
        $xar = $this->getParent();

        // Create the output.
        return $xar->tpl()->module(
            $modName,
            $modType,
            $funcName,
            $tplData,
            $templateName,
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
        // Pass along the context for xar::tpl()->module() if needed
        $tplData['context'] ??= $this->getContext();
        return $tplData;
    }

    /**
     * Get module name for this module
     * @todo align with xar::mod($modName)->getName() - move back to ModuleService?
     */
    public function getName(?int $regID = null): string
    {
        return $this->getInfoHelper()->getName($regID);
    }

    /**
     * Get module system ID for this module (internal)
     */
    public function getID(?string $modName = null): ?int
    {
        $modName ??= $this->getModName();
        return $this->getInfoHelper()->getID($modName);
    }

    /**
     * Get module registry ID for this module (fixed)
     */
    public function getRegID(?string $modName = null): int
    {
        $modName ??= $this->getModName();
        return $this->getInfoHelper()->getRegID($modName);
    }

    /**
     * Get display name for this module
     */
    public function getDisplayName(?string $modName = null): string
    {
        $modName ??= $this->getModName();
        return $this->getInfoHelper()->getDisplayName($modName);
    }

    /**
     * Get the displayable description for modName
     */
    public function getDisplayDescription(?string $modName = null): string
    {
        $modName ??= $this->getModName();
        return $this->getInfoHelper()->getDisplayDescription($modName);
    }

    /**
     * Get info from version.php
     * @return array<string, mixed>
     */
    public function getFileInfo(?string $modName = null): array
    {
        $modName ??= $this->getModName();
        return $this->getInfoHelper()->getFileInfo($modName);
    }

    /**
     * @return array<string, mixed>
     */
    public function parseFileInfo($version, $name = ''): array
    {
        return $this->getInfoHelper()->parseFileInfo($version, $name);
    }

    /**
     * Get base information on module
     * @return array<string, mixed>
     */
    public function getBaseInfo(?string $modName = null): array
    {
        $modName ??= $this->getModName();
        return $this->getInfoHelper()->getBaseInfo($modName);
    }

    /**
     * Get information on module by registry ID (fixed)
     * @return array<string, mixed>
     */
    public function getInfo(int $modRegId): array
    {
        return $this->getInfoHelper()->getInfo($modRegId);
    }

    /**
     * Get noCache
     */
    public function getNoCache(): bool
    {
        return $this->getInfoHelper()->noCacheMod;
    }

    /**
     * Set noCache
     */
    public function setNoCache(bool $noCache): void
    {
        $this->getInfoHelper()->noCacheMod = (bool) $noCache;
    }

    /**
     * Get tables from tables.php
     * @todo pass along the DB prefix to $tablefunc
     * @return array<string, mixed>
     */
    public function getTables(?string $modName = null): array
    {
        $modName ??= $this->getModName();
        return $this->getInfoHelper()->getTables($modName);
    }

    /**
     * Check if a module is available - @todo review for module classes
     */
    public function isAvailable(?string $modName = null): bool
    {
        $modName ??= $this->getModName();
        return $this->getInfoHelper()->isAvailable($modName);
    }

    /**
     * @param array<string, mixed> $args
     */
    public function apiFunc(?string $modName = null, ?string $modType = null, string $funcName = 'main', array $args = []): mixed
    {
        $modName ??= $this->getModName();
        $modType ??= $this->getModType();
        return $this->getExecHelper()->apiFunc($modName, $modType, $funcName, $args);
    }

    public function apiLoad(?string $modName = null, ?string $modType = null, int $flags = ixarMod::LOAD_ANYSTATE): mixed
    {
        $modName ??= $this->getModName();
        $modType ??= $this->getModType();
        return $this->getExecHelper()->apiLoad($modName, $modType, $flags);
    }

    /**
     * @param array<string, mixed> $args
     */
    public function guiFunc(?string $modName = null, ?string $modType = null, string $funcName = 'main', array $args = []): mixed
    {
        $modName ??= $this->getModName();
        $modType ??= $this->getModType();
        return $this->getExecHelper()->guiFunc($modName, $modType, $funcName, $args);
    }

    public function load(?string $modName = null, ?string $modType = null, int $flags = ixarMod::LOAD_ONLYACTIVE): mixed
    {
        $modName ??= $this->getModName();
        $modType ??= $this->getModType();
        return $this->getExecHelper()->load($modName, $modType, $flags);
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
        return $this->getInfoHelper()->loadDbInfo($modName, $modDir);
    }

    public function userapi(?string $modName = null): ?UserApiInterface
    {
        $modName ??= $this->getModName();
        return $this->getModule($modName)->userapi();
    }

    public function usergui(?string $modName = null): ?UserGuiInterface
    {
        $modName ??= $this->getModName();
        return $this->getModule($modName)->usergui();
    }

    /**
     * Check if a particular module function exists, or default back to 'dynamicdata'
     * @param string $tplmodule optional module where the templates reside
     * @param string $type link type (user, userapi, admin, adminapi, ...)
     * @param string $func link function (display, getitemtypes, ...)
     * @return string tplmodule or 'dynamicdata'
     * @see \Xaraya\Bridge\RestAPI\RestAPIBuilder::find_default_api_functions()
     * @see \xarDDObject::getModuleURL()
     */
    public function checkModuleFunction(string $tplmodule = 'dynamicdata', string $type = 'userapi', string $func = 'getitemtypes', string $defaultmodule = 'dynamicdata'): string
    {
        return $this->getExecHelper()->checkModuleFunction($tplmodule, $type, $func, $defaultmodule);
    }

    /**
     * Get module class for this module (if there is one)
     * @param ?string $modName
     * @return ModuleInterface
     */
    public function getModule(?string $modName = null): ModuleInterface
    {
        $modName ??= $this->getModName();
        return $this->getExecHelper()->getModule($modName);
    }

    /**
     * Get module class component for this module (if there is one)
     * @param ?string $modName
     * @param ?string $modType (incl. funcType) -> will be mapped to class type
     * @return ModuleClassInterface|null
     */
    public function getModuleClass(?string $modName = null, ?string $modType = null): ?ModuleClassInterface
    {
        $modName ??= $this->getModName();
        $modType ??= $this->getModType();
        return $this->getExecHelper()->getModuleClass($modName, $modType);
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
        return $this->getExecHelper()->getModuleClassMethod($modName, $modType, $funcName, $callType);
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
        // @todo make sure apiMethod() calls always use full $modType . $funcType
        if (!str_ends_with($modType, 'api') && !str_ends_with($modType, 'gui')) {
            $modType .= 'api';
        }
        return $this->getExecHelper()->apiMethod($modName, $modType, $funcName, $args);
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
        return $this->getExecHelper()->guiMethod($modName, $modType, $funcName, $args);
    }

    /**
     * Resolve module alias
     * @param string $name
     * @return string
     */
    public function resolveAlias(string $name): string
    {
        // @todo move back to ModulesService for direct method calls
        return $this->getAliasHelper()->resolve($name);
    }

    public function defineAlias(string $alias, string $modName): mixed
    {
        return $this->getAliasHelper()->define($alias, $modName);
    }

    /**
     * @deprecated 2.9.0 use xar::mod()->defineAlias() instead
     */
    public function setAlias(string $alias, string $modName): mixed
    {
        return $this->defineAlias($alias, $modName);
    }

    public function removeAlias(string $alias, string $modName): mixed
    {
        return $this->getAliasHelper()->remove($alias, $modName);
    }

    /**
     * See if a hook module (observer) is attached (hooked) to specific module (subject) (+ itemtype)
     */
    public function isHooked(string $hookModName, ?string $callerModName = null, ?int $callerItemType = null): bool
    {
        $callerModName ??= $this->getModName();
        $callerItemType ??= $this->getItemType();
        return $this->getHooksHelper()->isHooked($hookModName, $callerModName, $callerItemType);
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
        //return $this->getHooksHelper()->callHooks($scope, $action, $itemid, $extraInfo, $this->getModName(), $this->getItemType(), $this->getContext());
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
        return $this->getHooksHelper()->notifyHooks($event, $info, $this->getContext());
    }

    public function checkAccess(?string $modName = null, string $action = '', ?int $roleid = null): bool
    {
        $modName ??= $this->getModName();
        // TODO: get module variable with access config: groups, masks, levels or whatever

        // TODO: check for access e.g. by group

        // Fall back on mask-less security check with access levels corresponding to action

        // default actions supported on modules
        switch ($action) {
            case 'admin':
                $seclevel = xarSecurity::ACCESS_ADMIN;
                break;

                // CHECKME: any others we really use on module level (instead of object/item/block/... level) ?

            case 'view':
                $seclevel = xarSecurity::ACCESS_OVERVIEW;
                break;

            default:
                throw new BadParameterException('action', "Supported actions on module level are 'view' and 'admin'");
        }

        if (!empty($roleid)) {
            $role = xarRoles::get($roleid);
            $rolename = $role->getName();
            return xarSecurity::check('', 0, 'All', 'All', $modName, $rolename, 0, $seclevel);
        } else {
            return xarSecurity::check('', 0, 'All', 'All', $modName, '', 0, $seclevel);
        }
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

    /**
     * Create a specialized version of this service for a specific module name.
     * @param mixed ...$args
     * @return ServiceInterface
     */
    public function specialize(...$args): ServiceInterface
    {
        $clone = clone $this;
        if (isset($args[0])) {
            $clone->setCurrentModName($args[0]);
        }
        return $clone;
    }

    // @todo remove this when all specialize() methods are implemented
    public function __clone()
    {
        $this->currentModName = null;
    }

    public function __serialize()
    {
        $data = xar::getPublicProperties($this);
        // add any protected/private properties that are relevent here
        $data['initialized'] = $this->initialized;
        $data['currentModName'] = $this->currentModName ?? null;
        // reset private helpers for comparison - see SerializeServicesTest::testModulesService()
        $this->resetHelpers();
        return $data;
    }
}
