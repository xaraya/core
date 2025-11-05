<?php

/**
 * Get core services from parent class (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\Services;

use Xaraya\Context\ContextTrait;
use sys;

sys::import('xaraya.context.contexttrait');
sys::import('xaraya.services.servicestrait');

/**
 * For documentation purposes only - available via ParentServicesTrait
 */
interface ParentServicesInterface extends CoreServicesInterface
{
    public function getParent(): ServicesInterface;
}

/**
 * Get core services from parent class
 *
 * Available services:
 * - $this->ctl() = xarController::* Main Controller (getURL, redirect, ...)
 * - $this->req() = xarRequest::* Server Request (getModule, getType, ...)
 * - $this->log() = xarLog::* Logger (message, variable, ...)
 * - $this->mem() = xarCoreCache::* Memory Cache (has, get, ...)
 * - $this->mls() = xarMLS::* Multi-Language System (translate, ...)
 * - $this->mod() = xarMod*::* Modules (getVar, setVar, ...)
 * - $this->sec() = xarSec::* Security (checkAccess, genAuthKey, ...)
 * - $this->tpl() = xarTpl::* Templating (module, setPageTitle, ...)
 * - $this->var() = xarVar::* Variables (fetch, check, ...)
 * - $this->block() = xarBlock*::* Blocks (render, ...)
 * - $this->data() = DataObjectFactory::* with context (getObject, getObjectList, ...)
 * - $this->prop() = DataProperty*::* with context (getProperty, getPropertyTypes, ...)
 * - $this->cache() = xar*Cache::* Caching (getModuleKey, getObjectKey, ...)
 * - $this->config() = xarConfigVars::* Config (getVar, setVar, ...)
 * - $this->system() = xarSystemVars::* System (getVar, setVar, ...)
 * - $this->session() = xarSession::* Session (getVar, setVar, ...)
 * - $this->db() = xarDB::* Database (getConn, getPrefix, ...)
 * - ...
 * - $this->service($name, ...$args) = get core service by name
 * - $this->ml($rawstring, ...$args) = short-hand version for $this->mls()->translate()
 * - $this->exit($status = 0) = call exit() - override for non-blocking servers, php unit tests or elsewhere
 *
 */
trait ParentServicesTrait
{
    use ContextTrait;

    /**
     * Get static services class for shared service instances
     */
    public function getStaticServices(): StaticServicesClass
    {
        return $this->getParent()->getStaticServices();
    }

    /**
     * Get local service by key
     */
    public function getLocalService(string $key): ServiceInterface|callable|null
    {
        return $this->getParent()->getLocalService($key);
    }

    /**
     * Set local service by key
     */
    public function setLocalService(string $key, ServiceInterface|callable $service): void
    {
        $this->getParent()->setLocalService($key, $service);
    }

    public function hasLocalService(string $key): bool
    {
        return $this->getParent()->hasLocalService($key);
    }

    /**
     * Set core services for access via methods
     * @param array<string, mixed> $args array of name => service to replace default ones
     */
    public function setCoreServices(array $args = []): void
    {
        $this->getParent()->setCoreServices($args);
    }

    /**
     * Get core service by name
     * @param array<mixed> $args
     */
    public function service(string $name, ...$args): ServiceInterface
    {
        return $this->getParent()->service($name, ...$args);
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
        return $this->getParent()->ctl();
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
        return $this->getParent()->log();
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
        return $this->getParent()->mls();
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
        return $this->getParent()->mod($modName);
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
        return $this->getParent()->sec();
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
        return $this->getParent()->tpl();
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
     * - validate() - or use $this->prep()->validate() instead
     * - prep() - @deprecated use $this->prep()->text() instead
     * - prepHTML() - @deprecated use $this->prep()->html() instead
     * - ...
     *
     */
    public function var(): VariablesInterface
    {
        return $this->getParent()->var();
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
    public function block(): BlocksInterface
    {
        return $this->getParent()->block();
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
        return $this->getParent()->data();
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
    public function prop(): DataPropertyInterface
    {
        return $this->getParent()->prop();
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
     * Required methods in parent:
     * - getObject() for cache()->getObjectKey(null, '...')
     *
     */
    public function cache(): CachingInterface
    {
        return $this->getParent()->cache();
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
    public function mem(): MemoryInterface
    {
        return $this->getParent()->mem();
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
    public function req(): RequestInterface
    {
        return $this->getParent()->req();
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
        return $this->getParent()->config();
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
    public function system(): SystemInterface
    {
        return $this->getParent()->system();
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
        return $this->getParent()->session();
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
        return $this->getParent()->user($userId);
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
        return $this->getParent()->db();
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
    public function prep(): WrapperInterface
    {
        return $this->getParent()->prep();
    }

    public function events(): WrapperInterface
    {
        return $this->getParent()->events();
    }

    public function hooked(): WrapperInterface
    {
        return $this->getParent()->hooked();
    }

    public function theme(): WrapperInterface
    {
        return $this->getParent()->theme();
    }

    /**
     * Call exit() - override for non-blocking servers, php unit tests or elsewhere
     * @return void|never
     */
    public function exit(int|string $status = 0)
    {
        $this->getParent()->exit($status);
    }

    /**
     * Access sys::* methods (code, varpath, ...) as instance methods in templates
     */
    public function sys(): sys
    {
        return $this->getParent()->sys();
    }

    /**
     * Translate string with optional arguments
     * = short-hand version for $this->mls()->translate()
     * @param string $rawstring
     * @param mixed ...$args
     */
    public function ml($rawstring, ...$args): string
    {
        return $this->getParent()->ml($rawstring, ...$args);
    }

    public function getParent(): ServicesInterface
    {
        assert($this->parent instanceof ServicesInterface);
        return $this->parent;
    }
}

/**
 * Example parent class offering core services
 */
class ParentServicesClass extends ServicesClass
{
    // ...
}

/**
 * Child class using services from parent class
 * e.g. method -> module or property -> object or service -> services
 */
class ChildServicesClass implements ParentServicesInterface
{
    use ParentServicesTrait;

    protected ServicesInterface $parent;
}
