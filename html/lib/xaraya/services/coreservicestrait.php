<?php

/**
 * Core Services for classes (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\Services;

use Xaraya\Context\ContextInterface;
use Xaraya\Context\ContextTrait;
use sys;
use Exception;

sys::import('xaraya.context.contexttrait');
sys::import('xaraya.services.controller');
sys::import('xaraya.services.logger');
sys::import('xaraya.services.multilanguage');
sys::import('xaraya.services.modules');
sys::import('xaraya.services.security');
sys::import('xaraya.services.templating');
sys::import('xaraya.services.variables');
sys::import('xaraya.services.blocks');
sys::import('xaraya.services.dataobject');
sys::import('xaraya.services.dataproperty');
sys::import('xaraya.services.caching');
sys::import('xaraya.objects');

/**
 * For documentation purposes only - available via CoreServicesTrait
 */
interface CoreServicesInterface extends ContextInterface
{
    /** @param array<string, mixed> $args */
    public function setCoreServices(array $args = []): void;
    /** @param array<mixed> $args */
    public function service(string $name, ...$args): ServiceInterface;
    public function ctl(): ControllerInterface;
    public function log(): LoggerInterface;
    public function mls(): MultiLanguageInterface;
    public function mod(?string $modName = null): ModulesInterface;
    public function sec(): SecurityInterface;
    public function tpl(): TemplatingInterface;
    public function var(): VariablesInterface;
    public function block(): BlocksInterface;
    public function data(): DataObjectInterface;
    public function prop(): DataPropertyInterface;
    public function cache(): CachingInterface;
    public function config(): ConfigInterface;
    public function session(): SessionInterface;
    public function user(?int $userId = null): UserInterface;
    public function db(): DatabaseInterface;
    /**
     * Call exit() - override for non-blocking servers, php unit tests or elsewhere
     * @return void|never
     */
    public function exit(int|string $status = 0);
    public function sys(): sys;
    /**
     * Translate string with optional arguments
     * = short-hand version for $this->mls()->translate()
     * @param string $rawstring
     * @param mixed ...$args
     */
    public function ml($rawstring, ...$args): string;
}

/**
 * Core Services trait for classes
 *
 * This defines the core services available to the parent class
 */
trait CoreServicesTrait
{
    use ContextTrait;

    protected ?ControllerInterface $xarCtl = null;
    protected ?LoggerInterface $xarLog = null;
    protected ?MultiLanguageInterface $xarMls = null;
    protected ?ModulesInterface $xarMod = null;
    /** @var array<string, ModulesInterface> */
    protected array $xarModClones = [];
    protected ?SecurityInterface $xarSec = null;
    protected ?TemplatingInterface $xarTpl = null;
    protected ?VariablesInterface $xarVar = null;
    protected ?BlocksInterface $xarBlock = null;
    protected ?DataObjectInterface $xarData = null;
    protected ?DataPropertyInterface $xarProp = null;
    protected ?CachingInterface $xarCache = null;
    protected ?ConfigInterface $xarConfig = null;
    protected ?SessionInterface $xarSession = null;
    protected ?UserInterface $xarUser = null;
    /** @var array<string, UserInterface> */
    protected array $xarUserClones = [];
    protected ?DatabaseInterface $xarDb = null;
    /** @var ?callable */
    protected $xarExit = null;
    protected ?sys $xarSys = null;

    /**
     * Set core services for access via methods
     * @param array<string, mixed> $args array of name => service to replace default ones
     */
    public function setCoreServices(array $args = []): void
    {
        $supported = ['ctl', 'log', 'mls', 'mod', 'sec', 'tpl', 'var', 'block', 'data', 'prop', 'cache', 'config', 'session', 'user', 'db', 'exit', 'sys'];
        foreach ($args as $name => $service) {
            if (!in_array($name, $supported)) {
                throw new Exception('Unsupported service ' . $name);
            }
            $varName = 'xar' . ucfirst($name);
            if (!property_exists($this, $varName)) {
                throw new Exception('Unsupported property ' . $varName);
            }
            $this->{$varName} = $service;
        }
    }

