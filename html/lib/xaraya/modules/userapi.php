<?php

/**
 * Handle module user api functions
 *
 * Usage:
 * ```
 * # userapi.php
 * namespace Xaraya\Modules\MyFancyModule;
 *
 * use Xaraya\Modules\UserApiClass;
 *
 * /**
 *  * Handle module user api functions
 *  * @extends UserApiClass<Module>
 *  *\/
 * class UserApi extends UserApiClass
 * {
 *     public function get($args = []) {
 *         // get single module item
 *         // $context = $this->getContext();
 *         return $data;
 *     }
 * }
 * ```
 *
 * @package core\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Modules;

use sys;

sys::import('xaraya.modules.userapitrait');

/**
 * Handle module user api functions
 *
 * User api methods:
 * - ... all other module user api functions implemented here or as method classes
 *
 * Available methods:
 * - configure() Provide additional api/gui configuration when created
 * - getModName() Get name for this module in module class or method
 * - getModType() Get module type of this module class (user, admin, ...)
 * - getItemType() Get item type in this module class
 * - setItemType($itemtype = 0) Set item type in this module class
 * - userapi() Get module user API class for this module
 * - usergui) Get module user GUI class for this module
 * - adminapi() Get module admin API class for this module
 * - admingui() Get module admin GUI class for this module
 * - getModule() Access other api/gui module classes from this instance
 * - hasMethod($funcName, $callType = 'api') Does this module class implement this method
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
 * - $this->service($name, ...$args) = get core service by name
 * - $this->ml($rawstring, ...$args) = short-hand version for $this->mls()->translate()
 * - $this->exit($status = 0) = call exit() - override for non-blocking servers, php unit tests or elsewhere
 *
 * @template TModule of ModuleInterface|null
 */
class UserApiClass implements UserApiInterface
{
    /** @use UserApiTrait<TModule> */
    use UserApiTrait;
}
