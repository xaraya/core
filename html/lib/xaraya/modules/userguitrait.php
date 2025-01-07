<?php

/**
 * Handle module user gui functions
 *
 * Usage:
 * ```
 * # class/usergui.php
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
 *         // get main user overview
 *         return $args;
 *     }
 * }
 *
 * # xaruser/main.php or xaruser.php (migration)
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

use xarMod;
use sys;

sys::import('xaraya.modules.methodstrait');

/**
 * For documentation purposes only - available via UserGuiTrait
 */
interface UserGuiInterface extends MethodsInterface
{
    /**
     * Summary of init
     * @param array<string, mixed> $args
     * @return void
     */
    public function init(array $args = []);

    /**
     * Summary of main
     * @param array<string, mixed> $args
     * @return array<mixed>
     */
    public function main(array $args = []);
}

/**
 * Trait to handle user gui functions
 */
trait UserGuiTrait
{
    use MethodsTrait;

    protected string $moduleName;          // set in constructor by ModuleTrait::createComponent()
    protected int $itemtype = 0;
    /** @var UserApiInterface */
    protected $api;

    public function __construct(string $moduleName)
    {
        $this->moduleName = $moduleName;
        $this->loadModule();
    }

    protected function loadModule(): void
    {
        xarMod::load($this->moduleName, 'user');
    }

    /**
     * Summary of getAPI
     * @return UserApiInterface
     */
    protected function getAPI()
    {
        $this->api ??= xarMod::getModule($this->moduleName)->getAPI();
        return $this->api;
    }

    /**
     * Summary of init
     * @param array<string, mixed> $args
     * @return void
     */
    public function init(array $args = []) {}

    /**
     * Summary of main
     * @param array<string, mixed> $args
     * @return array<mixed>
     */
    public function main(array $args = [])
    {
        $output = [
            'args' => $args,
        ];
        return $this->prepareOutput($output);
    }

    /**
     * Add standard template variables (module, itemtype and context)
     * @param array<string, mixed> $data
     * @return array<mixed>
     */
    protected function prepareOutput(array $data): array
    {
        // Add standard template variables
        $data['module'] ??= $this->moduleName ?? '';
        $data['itemtype'] ??= $this->itemtype ?? 0;
        // Pass along the context for xarTpl::module() if needed
        $data['context'] ??= $this->getContext();
        return $data;
    }
}