    /**
     * Get core service by name
     * @param array<mixed> $args
     */
    public function service(string $name, ...$args): ServiceInterface
    {
        return match ($name) {
            'ctl' => $this->ctl(),
            'log' => $this->log(),
            'mls' => $this->mls(),
            'mod' => $this->mod(...$args),
            'sec' => $this->sec(),
            'tpl' => $this->tpl(),
            'var' => $this->var(),
            'block' => $this->block(),
            'data' => $this->data(),
            'prop' => $this->prop(),
            'cache' => $this->cache(),
            'config' => $this->config(),
            'session' => $this->session(),
            'user' => $this->user(...$args),
            'db' => $this->db(),
            default => throw new Exception('Unsupported service ' . $name),
        };
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
    public function ctl(): ControllerInterface
    {
        $this->xarCtl ??= ServiceFactory::getControllerService($this);
        return $this->xarCtl;
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
    public function log(): LoggerInterface
    {
        $this->xarLog ??= ServiceFactory::getLoggerService($this);
        return $this->xarLog;
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
    public function mls(): MultiLanguageInterface
    {
        $this->xarMls ??= ServiceFactory::getMultiLanguageService($this);
        return $this->xarMls;
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
    public function mod(?string $modName = null): ModulesInterface
    {
        $this->xarMod ??= ServiceFactory::getModulesService($this);
        if (isset($modName)) {
            // @todo cache clones per modName too?
            if (!array_key_exists($modName, $this->xarModClones)) {
                $this->xarModClones[$modName] = clone $this->xarMod;
                $this->xarModClones[$modName]->setCurrentModName($modName);
            }
            return $this->xarModClones[$modName];
        }
        return $this->xarMod;
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
    public function sec(): SecurityInterface
    {
        $this->xarSec ??= ServiceFactory::getSecurityService($this);
        return $this->xarSec;
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
    public function tpl(): TemplatingInterface
    {
        $this->xarTpl ??= ServiceFactory::getTemplatingService($this);
        return $this->xarTpl;
    }

    /**
     * Access xarVar::* Variables methods (fetch, get, prep, ...)
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
    public function var(): VariablesInterface
    {
        $this->xarVar ??= ServiceFactory::getVariablesService($this);
        return $this->xarVar;
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
    public function block(): BlocksInterface
    {
        $this->xarBlock ??= ServiceFactory::getBlocksService($this);
        return $this->xarBlock;
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
    public function data(): DataObjectInterface
    {
        $this->xarData ??= ServiceFactory::getDataObjectService($this);
        return $this->xarData;
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
    public function prop(): DataPropertyInterface
    {
        $this->xarProp ??= ServiceFactory::getDataPropertyService($this);
        return $this->xarProp;
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
    public function cache(): CachingInterface
    {
        $this->xarCache ??= ServiceFactory::getCachingService($this);
        return $this->xarCache;
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
    public function config(): ConfigInterface
    {
        $this->xarConfig ??= ServiceFactory::getConfigService($this);
        return $this->xarConfig;
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
    public function session(): SessionInterface
    {
        $this->xarSession ??= ServiceFactory::getSessionService($this);
        return $this->xarSession;
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
    public function user(?int $userId = null): UserInterface
    {
        $this->xarUser ??= ServiceFactory::getUserService($this);
        if (isset($userId)) {
            // @todo cache clones per userId too?
            if (!array_key_exists($userId, $this->xarUserClones)) {
                $this->xarUserClones[$userId] = clone $this->xarUser;
                $this->xarUserClones[$userId]->setCurrentId($userId);
            }
            return $this->xarUserClones[$userId];
        }
        return $this->xarUser;
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
    public function db(): DatabaseInterface
    {
        $this->xarDb ??= ServiceFactory::getDatabaseService($this);
        return $this->xarDb;
    }

    /**
     * Call exit() - override for non-blocking servers, php unit tests or elsewhere
     * @return void|never
     */
    public function exit(int|string $status = 0)
    {
        $this->xarExit ??= ServiceFactory::getExitCallable($this);
        // call exit callable :-)
        call_user_func($this->xarExit, $status);
    }

    /**
     * Access sys::* methods (code, varpath, ...) as instance methods in templates
     */
    public function sys(): sys
    {
        if (!isset($this->xarSys)) {
            // bypass private constructor for final class
            $class = new \ReflectionClass(sys::class);
            $this->xarSys = $class->newInstanceWithoutConstructor();
        }
        return $this->xarSys;
    }

    /**
     * Translate string with optional arguments
     * = short-hand version for $this->mls()->translate()
     * @param string $rawstring
     * @param mixed ...$args
     */
    public function ml($rawstring, ...$args): string
    {
        return $this->mls()->translate($rawstring, ...$args);
    }
}
