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
    public function ctl(): ControllerInterface;
    public function log(): LoggerInterface;
    public function mls(): MultiLanguageInterface;
    public function mod(): ModulesInterface;
    public function sec(): SecurityInterface;
    public function tpl(): TemplatingInterface;
    public function var(): VariablesInterface;
    public function block(): BlocksInterface;
    public function data(): DataObjectInterface;
    public function prop(): DataPropertyInterface;
    public function cache(): CachingInterface;
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
 */
trait CoreServicesTrait
{
    use ContextTrait;

    protected ?ControllerInterface $xarCtl = null;
    protected ?LoggerInterface $xarLog = null;
    protected ?MultiLanguageInterface $xarMls = null;
    protected ?ModulesInterface $xarMod = null;
    protected ?SecurityInterface $xarSec = null;
    protected ?TemplatingInterface $xarTpl = null;
    protected ?VariablesInterface $xarVar = null;
    protected ?BlocksInterface $xarBlock = null;
    protected ?DataObjectInterface $xarData = null;
    protected ?DataPropertyInterface $xarProp = null;
    protected ?CachingInterface $xarCache = null;
    /** @var ?callable */
    protected $xarExit = null;

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
     * - translate()
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
     */
    public function mod(): ModulesInterface
    {
        $this->xarMod ??= ServiceFactory::getModulesService($this);
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
     * - template() for current block type - or use tpl()->block() in general with modName blockType
     * - prepare()
     * - ...
     *
     * Required methods in parent:
     * - getModName()
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
     * - template() for current property - or use tpl()->property() in general with modName propertyName
     * - getPropertyTypes()
     * - getProperty()
     * - ...
     *
     * Required methods in parent:
     * - getPropertyName() for prop()->template()
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
     * - getObjectKey()
     * - hasObject()
     * - getObject()
     * - setObject()
     * - ...
     *
     * Required methods in parent:
     * - getObject() for cache()->getObjecKey(null, '...')
     *
     */
    public function cache(): CachingInterface
    {
        $this->xarCache ??= ServiceFactory::getCachingService($this);
        return $this->xarCache;
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
