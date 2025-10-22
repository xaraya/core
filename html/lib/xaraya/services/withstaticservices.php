<?php

/**
 * Make Core Services available via self::service() etc. in trait (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use Xaraya\Context\Context;
use sys;

/**
 * Make Core Services available via self::service() etc. in trait (WIP)
 *
 * ```
 * use Xaraya\Services\WithStaticServices;
 *
 * class SomethingInteresting
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
            sys::import('xaraya.services.servicestorage');
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
    public static function getServicesClass(?Context $context = null): ServicesInterface
    {
        $storage = self::getServiceStorage();
        if (!$storage->has()) {
            $storage->set(new ServicesClass());
        }
        $services = $storage->get();
        assert($services instanceof ServicesInterface);
        if (!is_null($context)) {
            $services->setContext($context);
        }
        return $services;
    }

    /**
     * Set context for core services
     * @param Context<string, mixed> $context
     */
    public static function setServicesContext(Context $context): void
    {
        self::getServicesClass($context);
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
     * - getObjectURL() - or use data()->getURL() for current object
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
        return self::service('ctl');
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
     * - log($message, $var = [])
     * - message($message, $level = xarLog::LEVEL_DEBUG) - original xarLog::message() using $level param
     * - variable($message, $var, $level = xarLog::LEVEL_DEBUG) - original xarLog::variable() using $level param
     *
     */
    public static function log(): LoggerInterface
    {
        return self::service('log');
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
        return self::service('mls');
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
     *
     * Optional methods in parent:
     * - getItemType() for mod()->prepare(), mod()->isHooked() and mod()->callHooks()
     * - getModType() for mod()->template()
     *
     */
    public static function mod(?string $modName = null): ModulesInterface
    {
        if (is_null(self::getServicesClass()->getContext())) {
            throw new \RuntimeException('Missing context for core services');
        }
        return self::service('mod', $modName);
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
        return self::service('sec');
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
     * - getModName() for tpl()->setPageTitle()
     *
     */
    public static function tpl(): TemplatingInterface
    {
        return self::service('tpl');
    }

    /**
     * Access xarVar::* Variables methods (fetch, check, prep, ...)
     *
     * Available methods:
     * - get() - xarVar::GET_OR_POST = Get required variable by name: set the value if there is one, and validate the variable or throw excception
     * - check() - xarVar::DONT_SET = Check existing variable by name: use current value or get it by name if it is not already set, and validate the variable
     * - find() - xarVar::NOT_REQUIRED = Find optional variable by name: set the value if there is one, and validate the variable
     * - update() - xarVar::DONT_REUSE = Update required variable by name: set the value if there is one or reset it, and validate the variable or throw exception
     * - fetch() - original xarVar::fetch() with different order of params than above
     * - validate()
     * - prep()
     * - prepHTML()
     * - ...
     *
     */
    public static function var(): VariablesInterface
    {
        return self::service('var');
    }

    /**
     * Access xarBlock*::* Blocks methods (template, ...)
     *
     * Available methods:
     * - template() for current block type - @deprecated 2.8.1 use tpl()->block() in general with modName blockType
     * - prepare() - @deprecated 2.8.1 only used in block()->template()
     * - guiRequest()
     * - apiRequest()
     * - ...
     *
     * Required methods in parent:
     * - getModName() for block()->template() and block()->prepare()
     * - getBlockType() for block()->template()
     *
     */
    public static function block(): BlocksInterface
    {
        return self::service('block');
    }

    /**
     * Access DataObjectFactory::* methods with context (getObject, getObjectList, ...)
     *
     * Available methods:
     * - getURL() for current object - or use ctl()->getObjectURL() in general with objectName
     * - template() for current object - or use tpl()->object() in general with modName objectTemplate
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
     * - getObjectName() for data()->getURL()
     *
     */
    public static function data(): DataObjectInterface
    {
        return self::service('data');
    }

    /**
     * Access DataProperty*::* methods with context (getProperty, template, ...)
     *
     * Available methods:
     * - template() for current property - @deprecated 2.8.1 use tpl()->property() in general with modName propertyName
     * - getPropertyTypes()
     * - getProperties()
     * - getProperty()
     * - ...
     *
     * Required methods in parent:
     * - getModName() for prop()->template()
     * - getPropertyTemplate() for prop()->template()
     *
     */
    public static function prop(): DataPropertyInterface
    {
        return self::service('prop');
    }

    /**
     * Access xar*Cache::* Caching methods (getModuleKey, getObjectKey, ...)
     *
     * Available methods:
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
     * Required methods in parent:
     * - getObject() for cache()->getObjectKey(null, '...')
     *
     */
    public static function cache(): CachingInterface
    {
        return self::service('cache');
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
        return self::service('config');
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
        return self::service('session');
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
        if (is_null(self::getServicesClass()->getContext())) {
            throw new \RuntimeException('Missing context for core services');
        }
        return self::service('user', $userId);
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
        return self::service('db');
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
        return self::mls()->translate($rawstring, ...$args);
    }
}
