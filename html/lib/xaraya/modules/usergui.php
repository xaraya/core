<?php

/**
 * Handle module user gui functions
 *
 * Usage:
 * ```
 * // class/usergui.php
 * namespace Xaraya\Modules\MyFancyModule;
 *
 * use Xaraya\Modules\UserGuiInterface;
 * use Xaraya\Modules\UserGuiTrait;
 *
 * class UserGui implements UserGuiInterface
 * {
 *     use UserGuiTrait;
 *
 *     public function main($args = []) {
 *         // get main module overview
 *         return $args;
 *     }
 * }
 *
 * // xaruser/main.php or xaruser.php
 * function myfancymodule_user_main($args = [], $context = null) {
 *     // get module class instance first
 *     //$module = xarMod::getModule('myfancymodule');
 *     //$module->setContext($context);
 *     //return $module->getGUI()->main($args);
 *     // or get module gui directly
 *     $usergui = xarMod::getGUI('myfancymodule');
 *     $usergui->setContext($context);
 *     return $usergui->main($args);
 * }
 * ```
 * }
 *
 * // xaruser.php
 * function myfancymodule_user_main($args = [], $context = null) {
 *     // get module class instance first
 *     //$module = xarMod::getModule('myfancymodule');
 *     //$module->setContext($context);
 *     //return $module->getGUI()->main($args);
 *     // or get module gui directly
 *     $usergui = xarMod::getGUI('myfancymodule');
 *     $usergui->setContext($context);
 *     return $usergui->main($args);
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

sys::import('xaraya.modules.userguitrait');

/**
 * Summary of UserGui
 */
class UserGui implements UserGuiInterface
{
    use UserGuiTrait;
}
