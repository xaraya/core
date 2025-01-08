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
 *     return $module->getGUI()->main($args);
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
 */
class ModuleClass implements ModuleInterface
{
    use ModuleTrait;
}
