<?php

/**
 * Handle single module function as method
 *
 * Usage:
 * ```
 * # userapi/get.php
 * namespace Xaraya\Modules\MyFancyModule\UserApi;
 *
 * use Xaraya\Modules\MethodClass;
 *
 * class GetMethod extends MethodClass
 * {
 *     public function __invoke(array $args = [])
 *     {
 *         // ...
 *         // $context = $this->getContext();
 *         return $data;
 *     }
 * }
 * ```
 *
 * @package core\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.8.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Modules;

use Xaraya\Services\ParentServicesInterface;
use Xaraya\Services\ParentServicesTrait;

/**
 * For documentation purposes only - available via MethodClass
 * @template TComponent of ModuleClassInterface
 */
interface MethodClassInterface extends ParentServicesInterface
{
    /**
     * Summary of __invoke
     * @param array<mixed> $args
     * @return mixed
     */
    public function __invoke(array $args = []);
    /** @param TComponent|null $parent */
    public function __construct(string $modName, int $itemtype = 0, ?ModuleClassInterface $parent = null);
    public function configure(): void;
    /** @return TComponent */
    public function getParent(): ModuleClassInterface;
    /** @param TComponent $parent */
    public function setParent(ModuleClassInterface $parent): void;
    public function getModule(?string $modName = null): ?ModuleInterface;
    public function userapi(): ?UserApiInterface;
    public function usergui(): ?UserGuiInterface;
    public function adminapi(): ?AdminApiInterface;
    public function admingui(): ?AdminGuiInterface;
    public function getModName(): string;
    public function setModName(string $modName): void;
    public function getItemType(): int;
    public function setItemType(int $itemtype = 0): void;
}

/**
 * Handle single module function as method from api/gui module class
 *
 * The instance will be created by the api/gui module class
 * and configured with the right module, itemtype and parent
 *
 * @template TComponent of ModuleClassInterface
 */
trait MethodClassTrait
{
    use ParentServicesTrait;

    /**
     * Invoke this module function and return result
     * @param array<mixed> $args
     * @return mixed
     */
    public function __invoke(array $args = [])
    {
        return $args;
    }

    /**
     * Create method class instance with modName, itemtype and parent
     * @param TComponent|null $parent
     */
    public function __construct(string $modName, int $itemtype = 0, ?ModuleClassInterface $parent = null)
    {
        // make parent mandatory to comply with parent requirement of services
        assert($parent instanceof ModuleClassInterface);
        $this->setModName($modName);
        // pass along itemtype from module class - @todo is this useful/relevant?
        $this->setItemType($itemtype);
        $this->setParent($parent);
        $this->configure();
    }

    /**
     * Configure method class if needed - called in constructor
     */
    public function configure(): void
    {
        // ...
    }

    /**
     * Get parent module class for core services
     * @return TComponent
     */
    public function getParent(): ModuleClassInterface
    {
        return $this->parent;
    }

    /**
     * Set parent module class for core services
     * @param TComponent $parent
     */
    public function setParent(ModuleClassInterface $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * Get parent module to access other module classes
     */
    public function getModule(?string $modName = null): ?ModuleInterface
    {
        return $this->getParent()->getModule($modName);
    }

    /**
     * Get module user API class for this module
     */
    public function userapi(): ?UserApiInterface
    {
        return $this->getParent()->userapi();
    }

    /**
     * Get module user GUI class for this module
     */
    public function usergui(): ?UserGuiInterface
    {
        return $this->getParent()->usergui();
    }

    /**
     * Get module admin API class for this module
     */
    public function adminapi(): ?AdminApiInterface
    {
        return $this->getParent()->adminapi();
    }

    /**
     * Get module admin GUI class for this module
     */
    public function admingui(): ?AdminGuiInterface
    {
        return $this->getParent()->admingui();
    }

    /**
     * Get name for this module in module class or method
     */
    public function getModName(): string
    {
        return $this->moduleName;
    }

    /**
     * Set name for this module in module class or method
     */
    public function setModName(string $modName): void
    {
        $this->moduleName = $modName;
    }

    /**
     * Get item type in module class or method
     */
    public function getItemType(): int
    {
        return $this->itemtype;
    }

    /**
     * Set item type in module class or method
     */
    public function setItemType(int $itemtype = 0): void
    {
        $this->itemtype = $itemtype;
    }

    /**
     * Dummy method for ModuleClassInterface extends ServicesInterface
     */
    public function getObject(): null
    {
        return null;
    }

    /**
     * Dummy method for ModuleClassInterface extends ServicesInterface
     */
    public function getProperty(): null
    {
        return null;
    }
}

/**
 * Handle single module function as method from api/gui module class
 *
 * The instance will be created by the api/gui module class
 * and configured with the right module, itemtype and parent
 *
 * Available methods:
 * - __invoke(array $args = []) This contains the actual method code
 * - configure() Provide additional method configuration when created
 * - userapi() Get module user API class for this module
 * - usergui) Get module user GUI class for this module
 * - adminapi() Get module admin API class for this module
 * - admingui() Get module admin GUI class for this module
 * - getParent() Get parent api/gui module class to call other methods
 *   or access other api/gui module classes from this instance
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
 * - $this->sysConfig() = xarSystemVars::* System (getVar, setVar, ...)
 * - $this->session() = xarSession::* Session (getVar, setVar, ...)
 * - $this->db() = xarDB::* Database (getConn, getPrefix, ...)
 * - ...
 * - $this->service($name, ...$args) = get core service by name
 * - $this->ml($rawstring, ...$args) = short-hand version for $this->mls()->translate()
 * - $this->exit($status = 0) = call exit() - override for non-blocking servers, php unit tests or elsewhere
 *
 * @template TComponent of ModuleClassInterface
 * @implements MethodClassInterface<TComponent>
 */
class MethodClass implements MethodClassInterface
{
    /** @use MethodClassTrait<TComponent> */
    use MethodClassTrait;

    protected string $moduleName;          // set in constructor by MethodsTrait::__call()
    protected int $itemtype = 0;
    /** @var TComponent */
    protected ModuleClassInterface $parent;

    // ...
}
