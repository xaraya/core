<?php

/**
 * Core Services for classes (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\Services;

use Xaraya\Context\ContextInterface;
use Xaraya\Context\ContextTrait;
use DataObjectList;
use DataObject;
use sys;

sys::import('xaraya.context.contexttrait');
sys::import('xaraya.services.controllertrait');
sys::import('xaraya.services.multilanguagetrait');
sys::import('xaraya.services.templatingtrait');
sys::import('xaraya.services.variablestrait');
sys::import('xaraya.services.dataobjecttrait');
sys::import('xaraya.objects');

interface ServicesInterface extends ContextInterface
{
    /**
     * Get name of the module from here - override if needed
     */
    public function getModName(): string;

    /**
     * Get item type from here - override if needed
     */
    public function getItemType(): int;

    /**
     * Get module type (user, admin, ...) from here - override if needed
     */
    public function getModType(): string;

    /**
     * Get data object or objectlist - override if needed
     */
    public function getObject(): DataObjectList|DataObject|null;

    /**
     * Provide access to core methods via service classes
     */
    public function setCoreServices(): void;
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

    /**
     * Set core services for access via methods
     *
     * Available services:
     * - xCtl(): xarController::* Main Controller (getURL, redirect, ...)
     * - xMls(): xarMLS::* Multi-Language System (translate, ...)
     * - xSec(); xarSec::* Security (checkAccess, genAuthKey, ...)
     * - xTpl(): xarTpl::* Templating (module, setPageTitle, ...)
     * - xVar(): xarVar::* Variables (fetch, get, ...)
     * - xData(): DataObjectFactory::* with context (getObject, getObjectList, ...)
     * - ...
     *
     * @return void
     */
    public function setCoreServices(): void
    {
        // ...
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
     * - getModName() for xCtl()->getURL()
     * - getObject() for xCtl()->getObjectUrl()
     *
     * @return ControllerService<TParent>
     */
    public function xCtl(): ControllerService
    {
        $this->xarCtl ??= $this->getControllerService();
        return $this->xarCtl;
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
    public function xMls(): MultiLanguageService
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
     * - ...
     *
     * Required methods in parent:
     * - getModName()
     * - getItemType() for xMod()->module()
     * - getModType() for xMod()->module()
     *
     * @return ModulesService<TParent>
     */
    public function xMod(): ModulesService
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
    public function xSec(): SecurityService
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
     * - getModType() for xTpl()->module()
     * - getObject() for xTpl()->object()
     *
     * @return TemplatingService<TParent>
     */
    public function xTpl(): TemplatingService
    {
        $this->xarTpl ??= $this->getTemplatingService();
        return $this->xarTpl;
    }

    /**
     * Access xarVar::* Variables methods (fetch, get, prep, ...)
     *
     * Available methods:
     * - fetch()
     * - get()
     * - prep()
     * - ...
     *
     * @return VariablesService<TParent>
     */
    public function xVar(): VariablesService
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
    public function xData(): DataObjectService
    {
        $this->xarData ??= $this->getDataObjectService();
        return $this->xarData;
    }

    /**
     * Summary of getControllerService
     * @return ControllerService<TParent>
     */
    public function getControllerService(): ControllerService
    {
        return new ControllerService($this);
    }

    /**
     * Summary of getMultiLanguageService
     * @return MultiLanguageService<TParent>
     */
    public function getMultiLanguageService(): MultiLanguageService
    {
        return new MultiLanguageService($this);
    }

    /**
     * Summary of getModulesService
     * @return ModulesService<TParent>
     */
    public function getModulesService(): ModulesService
    {
        return new ModulesService($this);
    }

    /**
     * Summary of getSecurityService
     * @return SecurityService<TParent>
     */
    public function getSecurityService(): SecurityService
    {
        return new SecurityService($this);
    }

    /**
     * Summary of getTemplatingService
     * @return TemplatingService<TParent>
     */
    public function getTemplatingService(): TemplatingService
    {
        return new TemplatingService($this);
    }

    /**
     * Summary of getVariablesService
     * @return VariablesService<TParent>
     */
    public function getVariablesService(): VariablesService
    {
        return new VariablesService($this);
    }

    /**
     * Summary of getDataObjectService
     * @return DataObjectService<TParent>
     */
    public function getDataObjectService(): DataObjectService
    {
        return new DataObjectService($this);
    }
}

/**
 * Services trait for classes - override methods here if needed
 *
 * This defines where to get modName, itemType, modType and object
 * from the parent class for use by the core service classes
 *
 * @template TParent of ServicesInterface
 */
trait ServicesTrait
{
    /** @use CoreServicesTrait<$this> */
    use CoreServicesTrait;

    /**
     * Get name of the module from here - override if needed
     */
    public function getModName(): string
    {
        return $this->moduleName;
    }

    /**
     * Get item type from here - override if needed
     */
    public function getItemType(): int
    {
        return $this->itemtype;
    }

    /**
     * Get module type (user, admin, ...) from here - override if needed
     */
    public function getModType(): string
    {
        return $this->moduleType;
    }

    /**
     * Get data object or objectlist from here - override if needed
     */
    public function getObject(): DataObjectList|DataObject|null
    {
        return $this->object;
    }

    /**
     * Set core services for access via methods
     *
     * Available services:
     * - xCtl(): xarController::* Main Controller (getURL, redirect, ...)
     * - xMls(): xarMLS::* Multi-Language System (translate, ...)
     * - xSec(); xarSec::* Security (checkAccess, genAuthKey, ...)
     * - xTpl(): xarTpl::* Templating (module, setPageTitle, ...)
     * - xVar(): xarVar::* Variables (fetch, get, ...)
     * - xData(): DataObjectFactory::* with context (getObject, getObjectList, ...)
     * - ...
     *
     * @return void
     */
    public function setCoreServices(): void
    {
        // ...
    }
}

/**
 * Summary of ServicesClass
 */
class ServicesClass implements ServicesInterface
{
    /** @use ServicesTrait<$this> */
    use ServicesTrait;

    public string $moduleName;
    public string $moduleType;
    public int $itemtype = 0;
    /** @var DataObject|DataObjectList */
    public $object;
}
