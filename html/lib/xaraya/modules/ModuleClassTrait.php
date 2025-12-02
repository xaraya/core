<?php

/**
 * Handle module functions as methods
 *
 * @package core\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.8.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Modules;

use Xaraya\Context\Context;
use Xaraya\Services\ServicesInterface;
use Xaraya\Services\CoreServicesTrait;

/**
 * For documentation purposes only - available via ModuleClassTrait
 */
interface ModuleClassInterface extends ServicesInterface
{
    /** @param ?Context<string, mixed> $context */
    public function __construct(string $modName, ?ModuleInterface $parent = null, ?Context $context = null);
    /** @return void */
    public function configure();
    public function getModType(): string;
    public function setModType(string $modType): void;
    public function hasMethod(string $funcName, string $callType = 'api'): bool;
    public function getModule(?string $modName = null): ?ModuleInterface;
    public function userapi(): ?UserApiInterface;
    public function usergui(): ?UserGuiInterface;
    public function adminapi(): ?AdminApiInterface;
    public function admingui(): ?AdminGuiInterface;
}

/**
 * @deprecated 2.8.9 use ModuleClassInterface() instead
 */
interface ModuleServicesInterface extends ModuleClassInterface
{
    // ...
}

/**
 * Module class supports api methods
 */
interface ApiModuleClassInterface extends ModuleClassInterface
{
    // ...
}

/**
 * Module class supports gui methods
 */
interface GuiModuleClassInterface extends ModuleClassInterface
{
    // ...
}

/**
 * Trait to handle module functions as methods
 * @see https://phpstan.org/blog/generics-in-php-using-phpdocs
 * @template TModule of ModuleInterface|null
 */
trait ModuleClassTrait
{
    use CoreServicesTrait;

    protected string $moduleName;          // set in constructor by ModuleTrait::createComponent()
    protected string $moduleType;          // set in configure() by user/admin gui/api traits
    protected int $itemtype = 0;
    protected ?ModuleInterface $parent;

    /** @var array<string> */
    protected array $allowed = [];
    /** @var array<string> */
    protected array $internal = [
        // ContextTrait
        'getcontext',
        'setcontext',
        // CoreServicesTrait
        'ctl',
        'log',
        'mls',
        'mod',
        'sec',
        'tpl',
        'var',
        'block',
        'data',
        'prop',
        'cache',
        'db',
        'ml',
        'exit',
        // ModuleClassTrait
        'configure',
        'getmodtype',
        'setmodtype',
        'hasmethod',
        'getmodule',
        'setmodule',
        'getmethodclass',
        'getclassname',
        'getnamespace',
        'getapi',
        'userapi',
        'usergui',
        'adminapi',
        'admingui',
        'getmodname',
        'setmodname',
        'getitemtype',
        'setitemtype',
        'getblocktype',
        'getobject',
        'getproperty',
        // @todo add new internal methods here + find a better way to do this
    ];
    /** @var array<string, MethodClassInterface<ModuleClassInterface>|null> */
    private array $methods = [];

    /**
     * Summary of __construct
     * @param TModule $parent
     * @param ?Context<string, mixed> $context
     * @param ?ServicesInterface $xar
     */
    public function __construct(string $modName, ?ModuleInterface $parent = null, ?Context $context = null, $xar = null)
    {
        $this->setModName($modName);
        $this->setModule($parent);
        $this->setContext($context);
        if (isset($xar)) {
            $this->setStaticServices($xar->getStaticServices());
        }
        // call configure() after setting the context
        $this->configure();
    }

    /**
     * Configure this module class - override if needed
     * @return void
     * @see \Xaraya\Modules\ModuleTrait::getComponent()
     */
    public function configure()
    {
        // ...
    }

    /**
     * Get module type of this module class (user, admin, ...)
     */
    public function getModType(): string
    {
        return $this->moduleType;
    }

    /**
     * Set module type for this module class (user, admin, ...)
     */
    public function setModType(string $modType): void
    {
        $this->moduleType = $modType;
    }

    /**
     * Summary of hasMethod
     * @param string $funcName
     * @param string $callType is this for an api call or not?
     * @return bool
     */
    public function hasMethod(string $funcName, string $callType = 'api'): bool
    {
        // restrict any internal _* methods (including magic methods)
        if (str_starts_with($funcName, '_')) {
            return false;
        }
        // @todo should we check $callType on component level or method level - do we allow mix of both in class?
        // don't allow api methods to be called as gui functions
        if ($callType != 'api' && $this instanceof ApiModuleClassInterface) {
            return false;
        }
        // Note: non-api methods can still be called as api functions here if needed
        //if ($callType == 'api' && !($this instanceof ApiModuleClassInterface)) {
        //    return false;
        //}
        // normalize for case-insensitive + conversion from snake_case to PascalCase
        $normalized = strtolower(str_replace('_', '', $funcName));
        // whitelist methods (if defined)
        if (!empty($this->allowed) && !in_array($normalized, $this->allowed)) {
            return false;
        }
        // blacklist methods (always)
        if (in_array($normalized, $this->internal)) {
            return false;
        }
        // Note: we cannot use is_callable() here, because due to the presence of __call it will accept anything
        // support regular class method (case-insensitive) or single-method class in namespace (converted to PascalCase)
        return method_exists($this, $funcName) || class_exists($this->getClassName($funcName));
    }

