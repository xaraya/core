<?php

/**
 * Handle module classes via xar::module()
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
 *     $module = xar::module('myfancymodule');
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
use Xaraya\Context\WithContextInterface;
use Xaraya\Context\WithContextTrait;
use Xaraya\Services\ServicesInterface;
use Xaraya\Services\WithServicesTrait;
use Xaraya\Services\WithServicesInterface;
use sys;
use Exception;

/**
 * For documentation purposes only - available via ModuleTrait
 */
interface ModuleInterface extends WithContextInterface, WithServicesInterface
{
    public function __construct(string $modName, ?Context $context = null, $xar = null);
    /** @return void */
    public function configure();
    public function getName(): string;
    /** @return array<string, mixed> */
    public function getFileInfo(): array;
    /** @return array<string, mixed> */
    public function getTables(): array;
    public function getComponent(string $classType): ?ModuleClassInterface;
    public function hasComponent(string $classType): bool;
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
 * Trait to get module classes via xar::module()
 * @see \xar::module()
 */
trait ModuleTrait
{
    use WithContextTrait;
    use WithServicesTrait;

    protected string $moduleName;          // set in constructor by xar::module()

    /** @var array<string, string> */
    protected array $classtypes = [];
    /** @var array<string, ModuleClassInterface|null> */
    private array $components = [];

    /**
     * @param ?Context<string, mixed> $context
     * @param ?ServicesInterface $xar
     */
    public function __construct(string $modName, ?Context $context = null, $xar = null)
    {
        $this->setModName($modName);
        $this->setContext($context);
        $this->setServicesClass($xar);
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
     * @template TComponent of ModuleClassInterface
     * @param class-string<TComponent> $className
     * @return TComponent
     */
    protected function createComponent(string $className): ModuleClassInterface
    {
        return new $className($this->getModName(), $this);
    }

    /**
     * Summary of getClassName
     * @param string $classType
     * @return class-string<ModuleClassInterface>
     */
    protected function getClassName(string $classType): string
    {
        // this assumes that the class is in the same namespace as the module
        // Xaraya\Modules\MyFancyModule\AdminGui
        return $this->getNamespace() . '\\' . $classType;
    }

    protected function getNamespace(): string
    {
        // Xaraya\Modules\MyFancyModule
        return substr($this::class, 0, strrpos($this::class, '\\'));
    }

    public function getComponent(string $classType): ?ModuleClassInterface
    {
        if (!array_key_exists($classType, $this->components)) {
            try {
                // this assumes that the class is in the same namespace as the module
                $className = $this->getClassName($classType);
                if (class_exists($className)) {
                    $this->components[$classType] = $this->createComponent($className);
                } else {
                    $this->components[$classType] = null;
                }
            } catch (\Throwable $e) {
                throw new Exception("Unable to create '$className': " . $e->getMessage(), 0, $e);
                //$this->components[$classType] = null;
            }
        }
        return $this->components[$classType];
    }

    public function hasComponent(string $classType): bool
    {
        $className = $this->getClassName($classType);
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
        $xar = $this->getServicesClass();
        // Xaraya\Modules\MyFancyModule\Version
        $className = $this->getNamespace() . '\\Version';
        if (class_exists($className)) {
            $versionCall = new $className();
            $modversion = $versionCall();
            return $xar->mod()->parseFileInfo($modversion);
        }
        return $xar->mod()->getFileInfo($this->getModName());
    }

    /**
     * Get tables from tables.php
     * @return array<string, mixed>
     */
    public function getTables(): array
    {
        $xar = $this->getServicesClass();
        // Xaraya\Modules\MyFancyModule\Tables
        $className = $this->getNamespace() . '\\Tables';
        if (class_exists($className)) {
            $tablesCall = new $className();
            // pass along the DB prefix to $tablesCall
            return $tablesCall($xar->db()->getPrefix());
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
            $prefix = $xar->db()->getPrefix();
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

    public function __serialize()
    {
        // reset components for comparison - see SerializeServicesTest::testModuleClass()
        $this->components = [];
        // add any protected/private properties that are relevent here
        return [
            'moduleName' => $this->moduleName ?? null,
            'classtypes' => $this->classtypes,
            'context'    => $this->getContext(),
        ];
    }

    public function __unserialize($data)
    {
        foreach ($data as $name => $value) {
            $this->{$name} = $value;
        }
        // Reconnect to current static services
        $this->getServicesClass();
    }
}
