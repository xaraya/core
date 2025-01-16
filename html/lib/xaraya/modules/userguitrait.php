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
 * @version 2.5.7
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Modules;

use xarMod;
use xarTpl;
use sys;

sys::import('xaraya.modules.methodstrait');

/**
 * Module class supports user gui methods - available via UserGuiTrait
 */
interface UserGuiInterface extends GuiMethodsInterface
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
    /** @use MethodsTrait<TModule> */
    use MethodsTrait;

    /**
     * Summary of configure
     * @return void
     */
    public function configure()
    {
        $this->setModType('user');
        xarMod::load($this->getModName(), $this->getModType());
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
        return $this->prepareOutput($output);
    }

    /**
     * Add standard template variables (module, itemtype and context)
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function prepareOutput(array $data = []): array
    {
        // Add standard template variables
        $data['module'] ??= $this->getModName();
        $data['itemtype'] ??= $this->getItemType();
        // Pass along the context for xarTpl::module() if needed
        $data['context'] ??= $this->getContext();
        return $data;
    }

    /**
     * Summary of tplModule
     * @param string $funcName
     * @param array<string, mixed> $data
     * @return string
     */
    protected function tplModule(string $funcName, array $data = []): string
    {
        // Add standard template variables (module, itemtype and context)
        $data = $this->prepareOutput($data);

        // See if we have a special template to apply
        $templateName = null;
        if (isset($data['_bl_template'])) {
            $templateName = $data['_bl_template'];
        }

        // Create the output.
        return xarTpl::module($this->getModName(), $this->getModType(), $funcName, $data, $templateName);
    }

    /**
     * Set page title
     * @param string $title
     * @return bool
     */
    public function setPageTitle(string $title): bool
    {
        return xarTpl::setPageTitle($title, $this->getModName());
    }
}
