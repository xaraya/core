<?php

/**
 * Get core services from parent class (WIP)
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

use sys;

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
 * - $this->log() = xarLog::* Logger (message, variable, ...)
 * - $this->mls() = xarMLS::* Multi-Language System (translate, ...)
 * - $this->mod() = xarMod*::* Modules (getVar, setVar, ...)
 * - $this->sec() = xarSec::* Security (checkAccess, genAuthKey, ...)
 * - $this->tpl() = xarTpl::* Templating (module, setPageTitle, ...)
 * - $this->var() = xarVar::* Variables (fetch, check, ...)
 * - $this->data() = DataObjectFactory::* with context (getObject, getObjectList, ...)
 * - $this->cache() = xar*Cache::* Caching (getModuleKey, getObjectKey, ...)
 * - ...
 * - $this->exit($status = 0) = call exit() - override for non-blocking servers, php unit tests or elsewhere
 *
 * @template TParent of ServicesInterface
 */
trait ParentServicesTrait
{
    /**
     * Set core services for access via methods
     * @param array<string, mixed> $args array of name => service to replace default ones
     */
    public function setCoreServices(array $args = []): void
    {
        $this->getParent()->setCoreServices($args);
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
        return $this->getParent()->ctl();
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
        return $this->getParent()->log();
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
        return $this->getParent()->mls();
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
        return $this->getParent()->mod();
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
        return $this->getParent()->sec();
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
        return $this->getParent()->tpl();
    }

    /**
     * Access xarVar::* Variables methods (fetch, check, prep, ...)
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
        return $this->getParent()->var();
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
        return $this->getParent()->data();
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
        return $this->getParent()->cache();
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
     * @return TParent
     */
    public function getParent(): ServicesInterface
    {
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
 * e.g. method -> module or property -> object
 *
 * @template TParent of ServicesInterface
 */
class ChildServicesClass
{
    /** @use ParentServicesTrait<TParent> */
    use ParentServicesTrait;

    /** @var TParent */
    protected ServicesInterface $parent;
}
