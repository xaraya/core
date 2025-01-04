<?php

/**
 * Trait to get module classes via xarMod::getModule()
 *
 * Usage:
 * ```
 * // class/module.php
 * namespace Xaraya\Modules\MyFancyModule;
 *
 * use Xaraya\Core\Traits\ModuleInterface;
 * use Xaraya\Core\Traits\ModuleTrait;
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

/**
 * For documentation purposes only - available via ModuleTrait
 */
interface ModuleInterface extends ContextInterface
{
    public function getName(): string;
    public function getAPI(): object;
    public function getGUI(): object;
    public function getAdminAPI(): object;
    public function getAdminGUI(): object;
    public function getHooks(): object;
    public function getInstaller(): object;
}

/**
 * Trait to get module classes via xarMod::getModule()
 * @uses \sys::autoload()
 * @see \xarMod::getModule()
 */
trait ModuleTrait
{
    use ContextTrait;

    protected string $moduleName;          // set in constructor by xarMod::getModule()
    /** @var array<string, object> */
    private array $components = [];

    public function __construct(string $moduleName)
    {
        $this->moduleName = $moduleName;
    }

    protected function createComponent(string $type): object
    {
        // this assumes that the class is in the same namespace as the module
        $class = $this->getNamespace() . '\\' . $type;
        return new $class($this->moduleName);
    }

    protected function getNamespace(): string
    {
        return substr($this::class, 0, strrpos($this::class, '\\'));
    }

    protected function getComponent(string $type): object
    {
        if (!isset($this->components[$type])) {
            // @todo do we need to call xarMod::load() and/or xarMod::apiLoad() here?
            $this->components[$type] = $this->createComponent($type);
            if ($this->context !== null) {
                $this->components[$type]->setContext($this->context);
            }
        }
        return $this->components[$type];
    }

    public function setContext($context)
    {
        $this->context = $context;
        foreach ($this->components as $component) {
            if (method_exists($component, 'setContext')) {
                $component->setContext($context);
            }
        }
    }

    public function getName(): string
    {
        return $this->moduleName;
    }

    /**
     * Get info from xarversion.php
     * @return array<string, mixed>
     */
    public function getInfo(): array
    {
        return xarMod::getFileInfo($this->moduleName);
    }

    /**
     * Wrapper for xarMod::apiFunc() - only for migration
     * @param mixed $type
     * @param mixed $func
     * @param mixed $args
     * @return mixed
     */
    public function callAPI($type, $func, $args = [])
    {
        return xarMod::apiFunc($this->moduleName, $type, $func, $args, $this->getContext());
    }

    /**
     * Wrapper for xarMod::guiFunc() - only for migration
     * @param mixed $type
     * @param mixed $func
     * @param mixed $args
     * @return mixed
     */
    public function callGUI($type, $func, $args = [])
    {
        return xarMod::guiFunc($this->moduleName, $type, $func, $args, $this->getContext());
    }

    public function getAPI(): object
    {
        return $this->getComponent('UserApi');
    }

    public function getGUI(): object
    {
        return $this->getComponent('UserGui');
    }

    public function getAdminAPI(): object
    {
        return $this->getComponent('AdminApi');
    }

    public function getAdminGUI(): object
    {
        return $this->getComponent('AdminGui');
    }

    public function getHooks(): object
    {
        return $this->getComponent('Hooks');
    }

    public function getInstaller(): object
    {
        return $this->getComponent('Installer');
    }
}
