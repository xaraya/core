<?php

/**
 * Handle module classes via xar::mod()->getModule()
 *
 * Usage:
 * ```
 * # module.php
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
 *     $module = xar::mod()->getModule('myfancymodule');
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

use Xaraya\Context\Context;
use Xaraya\Context\ContextInterface;
use Xaraya\Context\ContextTrait;
use xarMod;
use sys;
use Exception;

sys::import('xaraya.modules.servicestrait');
sys::import('xaraya.services.xar');
use Xaraya\Services\xar;

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
    public function getComponent(string $type): ?ModuleServicesInterface;
    public function hasComponent(string $type): bool;
    public function userapi(): ?UserApiInterface;
    public function usergui(): ?UserGuiInterface;
    public function adminapi(): ?AdminApiInterface;
    public function admingui(): ?AdminGuiInterface;
    public function installer(): ?InstallerInterface;
    public function restapi(): ?UserApiInterface;
    public function schedulerapi(): ?UserApiInterface;
    public function setClassTypes(): void;
    public function getClassType(string $modType): ?string;
    public function getCallableMethod(string $modType, string $funcName, string $callType = 'api'): ?callable;
}

/**
 * Trait to get module classes via xar::mod()->getModule()
 * @uses \sys::autoload()
 * @see \xar::mod()->getModule()
 */
trait ModuleTrait
{
    use ContextTrait;

    protected string $moduleName;          // set in constructor by xar::mod()->getModule()

    /** @var array<string, string> */
    protected array $classtypes = [];
    /** @var array<string, ModuleServicesInterface|null> */
    private array $components = [];

    /**
     * @param ?Context<string, mixed> $context
     */
    public function __construct(string $modName, ?Context $context = null)
    {
        $this->setModName($modName);
        $this->setContext($context);
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
        return new $className($this->getModName(), $this, $this->context);
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

    public function getComponent(string $type): ?ModuleServicesInterface
    {
        if (!array_key_exists($type, $this->components)) {
            try {
                // this assumes that the class is in the same namespace as the module
                $className = $this->getClassName($type);
                if (class_exists($className)) {
                    $this->components[$type] = $this->createComponent($className);
                } else {
                    $this->components[$type] = null;
                }
            } catch (\Throwable $e) {
                throw new Exception("Unable to create '$className': " . $e->getMessage(), 0, $e);
                //$this->components[$type] = null;
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
     * Get file info from version.php
     * @return array<string, mixed>
     */
    public function getFileInfo(): array
    {
        // Xaraya\Modules\MyFancyModule\Version
        $className = $this->getNamespace() . '\\Version';
        if (class_exists($className)) {
            $versionCall = new $className();
            $modversion = $versionCall();
            return xar::mod()->parseFileInfo($modversion);
        }
        return xar::mod()->getFileInfo($this->getModName());
    }

    /**
     * Get tables from tables.php
     * @return array<string, mixed>
     */
    public function getTables(): array
    {
        // Xaraya\Modules\MyFancyModule\Tables
        $className = $this->getNamespace() . '\\Tables';
        if (class_exists($className)) {
            $tablesCall = new $className();
            // pass along the DB prefix to $tablesCall
            return $tablesCall(xar::db()->getPrefix());
        }

        // Load the database definition if required
        try {
            include_once sys::code() . 'modules/' . $this->getModName() . '/xartables.php';
        } catch (Exception $e) {
            return [];
        }
        $tablefunc = $this->getModName() . '_' . 'xartables';
        if (function_exists($tablefunc)) {
            // pass along the DB prefix to $tablefunc
            $prefix = xar::db()->getPrefix();
            return $tablefunc($prefix);
        }
        return [];
    }

    public function userapi(): ?UserApiInterface
    {
        $component = $this->getComponent('UserApi');
        assert($component instanceof UserApiInterface);
        return $component;
    }

    public function usergui(): ?UserGuiInterface
    {
        $component = $this->getComponent('UserGui');
        assert($component instanceof UserGuiInterface);
        return $component;
    }

    public function adminapi(): ?AdminApiInterface
    {
        $component = $this->getComponent('AdminApi');
        assert($component instanceof AdminApiInterface);
        return $component;
    }

    public function admingui(): ?AdminGuiInterface
    {
        $component = $this->getComponent('AdminGui');
        assert($component instanceof AdminGuiInterface);
        return $component;
    }

    public function installer(): ?InstallerInterface
    {
        $component = $this->getComponent('Installer');
        assert($component instanceof InstallerInterface);
        return $component;
    }

    public function restapi(): ?UserApiInterface
    {
        $component = $this->getComponent('RestApi');
        //assert($component instanceof UserApiInterface);
        return $component;
    }

    public function schedulerapi(): ?UserApiInterface
    {
        $component = $this->getComponent('SchedulerApi');
        //assert($component instanceof UserApiInterface);
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
            'installer' => 'Installer',
            // other types
            'dataapi' => 'DataApi',
            'restapi' => 'RestApi',
            'schedulerapi' => 'SchedulerApi',
            'utilapi' => 'UtilApi',
        ];
    }

    /**
     * @see \xar::mod()->privateLoad()
     */
    public function getClassType(string $modType): ?string
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
     * @see \xar::mod()->getModuleClassMethod()
     */
    public function getCallableMethod(string $modType, string $funcName, string $callType = 'api'): ?callable
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
