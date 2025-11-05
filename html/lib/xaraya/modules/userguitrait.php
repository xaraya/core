<?php

/**
 * Handle module user gui functions
 *
 * Usage:
 * ```
 * # usergui.php
 * namespace Xaraya\Modules\MyFancyModule;
 *
 * use Xaraya\Modules\UserGuiInterface;
 * use Xaraya\Modules\UserGuiTrait;
 *
 * class UserGui implements UserGuiInterface
 * {
 *     /** @use UserGuiTrait<Module> *\/
 *     use UserGuiTrait;
 *
 *     public function main($args = []) {
 *         // get main user overview
 *         // $context = $this->getContext();
 *         return $output;
 *     }
 * }
 *
 * # xaruser/main.php or xaruser.php (migration)
 * function myfancymodule_user_main($args = [], $context = null) {
 *     // get module class instance first
 *     //$module = xar::mod()->getModule('myfancymodule');
 *     //$module->setContext($context);
 *     //return $module->usergui()->main($args);
 *     // or get module gui directly
 *     $usergui = xar::mod()->usergui('myfancymodule');
 *     $usergui->setContext($context);
 *     return $usergui->main($args);
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

use ixarMod;
use xarMod;
use sys;

sys::import('xaraya.modules.servicestrait');

/**
 * Module class supports user gui methods - available via UserGuiTrait
 */
interface UserGuiInterface extends GuiModuleServicesInterface
{
    /**
     * Summary of main
     * @param array<string, mixed> $args
     * @return array<mixed>|string|void
     */
    public function main(array $args = []);
}

/**
 * Trait to handle user gui functions
 * @template TModule of ModuleInterface|null
 */
trait UserGuiTrait
{
    /** @use ModuleServicesTrait<TModule> */
    use ModuleServicesTrait;

    /**
     * Summary of configure
     * @return void
     */
    public function configure()
    {
        $this->setModType('user');
        // any state here = otherwise during module init(), any GUI hook functions registered will throw ModuleNotActiveException
        $this->mod()->load($this->getModName(), $this->getModType(), ixarMod::LOAD_ANYSTATE);
    }

    /**
     * Summary of main
     * @param array<string, mixed> $args
     * @return array<mixed>|string|void
     */
    public function main(array $args = [])
    {
        // use main method class if it exists
        if (class_exists($this->getClassName('main'))) {
            return $this->__call('main', [$args]);
        }
        $output = [
            'args' => $args,
        ];
        return $this->mod()->prepare($output);
    }
}
