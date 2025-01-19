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
use xarLog;
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
    public function ctl(): ControllerService;
    public function log(): LoggerService;
    public function mls(): MultiLanguageService;
    public function mod(): ModulesService;
    public function sec(): SecurityService;
    public function tpl(): TemplatingService;
    public function var(): VariablesService;
    public function block(): BlocksService;
    public function data(): DataObjectService;
    public function prop(): DataPropertyService;
    public function cache(): CachingService;
    /**
     * Call exit() - override for non-blocking servers, php unit tests or elsewhere
     * @return void|never
     */
    public function exit(int|string $status = 0);
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
    /** @var ?BlocksService<TParent> */
    protected $xarBlock;
    /** @var ?DataObjectService<TParent> */
    protected $xarData;
    /** @var ?DataPropertyService<TParent> */
    protected $xarProp;
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
     * Access xarController::* Main Controller methods (URL, redirect, ...)
     *
     * Available methods:
     * - URL() - or use mod()->getURL() for current module
     * - getObjectURL() - or use data()->getURL() for current object
     * - redirect()
     * - forbidden()
     * - notFound()
     * - badRequest()
     * - ...
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
     * - getURL() for current module - or use ctl()->URL() with modName
     * - getRegId()
     * - getInfo()
     * - getTables()
     * - ...
     *
     * Required methods in parent:
     * - getModName()
     *
     * Optional methods in parent:
     * - getModType() for mod()->apiFunc(null, null, ...) - only for migration
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
     * - setPageTemplateName()
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
     * @return VariablesService<TParent>
     */
    public function var(): VariablesService
    {
        $this->xarVar ??= $this->getVariablesService();
        return $this->xarVar;
    }

    /**
     * Access xarBlock*::* Blocks methods (template, ...)
     *
     * Available methods:
     * - template() for current block type - or use tpl()->block() in general with modName blockType
     * - prepare()
     * - ...
     *
     * Required methods in parent:
     * - getModName()
     * - getBlockType() for block()->template()
     *
     * @return BlocksService<TParent>
     */
    public function block(): BlocksService
    {
    $this->xarBlock ??= $this->getBlocksService();
    return $this->xarBlock;
    }

    /**
     * Access DataObjectFactory::* methods with context (getObject, getObjectList, ...)
     *
     * Available methods:
     * - getURL() for current object - or use ctl()->getObjectURL() in general with objectName
     * - getObject()
     * - getObjectList()
     * - getObjectInfo()
     * - getObjectID()
     * - getObjectDescriptor()
     * - getPropertyTypes()
     * - ...
     *
     * Required methods in parent:
     * - getObjectName() for data()->getURL()
     *
     * @return DataObjectService<TParent>
     */
    public function data(): DataObjectService
    {
        $this->xarData ??= $this->getDataObjectService();
        return $this->xarData;
    }

    /**
     * Access DataProperty*::* methods with context (getProperty, template, ...)
     *
     * Available methods:
     * - template() for current property - or use tpl()->property() in general with modName propertyName
     * - getPropertyTypes()
     * - getProperty()
     * - ...
     *
     * Required methods in parent:
     * - getPropertyName() for prop()->template()
     *
     * @return DataPropertyService<TParent>
     */
    public function prop(): DataPropertyService
    {
        $this->xarProp ??= $this->getDataPropertyService();
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
     * Translate string with optional arguments
     * = short-hand version for $this->mls()->translate()
     * @param string $rawstring
     * @param mixed ...$args
     */
    public function ml($rawstring, ...$args): string
    {
        return $this->mls()->translate($rawstring, ...$args);
    }

    /**
     * Summary of getControllerService
     * @return ControllerService<TParent>
     */
    protected function getControllerService(): ControllerService
    {
        xarLog::message(__METHOD__ . ': starting service', xarLog::LEVEL_DEBUG);
        return new ControllerService($this);
    }

    /**
     * Summary of getLoggerService
     * @return LoggerService<TParent>
     */
    protected function getLoggerService(): LoggerService
    {
        xarLog::message(__METHOD__ . ': starting service', xarLog::LEVEL_DEBUG);
        return new LoggerService($this);
    }

    /**
     * Summary of getMultiLanguageService
     * @return MultiLanguageService<TParent>
     */
    protected function getMultiLanguageService(): MultiLanguageService
    {
        xarLog::message(__METHOD__ . ': starting service', xarLog::LEVEL_DEBUG);
        return new MultiLanguageService($this);
    }

    /**
     * Summary of getModulesService
     * @return ModulesService<TParent>
     */
    protected function getModulesService(): ModulesService
    {
        xarLog::message(__METHOD__ . ': starting service', xarLog::LEVEL_DEBUG);
        return new ModulesService($this);
    }

    /**
     * Summary of getSecurityService
     * @return SecurityService<TParent>
     */
    protected function getSecurityService(): SecurityService
    {
        xarLog::message(__METHOD__ . ': starting service', xarLog::LEVEL_DEBUG);
        return new SecurityService($this);
    }

    /**
     * Summary of getTemplatingService
     * @return TemplatingService<TParent>
     */
    protected function getTemplatingService(): TemplatingService
    {
        xarLog::message(__METHOD__ . ': starting service', xarLog::LEVEL_DEBUG);
        return new TemplatingService($this);
    }

    /**
     * Summary of getVariablesService
     * @return VariablesService<TParent>
     */
    protected function getVariablesService(): VariablesService
    {
        xarLog::message(__METHOD__ . ': starting service', xarLog::LEVEL_DEBUG);
        return new VariablesService($this);
        //return ServicesContainer::getInstance(VariablesService::class, $this);
    }

    /**
     * Summary of getBlocksService
     * @return BlocksService<TParent>
     */
    protected function getBlocksService(): BlocksService
    {
        xarLog::message(__METHOD__ . ': starting service', xarLog::LEVEL_DEBUG);
        return new BlocksService($this);
    }

    /**
     * Summary of getDataObjectService
     * @return DataObjectService<TParent>
     */
    protected function getDataObjectService(): DataObjectService
    {
        xarLog::message(__METHOD__ . ': starting service', xarLog::LEVEL_DEBUG);
        return new DataObjectService($this);
    }

    /**
     * Summary of getDataPropertyService
     * @return DataPropertyService<TParent>
     */
    protected function getDataPropertyService(): DataPropertyService
    {
        xarLog::message(__METHOD__ . ': starting service', xarLog::LEVEL_DEBUG);
        return new DataPropertyService($this);
    }

    /**
     * Summary of getCachingService
     * @return CachingService<TParent>
     */
    protected function getCachingService(): CachingService
    {
        xarLog::message(__METHOD__ . ': starting service', xarLog::LEVEL_DEBUG);
        return new CachingService($this);
        //return CachingService::getInstance($this);
    }

    /**
     * Summary of getExitService
     * @return callable
     */
    protected function getExitService(): callable
    {
        xarLog::message(__METHOD__ . ': starting service', xarLog::LEVEL_DEBUG);
        return function (int|string $status = 0) {
            \xarCore::exit($status);
        };
    }
}
