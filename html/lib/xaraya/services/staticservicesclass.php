<?php

/**
 * Core Services for static classes (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\Services;

use Xaraya\Context\Context;
use Xaraya\Requests\RequestInterface as RequestFacade;
use Xaraya\Sessions\SessionInterface as SessionFacade;
use xarCore;

/**
 * Core Services for static classes (WIP)
 *
 * This holds the context, request and session in storage
 * for each request in normal static, reactphp fiber and
 * swoole coroutine environment - see WithStaticServices
 */
class StaticServicesClass extends ServicesClass
{
    public const SLICE = 'static';

    /** @var ?RequestFacade */
    protected $requestInstance = null;
    /** @var ?SessionFacade */
    protected $sessionInstance = null;
    /** @var array<string, ServiceInterface> */
    public array $serviceCache = [];

    public function __construct()
    {
        // let's kick-start core cache here
        $this->mem();
    }

    /**
     * @return Context<string, mixed>|null
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param ?Context<string, mixed> $context
     * @return void
     */
    public function setContext($context)
    {
        $this->context = $context;
        // reset request & session instance if context is replaced
        $this->requestInstance = null;
        $this->sessionInstance = null;
        // don't reset core cache instance here
        //$this->memoryInstance = null;
    }

    /**
     * Set core services for access via methods
     * @param array<string, mixed> $args array of name => service to replace default ones
     */
    public function setCoreServices(array $args = []): void
    {
        $supported = ['ctl', 'req', 'log', 'mls', 'mod', 'sec', 'tpl', 'var', 'block', 'data', 'prop', 'cache', 'mem', 'config', 'session', 'user', 'db', 'exit'];
        foreach ($args as $name => $service) {
            if (!in_array($name, $supported)) {
                throw new Exception('Unsupported service ' . $name);
            }
            // Pre-populate the static services cache with the mocked/overridden service.
            $this->serviceCache[$name] = $service;
        }
        parent::setCoreServices($args);
    }

    /**
     * @return RequestFacade|null
     * @see xar::req()->getInstance()
     */
    public function getRequestInstance()
    {
        if (!isset($this->requestInstance)) {
            // do *not* initialize request here - depends on the caller
            //$this->requestInstance ??= $this->req()->newInstance($this->context);
        }
        return $this->requestInstance;
    }

    /**
     * @param ?RequestFacade $instance
     * @return void
     * @see xar::req()->setInstance()
     */
    public function setRequestInstance($instance)
    {
        $this->requestInstance = $instance;
    }

    /**
     * @return SessionFacade|null
     * @see xar::session()->getInstance()
     */
    public function getSessionInstance()
    {
        if (!isset($this->sessionInstance)) {
            // do *not* initialize session here - depends on the caller
            //$this->sessionInstance ??= $this->session()->newInstance($this->context);
        }
        return $this->sessionInstance;
    }

    /**
     * @param ?SessionFacade $instance
     * @return void
     * @see xar::session()->setInstance()
     */
    public function setSessionInstance($instance)
    {
        $this->sessionInstance = $instance;
    }

    /**
     * Get the static services class instance for this request - that's $this here
     */
    public function getStaticServices(): StaticServicesClass
    {
        return $this;
    }

    /**
     * Get core service by name - optimize for static shared services
     * @param array<mixed> $args
     */
    public function service(string $name, ...$args): ServiceInterface
    {
        // 1. Handle shared services (request-scoped singletons).
        if (in_array($name, ServiceFactory::$sharedServices)) {
            // Shared services are singletons per request, so we return the prototype directly.
            return $this->getServicePrototype($name);
        }
        return ServiceOrchestrator::get($this, $name, ...$args);
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
        return $this->getServicePrototype('ctl');
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
        return $this->getServicePrototype('log');
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
        return $this->getServicePrototype('mls');
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
     * - validate() - or use $this->prep()->validate() instead
     * - ...
     *
     */
    public function var(): VariablesInterface
    {
        return $this->getServicePrototype('var');
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
        return $this->getServicePrototype('block');
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
        return $this->getServicePrototype('prop');
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
        return $this->getServicePrototype('cache');
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
        return $this->getServicePrototype('mem');
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
        return $this->getServicePrototype('req');
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
        return $this->getServicePrototype('config');
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
    public function sysConfig(?string $scope = null): SystemInterface
    {
        if (!empty($scope)) {
            return $this->service('system', $scope);
        }
        return $this->getServicePrototype('system');
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
        return $this->getServicePrototype('session');
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
        if (!empty($userId)) {
            return $this->service('user', $userId);
        }
        return $this->getServicePrototype('user');
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
        return $this->getServicePrototype('db');
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
        return $this->getServicePrototype('prep');
    }

    public function events(): WrapperInterface
    {
        return $this->getServicePrototype('events');
    }

    public function hooked(): WrapperInterface
    {
        return $this->getServicePrototype('hooked');
    }

    public function theme(): WrapperInterface
    {
        return $this->getServicePrototype('theme');
    }

    /**
     * Get a service prototype instance, creating it if not already cached.
     * This ensures only one prototype per service type per request.
     *
     * @param string $name The name of the service.
     * @return ServiceInterface The service prototype.
     */
    public function getServicePrototype(string $name): ServiceInterface
    {
        if (!isset($this->serviceCache[$name])) {
            // For prototypes, the parent is the static services class itself.
            $this->serviceCache[$name] = ServiceFactory::createServicePrototype($name, $this);
        }
        return $this->serviceCache[$name];
    }

    /**
     * Check if the debugger is active
     */
    public function isDebuggerActive(): bool
    {
        return xarCore::isDebuggerActive();
    }
}
