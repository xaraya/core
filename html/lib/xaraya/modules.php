<?php

/**
 * Module handling subsystem
 *
 * Eventually we want this to split up in multiple files, for reference:
 * current classes in here (disregarding exceptions):
 *      xarModVars
 *      xarModUserVars
 *      xarMod
 *
 * @package core\modules
 * @category Xaraya Web Applications Framework
 * @version 2.8.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Jim McDonald
 * @author Marco Canini <marco@xaraya.com>
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @todo the double headed theme/module stuff needs to go, a theme is not a module
 */

sys::import("xaraya.context.contexttrait");
sys::import("xaraya.context.context");
sys::import('xaraya.services.xar');
use Xaraya\Context\ContextInterface;
use Xaraya\Context\Context;
use Xaraya\Services\ModulesService;
use Xaraya\Services\Modules\AliasHelper;
use Xaraya\Services\xar;

/**
 * Exception raised by the modules subsystem
**/
class ModuleBaseInfoNotFoundException extends NotFoundExceptions
{
    protected $message = 'The base info for module "#(1)" could not be found';
}

/**
 * Exception raised by the modules subsystem
**/
class ModuleNotFoundException extends NotFoundExceptions
{
    protected $message = 'A module is missing, the module name could not be determined in the current context';
}

/**
 * Exception raised by the modules subsystem
 * @todo during module init(), any GUI hook functions registered will throw this
**/
class ModuleNotActiveException extends xarExceptions
{
    protected $message = 'The module "#(1)" was called, but it is not active.';
}

/*
    Bring in the module variables to maintain interface compatibility for now
*/
sys::import('xaraya.variables.module');
sys::import('xaraya.variables.moduser');

/**
 * Interface declaration for xarMod
 *
 * @todo this is very likely to change, it was created as baseline for refactoring
 */
interface ixarMod
{
    public const LOAD_UNDEFINED                   = 0;
    public const LOAD_ONLYACTIVE                  = 1;
    public const LOAD_ANYSTATE                    = 2;
    public const STATE_UNINITIALISED              = 1;
    public const STATE_INACTIVE                   = 2;
    public const STATE_ACTIVE                     = 3;
    public const STATE_MISSING_FROM_UNINITIALISED = 4;
    public const STATE_UPGRADED                   = 5;
    public const STATE_ANY                        = 0;
    public const STATE_INSTALLED                  = 6;
    public const STATE_MISSING_FROM_INACTIVE      = 7;
    public const STATE_MISSING_FROM_ACTIVE        = 8;
    public const STATE_MISSING_FROM_UPGRADED      = 9;
    public const STATE_ERROR_UNINITIALISED        = 10;
    public const STATE_ERROR_INACTIVE             = 11;
    public const STATE_ERROR_ACTIVE               = 12;
    public const STATE_ERROR_UPGRADED             = 13;
}

/**
 * Preliminary class to model xarMod interface
 *
 * @package core\modules
 */
class xarMod extends xarObject implements ixarMod
{
    public static $genShortUrls = false;
    public static $genXmlUrls   = true;
    public static $noCacheState = false;
    /** @var array<string, object> */
    private static $moduleClasses = [];
    protected static bool $initialized = false;
    protected static ?ModulesService $modService = null;

    protected static function mod(): ModulesService
    {
        if (!isset(self::$modService)) {
            $xar = xar::getServicesClass();
            self::$modService = $xar->mod();
        }
        return self::$modService;
    }

    /**
     * Initialize
     *
     */
    public static function init(array $args = [])
    {
        // static cache for migration
        self::$modService = null;
        return self::mod()->init($args);
    }

    public static function getConfig()
    {
        return self::mod()->getConfig();
    }

    /**
     * Get name of a module
     *
     * If regID is passed in, return the name of that module, otherwise use
     * current toplevel module.
     *
     * @param int|null $regID optional regID for module
     * @return string the name of the current top-level module
     */
    public static function getName($regID = null)
    {
        return self::mod()->getName($regID);
    }

    /**
     * Get the displayable name for modName
     *
     * The displayable name is sensible to user language.
     *
     * @param string $modName registered name of module
     * @param string $type determines theme or module
     * @return string the displayable name
     * @todo   re-evaluate this, i think it causes more harm than joy
     */
    public static function getDisplayName($modName = null, $type = 'module')
    {
        return self::mod()->getDisplayName($modName);
    }

    /**
     * Get the displayable description for modName
     *
     * The displayable description is sensible to user language.
     *
     * @param string $modName registered name of module
     * @param string $type determines theme or module
     * @return string the displayable description
     */
    public static function getDisplayDescription($modName = null, $type = 'module')
    {
        return self::mod()->getDisplayDescription($modName);
    }

