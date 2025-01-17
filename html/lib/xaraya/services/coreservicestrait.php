<?php

/**
 * Core Services for classes (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
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
sys::import('xaraya.services.controllertrait');
sys::import('xaraya.services.loggertrait');
sys::import('xaraya.services.multilanguagetrait');
sys::import('xaraya.services.modulestrait');
sys::import('xaraya.services.securitytrait');
sys::import('xaraya.services.templatingtrait');
sys::import('xaraya.services.variablestrait');
sys::import('xaraya.services.dataobjecttrait');
sys::import('xaraya.services.cachingtrait');
sys::import('xaraya.objects');

/**
 * For documentation purposes only - available via CoreServicesTrait
 */
interface CoreServicesInterface extends ContextInterface
{
    /** @param array<string, mixed> $args */
    public function setCoreServices(array $args = []): void;
    public function ctl(): ControllerService;
    public function log(): LoggerService;
    public function mls(): MultiLanguageService;
    public function mod(): ModulesService;
    public function sec(): SecurityService;
    public function tpl(): TemplatingService;
    public function var(): VariablesService;
    public function cache(): CachingService;
    public function data(): DataObjectService;
    /**
     * Call exit() - override for non-blocking servers, php unit tests or elsewhere
     * @return void|never
     */
    public function exit(int|string $status = 0);
}

/**
 * Core Services trait for classes
 *
 * This defines the core services available to the parent class
 *
 * @template TParent of ServicesInterface
 */
trait CoreServicesTrait
{
    use ContextTrait;

    /** @var ?ControllerService<TParent> */
    protected $xarCtl;
    /** @var ?LoggerService<TParent> */
    protected $xarLog;
    /** @var ?MultiLanguageService<TParent> */
    protected $xarMls;
    /** @var ?ModulesService<TParent> */
    protected $xarMod;
    /** @var ?SecurityService<TParent> */
    protected $xarSec;
    /** @var ?TemplatingService<TParent> */
    protected $xarTpl;
    /** @var ?VariablesService<TParent> */
    protected $xarVar;
    /** @var ?DataObjectService<TParent> */
    protected $xarData;
    /** @var ?CachingService<TParent> */
    protected $xarCache;
    /** @var ?callable */
    protected $xarExit;

