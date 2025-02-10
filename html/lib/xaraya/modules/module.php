<?php

/**
 * Handle module classes via xarMod::getModule()
 *
 * Usage:
 * ```
 * # class/module.php
 * namespace Xaraya\Modules\MyFancyModule;
 *
 * use Xaraya\Modules\ModuleClass;
 * use sys;
 *
 * sys::import('xaraya.modules.module');
 *
 * class Module extends ModuleClass
 * {
 *     public function setClassTypes(): void
 *     {
 *          parent::setClassTypes();
 *          // add import class types for this module
 *          $this->classtypes['import'] = 'Import';
 *          $this->classtypes['importapi'] = 'ImportApi';
 *     }
 * }
 *
 * # xaruser/main.php or xaruser.php (migration)
 * function myfancymodule_user_main($args = [], $context = null) {
 *     // get module class instance first
 *     $module = xarMod::getModule('myfancymodule');
 *     $module->setContext($context);
 *     return $module->usergui()->main($args);
 *     // or get module gui directly
 *     // see usergui.php for an example
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

sys::import('xaraya.modules.moduletrait');

/**
 * Handle module classes via xarMod::getModule()
 *
 * Module methods:
 * - ... all other module functions implemented here
 * - getName() Get module name
 * - getInfo() Get info from xarversion.php
 * - getTables() Get tables from xartables.php
 *
 * Available methods:
 * - configure() Configure module class types - override if needed
 * - userapi() Get module class for user api functions
 * - usergui() Get module class for user gui functions
 * - adminapi() Get module class for admin api functions
 * - admingui() Get module class for admin gui functions
 * - installer() Get module class for installer functions
 * - setClassTypes() Set module class type for all supported modTypes (user, userapi, admin, ...)
 * - getClassType($modType) Is there a module class type for this modType
 * - getCallableMethod($modType, $funcName, $callType = 'api') Get callable method for this modType & funcName
 *
 */
class ModuleClass implements ModuleInterface
{
    use ModuleTrait;
}