    /**
     * Get module registry ID by name
     *
     * @param string $modName The name of the module
     * @param string $type determines theme or module
     * @return int|null The module registry ID.
     */
    public static function getRegID($modName, $type = 'module')
    {
        return self::mod()->getRegID($modName);
    }

    /**
     * Get module system ID by name
     *
     * @param string $modName The name of the module
     * @return int|void The module registry ID.
     */
    public static function getID($modName)
    {
        return self::mod()->getID($modName);
    }

    /**
     * Check if a module is installed and its state is STATE_ACTIVE
     *
     * @static $modAvailableCache array
     * @param string $modName registered name of module
     * @param string $type determines theme or module
     * @return bool true if the module is available
     */
    public static function isAvailable($modName, $type = 'module')
    {
        return self::mod()->isAvailable($modName);
    }

    /**
     * Get information on module
     *
     * @param int $modRegId module id
     * @param string $type determines theme or module
     * @return array<mixed> of module information
     * @throws EmptyParameterException
     * @throws BadParameterException
     * @throws IDNotFoundException
     */
    public static function getInfo($modRegId, $type = 'module')
    {
        return self::mod()->getInfo($modRegId);
    }

    /**
     * Load a module's base information
     *
     * @param string $modName the module's name
     * @param string $type determines theme or module
     * @return mixed an array of base module info on success
     * @throws EmptyParameterException
     * @throws BadParameterException
     */
    public static function getBaseInfo($modName, $type = 'module')
    {
        return self::mod()->getBaseInfo($modName);
    }

    /**
     * Get info from version.php for module specified by modOsDir
     *
     * @param string $modOsDir the module's directory
     * @param string $type determines theme or module
     * @return array<string, mixed>|void an array of module file information
     * @throws EmptyParameterException
     * @throws BadParameterException
     * @todo <marco> #1 FIXME: admin or admin capable?
     */
    public static function getFileInfo($modOsDir, $type = 'module')
    {
        return self::mod()->getFileInfo($modOsDir);
    }

    public static function parseFileInfo($version, $name = '')
    {
        return self::mod()->parseFileInfo($version, $name);
    }

    /**
     * Set noCache
     * @param bool $noCache
     * @return void
     */
    public static function setNoCache($noCache)
    {
        return self::mod()->setNoCache($noCache);
    }

    /**
     * Load database definition for a module
     *
     * @param string $modName name of module to load database definition for
     * @param string|null $modDir directory that module is in
     * @param string $type module or theme
     * @return mixed true on success
     * @throws EmptyParameterException
     */
    public static function loadDbInfo($modName, $modDir = null, $type = 'module')
    {
        return self::mod()->loadDbInfo($modName, $modDir);
    }

    /**
     * Call a module GUI function.
     *
     * Ex: modName_modType_funcName($args, $context);
     *
     * @param string $modName registered name of module
     * @param string $modType type of function to run
     * @param string $funcName specific function to run
     * @param array<string, mixed> $args arguments to pass to the function
     * @param ?Context<string, mixed> $context optional context for the function call (default = none)
     * @return mixed The output of the function, or raise an exception
     */
    public static function guiFunc($modName, $modType = 'user', $funcName = 'main', $args = [], $context = null)
    {
        return self::mod()->guiFunc($modName, $modType, $funcName, $args);
    }

    /**
     * Call a module API function.
     *
     * Using the modules name, type, func, and optional arguments
     * builds a function name by joining them together
     * and using the optional arguments as parameters
     * like so:
     * Ex: modName_modTypeapi_funcName($args, $context);
     *
     * @param string $modName registered name of module
     * @param string $modType type of function to run
     * @param string $funcName specific function to run
     * @param array<string, mixed> $args arguments to pass to the function
     * @param ?Context<string, mixed> $context optional context for the function call (default = none)
     * @return mixed The output of the function, or false on failure
     */
    public static function apiFunc($modName, $modType = 'user', $funcName = 'main', $args = [], $context = null)
    {
        return self::mod()->apiFunc($modName, $modType, $funcName, $args);
    }

    /**
     * Load the modType of module identified by modName.
     *
     * @param string $modName name of module to load
     * @param string $modType type of functions to load
     * @param int $flags flags to modify function behaviour (default LOAD_ONLYACTIVE)
     * @return mixed
     */
    public static function load($modName, $modType = 'user', $flags = self::LOAD_ONLYACTIVE, $context = null)
    {
        return self::mod()->load($modName, $modType);
    }