    /**
     * Get parent module to access other module classes
     * @return TModule
     */
    public function getModule(?string $modName = null): ?ModuleInterface
    {
        if (!empty($modName)) {
            $module = $this->mod()->getModule($modName);
            return $module;
        }
        $this->parent ??= $this->mod()->getModule($this->getModName());
        if (!$this->parent->hasContext()) {
            $this->parent->setContext($this->context);
        }
        return $this->parent;
    }

    /**
     * Set parent module for this module class
     * @param TModule $parent
     */
    public function setModule(?ModuleInterface $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * Get module user API class for this module
     */
    public function userapi(): ?UserApiInterface
    {
        $component = $this->getModule()?->userapi();
        assert($component instanceof UserApiInterface);
        return $component;
    }

    /**
     * Get module user GUI class for this module
     */
    public function usergui(): ?UserGuiInterface
    {
        $component = $this->getModule()?->usergui();
        assert($component instanceof UserGuiInterface);
        return $component;
    }

    /**
     * Get module admin API class for this module
     */
    public function adminapi(): ?AdminApiInterface
    {
        $component = $this->getModule()?->adminapi();
        assert($component instanceof AdminApiInterface);
        return $component;
    }

    /**
     * Get module admin GUI class for this module
     */
    public function admingui(): ?AdminGuiInterface
    {
        $component = $this->getModule()?->admingui();
        assert($component instanceof AdminGuiInterface);
        return $component;
    }

    /**
     * Summary of __call
     * @param string $funcName
     * @param array<mixed> $arguments
     * @return mixed
     */
    public function __call(string $funcName, array $arguments = [])
    {
        $this->context?->tracePath($this::class . '::__call: ' . $funcName, $arguments);
        // call any single-method class that exists in the component namespace
        if (!array_key_exists($funcName, $this->methods)) {
            $className = $this->getClassName($funcName);
            if (class_exists($className)) {
                $this->methods[$funcName] = $this->getMethodClass($className);
            } else {
                // $utilapi = $this->utilapi();
                $classType = $this->getModule()?->getClassType($funcName);
                if (!empty($classType)) {
                    $this->methods[$funcName] = $this->getModule()->getComponent($classType);
                } else {
                    $this->methods[$funcName] = null;
                }
            }
        }
        if (!isset($this->methods[$funcName])) {
            return;
        }
        $this->methods[$funcName]->setContext($this->context);
        if (!empty($arguments)) {
            return $this->methods[$funcName]->__invoke(...$arguments);
        }
        return $this->methods[$funcName]->__invoke();
    }

    /**
     * Get single-method class for module function by class name
     * @see https://phpstan.org/blog/generics-by-examples
     * @template TMethodClass of MethodClassInterface<ModuleClassInterface>
     * @param class-string<TMethodClass> $className
     * @return TMethodClass
     */
    protected function getMethodClass(string $className): MethodClassInterface
    {
        return new $className($this->getModName(), $this->getItemType(), $this);
    }

    /**
     * Get single-method class name for module function with
     * conversion from snake_case to PascalCase . 'Method'
     * Example:
     * [$modName, $modType, $funcName] = ['myfancymodule', 'userapi', 'test_call'];
     * will become Xaraya\Modules\MyFancyModule\UserApi\TestCallMethod
     *
     * @param string $funcName
     * @return class-string<MethodClass<ModuleClassInterface>>
     */
    protected function getClassName(string $funcName): string
    {
        // this assumes that the method class uses the component name as namespace
        $methodName = str_replace('_', '', ucwords($funcName, '_'));
        // Xaraya\Modules\MyFancyModule\UserApi\GetMethod
        return $this->getNamespace() . '\\' . $methodName . 'Method';
    }

    /**
     * Summary of getNamespace
     * @return string
     */
    protected function getNamespace(): string
    {
        // Xaraya\Modules\MyFancyModule\UserApi
        return $this::class;
    }

    /**
     * Get name for this module in module class or method
     */
    public function getModName(): string
    {
        return $this->moduleName;
    }

    /**
     * Set name for this module in module class or method
     */
    public function setModName(string $modName): void
    {
        $this->moduleName = $modName;
    }

    /**
     * Get item type in module class or method
     */
    public function getItemType(): int
    {
        return $this->itemtype;
    }

    /**
     * Set item type in module class or method
     */
    public function setItemType(int $itemtype = 0): void
    {
        $this->itemtype = $itemtype;
    }

    /**
     * Dummy method for ModuleClassInterface extends ServicesInterface
     */
    public function getObject(): null
    {
        return null;
    }

    /**
     * Dummy method for ModuleClassInterface extends ServicesInterface
     */
    public function getProperty(): null
    {
        return null;
    }

    public function __serialize()
    {
        // reset methods for comparison - see SerializeServicesTest::testModuleClassTrait()
        $this->methods = [];
        // add any protected/private properties that are relevent here
        return [
            'moduleName' => $this->moduleName ?? null,
            'moduleType' => $this->moduleType ?? null,
            'itemtype'   => $this->itemtype,
            'parent'     => $this->getModule(),
            'context'    => $this->getContext(),
        ];
    }

    public function __unserialize($data)
    {
        foreach ($data as $name => $value) {
            $this->{$name} = $value;
        }
        // Reconnect to current static services
        $this->getStaticServices();
    }
}
