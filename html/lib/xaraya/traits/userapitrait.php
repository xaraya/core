<?php

/**
 * Trait to handle user api functions
 *
 * Usage:
 * ```
 * // class/userapi.php
 * namespace Xaraya\Modules\MyFancyModule;
 *
 * use Xaraya\Core\Traits\UserApiInterface;
 * use Xaraya\Core\Traits\UserApiTrait;
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
 * @package core\traits
 * @subpackage traits
 * @category Xaraya Web Applications Framework
 * @version 2.5.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Core\Traits;

use xarMod;
use sys;

sys::import('xaraya.traits.hookstrait');

/**
 * For documentation purposes only - available via UserApiTrait
 */
interface UserApiInterface extends ContextInterface, HooksInterface
{
    // ...
}

/**
 * Trait to handle user api functions
 */
trait UserApiTrait
{
    use ContextTrait;
    use HooksTrait;

    protected string $moduleName;          // set in constructor by xarMod::getModule()
    protected int $moduleId;
    protected int $itemtype = 0;

    public function __construct(string $moduleName)
    {
        $this->moduleName = $moduleName;
        $this->loadModule();
        $this->moduleId = $this->getModuleId();
    }

    protected function loadModule(): void
    {
        xarMod::apiLoad($this->moduleName, 'user');
    }

    /**
     * Get module registry ID by name
     * @return int
     */
    protected function getModuleId(): int
    {
        // avoid getting module id from xarMod::getRegID() here
        //return xarMod::getRegId($this->moduleName);
        $fileInfo = xarMod::getFileInfo($this->moduleName);
        return $fileInfo['regid'];
    }
}

/**
 * Summary of DefaultUserApi
 */
class DefaultUserApi implements UserApiInterface
{
    use UserApiTrait;
}