    /**
     * Load the modType API for module identified by modName.
     *
     * @param string $modName registered name of the module
     * @param string $modType type of functions to load
     * @param int $flags flags to modify function behaviour (default LOAD_ANYSTATE)
     * @return mixed true on success
     */
    public static function apiLoad($modName, $modType = 'user', $flags = self::LOAD_ANYSTATE, $context = null)
    {
        return self::mod()->apiLoad($modName, $modType);
    }

    /**
     * Get module class for modName based on defined namespace or ucfirst($modName)
     * @uses \sys::autoload()
     * @param string $modName
     * @return \Xaraya\Modules\ModuleInterface
     */
    public static function getModule($modName, $context = null)
    {
        return self::mod()->getModule($modName);
    }

    /**
     * Get module class handling user api functions
     * @param string $modName
     * @return \Xaraya\Modules\UserApiInterface|null
     */
    public static function userapi($modName, $context = null)
    {
        return self::mod()->userapi($modName);
    }

    /**
     * Get module class handling user gui functions
     * @param string $modName
     * @return \Xaraya\Modules\UserGuiInterface|null
     */
    public static function usergui($modName, $context = null)
    {
        return self::mod()->usergui($modName);
    }

    /**
     * Check if a particular module class method exists, or return null
     *
     * This looks for a module class in $modName handling $modType methods for method $funcName
     * and returns a callable to that method if it exists
     * Ex: [$instance, $funcName] => \Xaraya\Modules\$ModName\$ClassType()->$funcName($args);
     *
     * @param string $modName registered name of module -> used to define namespace
     * @param string $modType type of function to run (incl. funcType) -> will be mapped to class type
     * @param string $funcName specific function to run -> find corresponding method
     * @param string $callType is this called as an api function or not -> check against module class
     * @return callable|null
     */
    public static function getModuleClassMethod($modName, $modType, $funcName, $callType = 'api', $context = null)
    {
        return self::mod()->getModuleClassMethod($modName, $modType, $funcName, $callType);
    }

    /**
     * Check the version of this module against the core version
     *
     * @return boolean
     */
    public static function checkVersion($modName)
    {
        return self::mod()->getInfoHelper()->checkVersion($modName);
    }

    /**
     * Check if a particular module function exists, or default back to 'dynamicdata'
     *
     * @param string $tplmodule optional module where the templates reside
     * @param string $type link type (user, userapi, admin, adminapi, ...)
     * @param string $func link function (display, getitemtypes, ...)
     * @return string tplmodule or 'dynamicdata'
     */
    public static function checkModuleFunction($tplmodule = 'dynamicdata', $type = 'user', $func = 'display', $defaultmodule = 'dynamicdata')
    {
        return self::mod()->checkModuleFunction($tplmodule, $type, $func, $defaultmodule);
    }

    /**
     * Check access for a specific action on module level (see also xarObject and xarBlock)
     *
     * @param string $moduleName the module we want to check access for
     * @param string $action the action we want to take on this module (view/admin) // CHECKME: any others we really use on module level ?
     * @param mixed $roleid override the current user or null
     * @return boolean true if access
     * @throws BadParameterException
     */
    public static function checkAccess($moduleName, $action, $roleid = null)
    {
        return self::mod()->checkAccess($moduleName, $action, $roleid);
    }
}

/**
 * Interface declaration for module aliases
 *
 */
interface IxarModAlias
{
    public static function resolve($alias);
    public static function set($alias, $modName);
    public static function delete($alias, $modName);
}

/**
 * Class to model interface to module aliases
 *
 * @package core\modules
 * @version 2.8.5
 * @todo evaluate dependency consequences
 * @todo evaluate usage in modules, it's not very common, as in, perhaps worth to scrap and bolt onto a request mapper
 * @deprecated 2.8.5 use xar::mod()->*Alias instead
 */
class xarModAlias extends xarObject implements IxarModAlias
{
    protected static ?AliasHelper $modalias = null;

    protected static function modalias()
    {
        if (!isset(self::$modalias)) {
            $modalias = xar::getServicesClass()->service('modules.alias');
            assert($modalias instanceof AliasHelper);
            self::$modalias = $modalias;
        }
        return self::$modalias;
    }

    /**
     * Resolve an alias for a module
     */
    public static function resolve($alias)
    {
        // @todo move back to ModulesService for direct method calls
        return self::modalias()->resolve($alias);
    }

    /**
     * Set an alias for a module
     */
    public static function set($alias, $modName)
    {
        // @todo move back to ModulesService for direct method calls
        return self::modalias()->set($alias, $modName);
    }

    /**
     * Delete an alias for a module
     */
    public static function delete($alias, $modName)
    {
        return self::modalias()->remove($alias, $modName);
    }
}
