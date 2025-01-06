<?php

/**
 * Handle module classes via xarMod::getModule()
 *
 * Usage:
 * ```
 * // class/module.php
 * namespace Xaraya\Modules\MyFancyModule;
 *
 * use Xaraya\Modules\ModuleInterface;
 * use Xaraya\Modules\ModuleTrait;
 *
 * class Module implements ModuleInterface
 * {
 *     use ModuleTrait;
 * }
 *
 * // xaruser/main.php or xaruser.php
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
 * @version 2.5.4
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
 * Summary of DefaultModule
 */
class DefaultModule implements ModuleInterface
{
    use ModuleTrait;

    /**
     * @see \xarMod::privateLoad()
     */
    public function getClassType(string $modType): string|null
    {
        // no class types available here
        return null;
    }

    /**
     * @see \xarMod::getModuleClassMethod()
     */
    public function getCallableMethod(string $modType, string $funcName): callable|null
    {
        // no callable methods available here
        return null;
    }
}