    /**
     * Set core services for access via methods
     * @param array<string, mixed> $args array of name => service to replace default ones
     */
    public function setCoreServices(array $args = []): void
    {
        $supported = ['ctl', 'log', 'mls', 'mod', 'sec', 'tpl', 'var', 'data', 'cache', 'exit'];
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
     * Access xarController::* Main Controller methods (getURL, redirect, ...)
     *
     * Available methods:
     * - getURL()
     * - redirect()
     * - forbidden()
     * - notFound()
     * - badRequest()
     * - getObjectURL()
     * - ...
     *
     * Required methods in parent:
     * - getModName() for ctl()->getURL()
     * - getObject() for ctl()->getObjectUrl()
     *
     * @return ControllerService<TParent>
     */
    public function ctl(): ControllerService
    {
        $this->xarCtl ??= $this->getControllerService();
        return $this->xarCtl;
    }

    /**
     * Access xarLog::* Logger methods (message, variable, ...)
     *
     * Available methods:
     * - message()
     * - variable()
     * - emergency()
     * - alert()
     * - critical()
     * - error()
     * - warning()
     * - notice()
     * - info()
     * - debug()
     * - log()
     *
     * @return LoggerService<TParent>
     */
    public function log(): LoggerService
    {
        $this->xarLog ??= $this->getLoggerService();
        return $this->xarLog;
    }

    /**
     * Access xarMLS::* Multi-Language System methods (translate, ...)
     *
     * Available methods:
     * - translate()
     * - ...
     *
     * @return MultiLanguageService<TParent>
     */
    public function mls(): MultiLanguageService
    {
        $this->xarMls ??= $this->getMultiLanguageService();
        return $this->xarMls;
    }

    /**
     * Access xarMod*::* Modules methods (getVar, setVar, ...)
     *
     * Available methods:
     * - getVar()
     * - setVar()
     * - getRegId()
     * - getInfo()
     * - getTables()
     * - ...
     *
     * Required methods in parent:
     * - getModName()
     * - getItemType() for mod()->module()
     * - getModType() for mod()->module()
     *
     * @return ModulesService<TParent>
     */
    public function mod(): ModulesService
    {
        $this->xarMod ??= $this->getModulesService();
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
     * @return SecurityService<TParent>
     */
    public function sec(): SecurityService
    {
        $this->xarSec ??= $this->getSecurityService();
        return $this->xarSec;
    }

    /**
     * Access xarTpl::* Templating methods (module, setPageTitle, ...)
     *
     * Available methods:
     * - module()
     * - object()
     * - setPageTitle()
     * - ...
     *
     * Required methods in parent:
     * - getModName()
     * - getModType() for tpl()->module()
     * - getObject() for tpl()->object()
     *
     * @return TemplatingService<TParent>
     */
    public function tpl(): TemplatingService
    {
        $this->xarTpl ??= $this->getTemplatingService();
        return $this->xarTpl;
    }

    /**
     * Access xarVar::* Variables methods (fetch, get, prep, ...)
     *
     * Available methods:
     * - fetch()
     * - check()
     * - find()
     * - update()
     * - validate()
     * - prep()
     * - prepHTML()
     * - ...
     *
     * @return VariablesService<TParent>
     */
    public function var(): VariablesService
    {
        $this->xarVar ??= $this->getVariablesService();
        return $this->xarVar;
    }

    /**
     * Access DataObjectFactory::* methods with context (getObject, getObjectList, ...)
     *
     * Available methods:
     * - getObject()
     * - getObjectList()
     * - getObjectInfo()
     * - getObjectID()
     * - getObjectDescriptor()
     * - getPropertyTypes()
     * - ...
     *
     * @return DataObjectService<TParent>
     */
    public function data(): DataObjectService
    {
        $this->xarData ??= $this->getDataObjectService();
        return $this->xarData;
    }

    /**
     * Access xar*Cache::* Caching methods (getModuleKey, getObjectKey, ...)
     *
     * Available methods:
     * - getObjectKey()
     * - hasObject()
     * - getObject()
     * - setObject()
     * - ...
     *
     * Required methods in parent:
     * - getObject() for cache()->getObjecKey(null, '...')
     *
     * @return CachingService<TParent>
     */
    public function cache(): CachingService
    {
        $this->xarCache ??= $this->getCachingService();
        return $this->xarCache;
    }

    /**
     * Call exit() - override for non-blocking servers, php unit tests or elsewhere
     * @return void|never
     */
    public function exit(int|string $status = 0)
    {
        $this->xarExit ??= $this->getExitService();
        // call exit service :-)
        call_user_func($this->xarExit, $status);
    }

    /**
     * Summary of getControllerService
     * @return ControllerService<TParent>
     */
    protected function getControllerService(): ControllerService
    {
        return new ControllerService($this);
    }

    /**
     * Summary of getLoggerService
     * @return LoggerService<TParent>
     */
    protected function getLoggerService(): LoggerService
    {
        return new LoggerService($this);
    }

    /**
     * Summary of getMultiLanguageService
     * @return MultiLanguageService<TParent>
     */
    protected function getMultiLanguageService(): MultiLanguageService
    {
        return new MultiLanguageService($this);
    }

    /**
     * Summary of getModulesService
     * @return ModulesService<TParent>
     */
    protected function getModulesService(): ModulesService
    {
        return new ModulesService($this);
    }

    /**
     * Summary of getSecurityService
     * @return SecurityService<TParent>
     */
    protected function getSecurityService(): SecurityService
    {
        return new SecurityService($this);
    }

    /**
     * Summary of getTemplatingService
     * @return TemplatingService<TParent>
     */
    protected function getTemplatingService(): TemplatingService
    {
        return new TemplatingService($this);
    }

    /**
     * Summary of getVariablesService
     * @return VariablesService<TParent>
     */
    protected function getVariablesService(): VariablesService
    {
        return new VariablesService($this);
    }

    /**
     * Summary of getDataObjectService
     * @return DataObjectService<TParent>
     */
    protected function getDataObjectService(): DataObjectService
    {
        return new DataObjectService($this);
    }

    /**
     * Summary of getCachingService
     * @return CachingService<TParent>
     */
    protected function getCachingService(): CachingService
    {
        return new CachingService($this);
    }

    /**
     * Summary of getExitService
     * @return callable
     */
    protected function getExitService(): callable
    {
        return function (int|string $status = 0): never {
            exit($status);
        };
    }
}
