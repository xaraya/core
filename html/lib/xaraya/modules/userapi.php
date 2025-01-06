<?php

/**
 * Handle module user api functions
 *
 * Usage:
 * ```
 * // class/userapi.php
 * namespace Xaraya\Modules\MyFancyModule;
 *
 * use Xaraya\Modules\UserApiInterface;
 * use Xaraya\Modules\UserApiTrait;
 *
 * class UserApi implements UserApiInterface
 * {
 *     use UserApiTrait;
 *
 *     public function get($args = []) {
 *         // get single module item
 *         return $args;
 *     }
 * }
 *
 * // xaruserapi/get.php or xaruserapi.php
 * function myfancymodule_userapi_get($args = [], $context = null) {
 *     // get module class instance first
 *     //$module = xarMod::getModule('myfancymodule');
 *     //$module->setContext($context);
 *     //return $module->getAPI()->get($args);
 *     // or get module api directly
 *     $userapi = xarMod::getAPI('myfancymodule');
 *     $userapi->setContext($context);
 *     return $userapi->get($args);
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

sys::import('xaraya.modules.userapitrait');

/**
 * Summary of UserApi
 */
class UserApi implements UserApiInterface
{
    use UserApiTrait;
}
