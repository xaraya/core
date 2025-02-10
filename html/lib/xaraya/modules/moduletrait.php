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
 *     return $module->usergui()->main($args);
 *     // or get module gui directly
 *     // see usergui.php for an example
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

use Xaraya\Context\ContextInterface;
use Xaraya\Context\ContextTrait;
use xarMod;
use sys;
use Exception;

sys::import('xaraya.modules.servicestrait');
sys::import('xaraya.facades.database');
use Xaraya\Facades\xarDB3;

/**
 * For documentation purposes only - available via ModuleTrait
 */
interface ModuleInterface extends ContextInterface
{
    public function __construct(string $modName);
    /** @return void */
    public function configure();
    public function getName(): string;
    /** @return array<string, mixed> */
    public function getFileInfo(): array;
    /** @return array<string, mixed> */
    public function getTables(): array;
    public function getComponent(string $type): ModuleServicesInterface|null;
    public function hasComponent(string $type): bool;
    public function userapi(): UserApiInterface|null;
    public function usergui(): UserGuiInterface|null;
    public function adminapi(): AdminApiInterface|null;
    public function admingui(): AdminGuiInterface|null;
    public function installer(): InstallerInterface|null;
    public function setClassTypes(): void;
    public function getClassType(string $modType): string|null;
    public function getCallableMethod(string $modType, string $funcName, string $callType = 'api'): callable|null;
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
    /** @var array<string, ModuleServicesInterface|null> */
    private array $components = [];

    public function __construct(string $modName)
    {
        $this->setModName($modName);
        $this->configure();
    }

    /**
     * Configure module class types - override if needed
     * @return void
     */
    public function configure()
    {
        $this->setClassTypes();
    }

    public function getModName(): string
    {
        return $this->moduleName;
    }

    public function setModName(string $modName): void
    {
        $this->moduleName = $modName;
    }

    /**
     * Summary of createComponent
     * @see https://phpstan.org/blog/generics-by-examples
     * @template TComponent of ModuleServicesInterface
     * @param class-string<TComponent> $className
     * @return TComponent
     */
    protected function createComponent(string $className): ModuleServicesInterface
    {
        return new $className($this->getModName(), $this);
    }

    /**
     * Summary of getClassName
     * @param string $type
     * @return class-string<ModuleServicesInterface>
     */
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

    public function getComponent(string $type): ModuleServicesInterface|null
    {
        if (!array_key_exists($type, $this->components)) {
            try {
                // this assumes that the class is in the same namespace as the module
                $className = $this->getClassName($type);
                $this->components[$type] = $this->createComponent($className);
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
        return $this->getModName();
    }

    /**
     * Get info from xarversion.php
     * @return array<string, mixed>
     */
    public function getFileInfo(): array
    {
        return xarMod::getFileInfo($this->getModName());
    }

    /**
     * Get tables from xartables.php
     * @return array<string, mixed>
     */
    public function getTables(): array
    {
        // Load the database definition if required
        try {
            sys::import('modules.' . $this->getModName() . '.xartables');
        } catch (Exception $e) {
            return [];
        }
        $tablefunc = $this->getModName() . '_' . 'xartables';
        if (function_exists($tablefunc)) {
            // pass along the DB prefix to $tablefunc
            $prefix = xarDB3::getPrefix();
            // xarDB3::importTables($tablefunc($prefix));
            return $tablefunc($prefix);
        }
        return [];
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
        return xarMod::apiFunc($this->getModName(), $type, $func, $args, $this->getContext());
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
        return xarMod::guiFunc($this->getModName(), $type, $func, $args, $this->getContext());
    }

    public function userapi(): UserApiInterface|null
    {
        $component = $this->getComponent('UserApi');
        assert($component instanceof UserApiInterface);
        return $component;
    }

    public function usergui(): UserGuiInterface|null
    {
        $component = $this->getComponent('UserGui');
        assert($component instanceof UserGuiInterface);
        return $component;
    }

    public function adminapi(): AdminApiInterface|null
    {
        $component = $this->getComponent('AdminApi');
        assert($component instanceof AdminApiInterface);
        return $component;
    }

    public function admingui(): AdminGuiInterface|null
    {
        $component = $this->getComponent('AdminGui');
        assert($component instanceof AdminGuiInterface);
        return $component;
    }

    public function installer(): InstallerInterface|null
    {
        $component = $this->getComponent('Installer');
        assert($component instanceof InstallerInterface);
        return $component;
    }

    /**
     * Use this to override or add class types if extended - see library
     */
    public function setClassTypes(): void
    {
        $this->classtypes = [
            // common types
            'userapi' => 'UserApi',
            'usergui' => 'UserGui',
            'user' => 'UserGui',
            'adminapi' => 'AdminApi',
            'admingui' => 'AdminGui',
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
        // fall back to finding module class in same namespace,
        // e.g. renderer -> Xaraya\Modules\MyFancyModule\Renderer
        $classType = ucfirst($modType);
        if ($this->hasComponent($classType)) {
            $this->classtypes[$modType] = $classType;
            return $this->classtypes[$modType];
        }
        return null;
    }

    /**
     * @see \xarMod::getModuleClassMethod()
     */
    public function getCallableMethod(string $modType, string $funcName, string $callType = 'api'): callable|null
    {
        // $modType already includes $funcType here, e.g. userapi or installer
        $classType = $this->getClassType($modType);
        if (!isset($classType)) {
            return null;
        }
        $component = $this->getComponent($classType);
        if (!isset($component)) {
            return null;
        }
        // @todo should we check $callType on component level or method level - do we allow mix of both in class?
        if ($component->hasMethod($funcName, $callType)) {
            // use array format instead of first-class callable syntax to allow setting the context
            return [$component, $funcName];
        }
        return null;
    }
}
