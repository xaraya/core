<?php

/**
 * Make Core Services available via self::service() etc. in trait (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use Xaraya\Context\Context;
use Xaraya\Modules\ModuleClassInterface;
use Xaraya\Modules\ModuleInterface;

/**
 * Make Core Services available via self::service() etc. in trait (WIP)
 *
 * ```
 * use Xaraya\Services\WithStaticServices;
 *
 * class xar
 * {
 *     use WithStaticServices;
 *
 *     public static function helloWorld():
 *     {
 *         $cache = self::service('cache);
 *         $dbconn = self::db()->getConn();
 *         // ...
 *     }
 * }
 * ```
 */
trait WithStaticServices
{
    /** @var class-string<ServiceStorageInterface> */
    public static $storageClass = StaticServiceStorage::class;
    /** @var ?ServiceStorageInterface */
    protected static $serviceStorage = null;  // Access core services with static methods
    public static $callers = [];

    /**
     * Set the storage class and reset the storage - see reactphp.php or swoole coroutine
     * @param class-string<ServiceStorageInterface> $storageClass
     */
    public static function setStorageClass(string $storageClass): void
    {
        static::$storageClass = $storageClass;
        static::$serviceStorage = null;
    }

    /**
     * Get the current service storage strategy.
     *
     * This is the key extension point. For a concurrent environment, this
     * method could be updated to return a Fiber-aware storage implementation.
     */
    protected static function getServiceStorage(): ServiceStorageInterface
    {
        if (static::$serviceStorage === null) {
            // For now, we always use the static storage for traditional requests.
            // In the future, we could detect a Fiber environment here and switch.
            if (class_exists(static::$storageClass)) {
                static::$serviceStorage = new static::$storageClass();
            } else {
                static::$serviceStorage = new StaticServiceStorage();
            }
        }
        return static::$serviceStorage;
    }

    /**
     * Get services class with optional context
     * @param ?Context<string, mixed> $context
     */
    public static function getServicesClass(?Context $context = null): StaticServicesClass
    {
        $storage = self::getServiceStorage();
        if (!$storage->has()) {
            $storage->set(new StaticServicesClass());
        }
        $services = $storage->get();
        assert($services instanceof StaticServicesClass);
        if (!is_null($context)) {
            $services->setContext($context);
        }
        return $services;
    }

    /**
     * Set context for core services
     * @param Context<string, mixed> $context
     */
    public static function setServicesContext(Context $context): StaticServicesClass
    {
        return self::getServicesClass($context);
    }

    /**
     * Get core service by name (static)
     * @param array<mixed> $args
     */
    public static function service(string $name, ...$args): ServiceInterface
    {
        return self::getServicesClass()->service($name, ...$args);
    }

    /**
     * Access xarController::* Main Controller methods (URL, redirect, ...)
     *
     * Available methods:
     * - getModuleURL() - or use mod()->getURL() for current module
     * - getObjectURL()
     * - getActionURL() - or use $object->getActionURL() with actual object
     * - getRouteURL() - @todo
     * - getCurrentURL()
     * - getBaseURL()
     * - getBaseURI()
     * - getServerVar()
     * - getRequest()
     * - getRequestVar()
     * - getRequestMethod()
     * - isSameReferer()
     * - redirect()
     * - forbidden()
     * - notFound()
     * - badRequest()
     * - ...
     *
     */
    public static function ctl(): ControllerInterface
    {
        return self::getServicesClass()->ctl();
    }

    /**
     * Access xarLog::* Logger methods (message, variable, ...)
     *
     * Available methods:
     * - emergency($message, $var = [])
     * - alert($message, $var = [])
     * - critical($message, $var = [])
     * - error($message, $var = [])
     * - warning($message, $var = [])
     * - notice($message, $var = [])
     * - info($message, $var = [])
     * - debug($message, $var = [])
     * - log($level, $message, $var = [])
     * - message($message, $level = xarLog::LEVEL_DEBUG) - original xarLog::message() using $level param
     * - variable($message, $var, $level = xarLog::LEVEL_DEBUG) - original xarLog::variable() using $level param
     *
     */
    public static function log(): LoggerInterface
    {
        return self::getServicesClass()->log();
    }

