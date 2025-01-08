<?php

/**
 * Handle module user api functions
 *
 * Usage:
 * ```
 * # class/userapi.php
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
 *         // $context = $this->getContext();
 *         return $data;
 *     }
 * }
 *
 * # xaruserapi/get.php or xaruserapi.php (migration)
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
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Modules;

use xarMod;
use sys;

sys::import('xaraya.modules.methodstrait');

/**
 * Module class supports user api methods - available via UserApiTrait
 */
interface UserApiInterface extends ApiMethodsInterface
{
    // ...
}

/**
 * Trait to handle user api functions
 */
trait UserApiTrait
{
    use MethodsTrait;

    protected string $moduleName;          // set in constructor by ModuleTrait::createComponent()
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
