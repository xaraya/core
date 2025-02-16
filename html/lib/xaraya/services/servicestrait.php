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

use DataObjectList;
use DataObject;
use DataProperty;
use sys;

sys::import('xaraya.services.coreservicestrait');
sys::import('xaraya.objects');

/**
 * For documentation purposes only - available via ServicesTrait
 */
interface ServicesInterface extends CoreServicesInterface
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
     * Get block type from here - override if needed
     */
    public function getBlockType(): string;

    /**
     * Get data object or objectlist - override if needed
     */
    public function getObject(): DataObjectList|DataObject|null;

    /**
     * @todo Get data property from here - override if needed
     */
    public function getProperty(): DataProperty|null;
}

/**
 * Services trait for classes - override methods here if needed
 *
 * This defines where to get modName, itemType, modType and object
 * from the parent class for use by the core service classes
 *
 * Available services:
 * - $this->ctl() = xarController::* Main Controller (getURL, redirect, ...)
 * - $this->log() = xarLog::* Logger (message, variable, ...)
 * - $this->mls() = xarMLS::* Multi-Language System (translate, ...)
 * - $this->mod() = xarMod*::* Modules (getVar, setVar, ...)
 * - $this->sec() = xarSec::* Security (checkAccess, genAuthKey, ...)
 * - $this->tpl() = xarTpl::* Templating (module, setPageTitle, ...)
 * - $this->var() = xarVar::* Variables (fetch, check, ...)
 * - $this->block() = xarBlock*::* Blocks (template, ...)
 * - $this->data() = DataObjectFactory::* with context (getObject, getObjectList, ...)
 * - $this->prop() = DataProperty*::* with context (getProperty, template, ...)
 * - $this->cache() = xar*Cache::* Caching (getModuleKey, getObjectKey, ...)
 * - $this->config() = xarConfigVars::* Config (getVar, setVar, ...)
 * - $this->session() = xarSession::* Session (getVar, setVar, ...)
 * - $this->db() = xarDB::* Database (getConn, getPrefix, ...)
 * - ...
 * - $this->ml($rawstring, ...$args) = short-hand version for $this->mls()->translate()
 * - $this->exit($status = 0) = call exit() - override for non-blocking servers, php unit tests or elsewhere
 *
 */
trait ServicesTrait
{
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
     * @todo Get block type from here - override if needed
     */
    public function getBlockType(): string
    {
        return 'TODO';
    }

    /**
     * Get data object or objectlist from here - override if needed
     */
    public function getObject(): DataObjectList|DataObject|null
    {
        return $this->object;
    }

    /**
     * @todo Get data property from here - override if needed
     */
    public function getProperty(): DataProperty|null
    {
        return $this->property;
    }
}

/**
 * Example class offering core services
 */
class ServicesClass implements ServicesInterface
{
    use ServicesTrait;

    public string $moduleName;
    public string $moduleType;
    public int $itemtype = 0;
    /** @var DataObject|DataObjectList */
    public $object;
    /** @var DataProperty */
    public $property;
}