    /**
     * Access xarMLS::* Multi-Language System methods (translate, ...)
     *
     * Available methods:
     * - getCurrentLocale()
     * - getCharsetFromLocale()
     * - loadLocale()
     * - formatDate()
     * - getFormattedDate()
     * - getFormattedTime()
     * - translate()
     * - loadTranslations()
     * - loadModuleTranslations()
     * - loadObjectTranslations()
     * - ...
     *
     */
    public static function mls(): MultiLanguageInterface
    {
        return self::getServicesClass()->mls();
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
     * - template() for current module type - @deprecated 2.9.3 replaced with render()
     * - render() for current module type - @deprecated 2.9.3 use module class render() or hookobserver render() instead, or use tpl()->module() in general with modName modType
     * - prepare() for current module itemtype - @deprecated 2.9.3 prepare tplData in module class render() or hookobserver render() instead
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
     *
     * Optional methods in parent:
     * - getItemType() for mod()->prepare(), mod()->isHooked() and mod()->callHooks()
     * - getModType() for mod()->render() - @deprecated 2.9.3
     *
     */
    public static function mod(?string $modName = null): ModulesInterface
    {
        return self::getServicesClass()->mod($modName);
    }

    /**
     * Access xarSec::* Security methods (checkAccess, genAuthKey, ...)
     *
     * Available methods:
     * - checkAccess()
     * - genAuthKey()
     * - confirmAuthKey()
     * - ...
     *
     * Required methods in parent:
     * - getModName()
     *
     */
    public static function sec(): SecurityInterface
    {
        return self::getServicesClass()->sec();
    }

    /**
     * Access xarTpl::* Templating methods (module, setPageTitle, ...)
     *
     * Available methods:
     * - module()
     * - block()
     * - object()
     * - property()
     * - setPageTitle()
     * - setPageTemplateName()
     * - getImage()
     * - getPager()
     * - ...
     *
     * Optional methods in parent:
     * - getModName() for tpl()->setPageTitle() - @deprecated 2.9.2 use xar::mod()->getName() instead
     *
     */
    public static function tpl(): TemplatingInterface
    {
        return self::getServicesClass()->tpl();
    }

    /**
     * Access xarVar::* Variables methods (fetch, check, prep, ...)
     * @todo identify what can be shared across requests and what should be private
     * in concurrent environments - see xar::mem()->cacheCollection array
     *
     * Available methods:
     * - get() - xarVar::GET_OR_POST = Get required variable by name: set the value if there is one, and validate the variable or throw excception
     * - check() - xarVar::DONT_SET = Check existing variable by name: use current value or get it by name if it is not already set, and validate the variable
     * - find() - xarVar::NOT_REQUIRED = Find optional variable by name: set the value if there is one, and validate the variable
     * - update() - xarVar::DONT_REUSE = Update required variable by name: set the value if there is one or reset it, and validate the variable or throw exception
     * - fetch() - original xarVar::fetch() with different order of params than above
     * - validate() - or use $this->prep()->validate() instead
     * - ...
     *
     */
    public static function var(): VariablesInterface
    {
        return self::getServicesClass()->var();
    }

    /**
     * Access xarBlock*::* Blocks methods (render, ...)
     *
     * Available methods:
     * - render()
     * - renderBlock()
     * - renderGroup()
     * - guiRequest()
     * - apiRequest()
     * - ...
     *
     */
    public static function block(): BlocksInterface
    {
        return self::getServicesClass()->block();
    }

    /**
     * Access DataObjectFactory::* methods with context (getObject, getObjectList, ...)
     *
     * Available methods:
     * - getURL() for current object - @deprecated 2.9.0 use xar::ctl()->getObjectURL() instead
     * - template() for current object - @deprecated 2.9.2 use xar::tpl()->object() instead
     * - getObject()
     * - getObjectList()
     * - getObjectLoader()
     * - getObjectInfo()
     * - getObjects()
     * - getObjectID()
     * - getObjectDescriptor()
     * - ...
     *
     * Required methods in parent:
     * - getObjectName() for data()->getURL() - @deprecated 2.9.0 use xar::ctl()->getObjectURL() instead
     * - getModName() for data()->template() - @deprecated 2.9.2 use xar::tpl()->object() instead
     * - getObjectTemplate() for data()->template() - @deprecated 2.9.2 use xar::tpl()->object() instead
     *
     */
    public static function data(): DataObjectInterface
    {
        return self::getServicesClass()->data();
    }

    /**
     * Access DataProperty*::* methods with context (getProperty, getPropertyTypes, ...)
     *
     * Available methods:
     * - getPropertyTypes()
     * - getProperties()
     * - getProperty()
     * - ...
     *
     */
    public static function prop(): DataPropertyInterface
    {
        return self::getServicesClass()->prop();
    }

    /**
     * Access xar*Cache::* Caching methods (getModuleKey, getObjectKey, ...)
     *
     * Available methods:
     * - getPageKey()
     * - hasPage()
     * - sendPage() - output to browser
     * - getPage()
     * - setPage()
     * - getModuleKey()
     * - hasModule()
     * - getModule()
     * - setModule()
     * - getBlockKey()
     * - hasBlock()
     * - getBlock()
     * - setBlock()
     * - getObjectKey()
     * - hasObject()
     * - getObject()
     * - setObject()
     * - getVariableKey()
     * - hasVariable()
     * - getVariable()
     * - setVariable()
     * - delVariable()
     * - ...
     *
     */
    public static function cache(): CachingInterface
    {
        return self::getServicesClass()->cache();
    }

    /**
     * Access MemoryService methods (has, get, set, del, flush, ...)
     *
     * Available methods:
     * - has()
     * - get()
     * - set()
     * - del()
     * - flush()
     * - hasPreload()
     * - load()
     * - save()
     *
     */
    public static function mem(): MemoryInterface
    {
        return self::getServicesClass()->mem();
    }

    /**
     * Access RequestService methods (getModule, getType, getFunction, ...)
     *
     * Available methods:
     * - getModule()
     * - getType()
     * - getFunction()
     * - getCurrentURL()
     * - getBaseURI()
     * - getServerVar()
     * - getVar()
     * - setServerVar()
     * - getMethod()
     * - isLocalReferer()
     * - isSameReferer()
     */
    public static function req(): RequestInterface
    {
        return self::getServicesClass()->req();
    }

    /**
     * Access xarConfigVars::* Config methods (getVar, setVar, ...)
     *
     * Available methods:
     * - getVar()
     * - setVar()
     * - delVar()
     * - cache()
     * - ...
     *
     */
    public static function config(): ConfigInterface
    {
        return self::getServicesClass()->config();
    }

    /**
     * Access xarSystemVars::* System methods (getVar, setVar, ...)
     *
     * Available methods:
     * - getVar()
     * - setVar()
     * - delVar()
     * - cache()
     * - ...
     *
     */
    public static function sysConfig(?string $scope = null): SystemInterface
    {
        return self::getServicesClass()->sysConfig($scope);
    }

    /**
     * Access xarSession::* Session methods (getVar, setVar, ...)
     *
     * Available methods:
     * - getVar()
     * - setVar()
     * - delVar()
     * - getUserId()
     * - getAnonId()
     * - ...
     *
     */
    public static function session(): SessionInterface
    {
        return self::getServicesClass()->session();
    }

    /**
     * Access xarUser::* User methods (getVar, setVar, ...)
     *
     * Available methods:
     * - getVar()
     * - setVar()
     * - getId()
     * - isLoggedIn()
     * - isDebugAdmin()
     * - isSiteAdmin()
     * - ...
     *
     */
    public static function user(?int $userId = null): UserInterface
    {
        return self::getServicesClass()->user($userId);
    }

    /**
     * Access xarDB::* Database methods (getConn, getPrefix, ...)
     *
     * Available methods:
     * - getConn()
     * - getFetchAssoc()
     * - getFetchEnum()
     * - getPrefix()
     * - getType()
     * - getTables()
     * - importTables()
     * - ...
     */
    public static function db(): DatabaseInterface
    {
        return self::getServicesClass()->db();
    }

    /**
     * Access xarVarPrep::* methods (text, html, ...)
     *
     * Available methods:
     * - text()
     * - html()
     * - email()
     * - path()
     * - validate()
     */
    public static function prep(): VarPrepInterface
    {
        return self::getServicesClass()->prep();
    }

    /**
     * Access xarEvents::* methods (notify, ...)
     *
     * Available methods:
     * - notify()
     * - ...
     */
    public static function events(): EventsInterface
    {
        return self::getServicesClass()->events();
    }

    /**
     * Access xarHooks::* methods (notify, ...)
     *
     * Available methods:
     * - notify()
     * - ...
     */
    public static function hooked(): HookedInterface
    {
        return self::getServicesClass()->hooked();
    }

    /**
     * Access xarTheme::* methods (isAvailable, getInfo, ...)
     *
     * Available methods:
     * - isAvailable()
     * - getInfo()
     * - ...
     */
    public static function theme(): ThemesInterface
    {
        return self::getServicesClass()->theme();
    }

    public static function module(string $modName): ModuleInterface
    {
        return self::getServicesClass()->module($modName);
    }

    public static function modclass(string $modName, string $modType): ?ModuleClassInterface
    {
        return self::getServicesClass()->modclass($modName, $modType);
    }

    /**
     * Call exit() - override for non-blocking servers, php unit tests or elsewhere
     * @return void|never
     */
    public static function exit(int|string $status = 0)
    {
        \xarLog::message('Exit from ' . __CLASS__, \xarLog::LEVEL_NOTICE);
        \xarCore::exit($status);
    }

    /**
     * Use static sys::* methods (code, varpath, ...) directly here
     * @throws \BadMethodCallException
     * @return never
     */
    public function sys()
    {
        throw new \BadMethodCallException('Use static sys::* methods (code, varpath, ...) directly here');
    }

    /**
     * Translate string with optional arguments
     * = short-hand version for self::mls()->translate()
     * @param string $rawstring
     * @param mixed ...$args
     */
    public static function ml($rawstring, ...$args): string
    {
        return self::getServicesClass()->mls()->translate($rawstring, ...$args);
    }
}
