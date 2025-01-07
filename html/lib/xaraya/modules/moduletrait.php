<?php

/**
 * Handle module classes via xarMod::getModule()
 *
 * Usage:
 * ```
 * # class/module.php
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
 * # xaruser/main.php or xaruser.php (migration)
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

use Xaraya\Context\ContextInterface;
use Xaraya\Context\ContextTrait;
use xarMod;
use sys;

sys::import('xaraya.modules.methodstrait');

/**
 * For documentation purposes only - available via ModuleTrait
 */
interface ModuleInterface extends ContextInterface
{
    public function getName(): string;
    public function getInfo(): array;
    public function getComponent(string $type): MethodsInterface|null;
    public function hasComponent(string $type): bool;
    public function getAPI(): UserApiInterface|null;
    public function getGUI(): UserGuiInterface|null;
    public function getAdminAPI(): AdminApiInterface|null;
    public function getAdminGUI(): AdminGuiInterface|null;
    public function getHooks(): HooksInterface|null;
    public function getInstaller(): InstallerInterface|null;
    public function setClassTypes(): void;
    public function getClassType(string $modType): string|null;
    public function getCallableMethod(string $modType, string $funcName): callable|null;
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
    /** @var array<string, string> */
    protected array $classtypes = [];
    /** @var array<string, object|null> */
    private array $components = [];

    public function __construct(string $moduleName)
    {
        $this->moduleName = $moduleName;
        $this->setClassTypes();
    }

    protected function createComponent(string $type): MethodsInterface
    {
        // this assumes that the class is in the same namespace as the module
        $class = $this->getClassName($type);
        return new $class($this->moduleName);
    }

    protected function getClassName(string $type): string
    {
        // this assumes that the class is in the same namespace as the module
        // Xaraya\Modules\MyFancyModule\AdminGui
        return $this->getNamespace() . '\\' . $type;
    }

    protected function getNamespace(): string
    {
        // Xaraya\Modules\MyFancyModule
        return substr($this::class, 0, strrpos($this::class, '\\'));
    }

    public function getComponent(string $type): MethodsInterface|null
    {
        if (!array_key_exists($type, $this->components)) {
            try {
                $this->components[$type] = $this->createComponent($type);
                if ($this->context !== null) {
                    $this->components[$type]->setContext($this->context);
                }
            } catch (\Throwable $e) {
                $this->components[$type] = null;
            }
        }
        return $this->components[$type];
    }

    public function hasComponent(string $type): bool
    {
        $className = $this->getClassName($type);
        return class_exists($className);
    }

    public function setContext($context)
    {
        $this->context = $context;
        foreach ($this->components as $component) {
            if (is_null($component)) {
                continue;
            }
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

    public function getAPI(): UserApiInterface|null
    {
        return $this->getComponent('UserApi');
    }

    public function getGUI(): UserGuiInterface|null
    {
        return $this->getComponent('UserGui');
    }

    public function getAdminAPI(): AdminApiInterface|null
    {
        return $this->getComponent('AdminApi');
    }

    public function getAdminGUI(): AdminGuiInterface|null
    {
        return $this->getComponent('AdminGui');
    }

    public function getHooks(): HooksInterface|null
    {
        return $this->getComponent('Hooks');
    }

    public function getInstaller(): InstallerInterface|null
    {
        return $this->getComponent('Installer');
    }

    /**
     * Use this to override or add class types if extended - see library
     */
    public function setClassTypes(): void
    {
        $this->classtypes = [
            // common types
            'userapi' => 'UserApi',
            'user' => 'UserGui',
            'adminapi' => 'AdminApi',
            'admin' => 'AdminGui',
            // special types
            'hooks' => 'Hooks',
            'installer' => 'Installer',
            // other types
            'dataapi' => 'DataApi',
            'restapi' => 'RestApi',
            'schedulerapi' => 'SchedulerApi',
            'utilapi' => 'UtilApi',
        ];
    }

    /**
     * @see \xarMod::privateLoad()
     */
    public function getClassType(string $modType): string|null
    {
        if (isset($this->classtypes[$modType])) {
            return $this->classtypes[$modType];
        }
        return null;
    }

    /**
     * @see \xarMod::getModuleClassMethod()
     */
    public function getCallableMethod(string $modType, string $funcName): callable|null
    {
        $classType = $this->getClassType($modType);
        if (!isset($classType)) {
            return null;
        }
        $component = $this->getComponent($classType);
        if (!isset($component)) {
            return null;
        }
        if ($component->hasMethod($funcName)) {
            // use array format instead of first-class callable syntax to allow setting the context
            return [$component, $funcName];
        }
        return null;
    }
}
