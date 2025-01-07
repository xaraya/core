<?php

/**
 * Handle single module function as method
 *
 * Usage:
 * ```
 * # class/userapi/get.php
 * namespace Xaraya\Modules\MyFancyModule\UserApi;
 *
 * use Xaraya\Modules\MethodClass;
 * use sys;
 *
 * sys::import('xaraya.modules.method');
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
 * @version 2.5.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Modules;

use Xaraya\Context\ContextInterface;
use Xaraya\Context\ContextTrait;
use xarMod;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.hookstrait');

/**
 * Handle single module function as method
 */
class MethodClass implements ContextInterface, HooksInterface
{
    use ContextTrait;
    use HooksTrait;

    public function __invoke(array $args = [])
    {
        return $args;
    }

    public function __construct(string $moduleName)
    {
        $this->moduleName = $moduleName;
    }

    /** Wrap some frequently used static method calls here */

    public function checkAccess(string $mask, string $action = ''): bool
    {
        if (empty($mask) && !empty($action)) {
            return xarMod::checkAccess($this->moduleName, $action) ? true : false;
        }
        // @todo use $action for something here, and/or pass moduleName?
        return xarSecurity::check($mask) ? true : false;
    }

    public function fetchVar($name, $validation, &$value, $defaultValue = null, $flags = xarVar::GET_OR_POST, $prep = xarVar::PREP_FOR_NOTHING)
    {
        return xarVar::fetch($name, $validation, $value, $defaultValue, $flags, $prep);
    }
}
