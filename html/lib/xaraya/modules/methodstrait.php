<?php

/**
 * Handle module functions as methods
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
use sys;

sys::import('xaraya.modules.coretrait');
sys::import('xaraya.modules.hookstrait');

/**
 * For documentation purposes only - available via MethodsTrait
 */
interface MethodsInterface extends ContextInterface, CoreInterface, HooksInterface
{
    public function __construct(string $moduleName, ?ModuleInterface $parent = null);
    /** @return void */
    public function configure();
    public function hasMethod(string $funcName, string $callType = 'api'): bool;
    public function getModule(): ModuleInterface|null;
}

/**
 * Module class supports api methods
 */
interface ApiMethodsInterface extends MethodsInterface
{
    // ...
}

/**
 * Module class supports gui methods
 */
interface GuiMethodsInterface extends MethodsInterface
{
    // ...
}

/**
 * Trait to handle module functions as methods
 * @see https://phpstan.org/blog/generics-in-php-using-phpdocs
 * @template TModule of ModuleInterface|null
 */
trait MethodsTrait
{
    use ContextTrait;
    use CoreTrait;
    use HooksTrait;

    protected string $moduleName;          // set in constructor by ModuleTrait::createComponent()
    protected int $itemtype = 0;
    protected ?ModuleInterface $parent;

    /** @var array<string> */
    protected array $allowed = [];
    /** @var array<string> */
    protected array $internal = [
        // ContextTrait
        'getcontext',
        'setcontext',
        // CoreTrait
        'checkaccess',
        'getapi',
        'getmoduleid',
        'getvar',
        'fetchvar',
        'genauthkey',
        'confirmauthkey',
        // HooksTrait
        'callhooks',
        'notifyhooks',
        // MethodsTrait
        'configure',
        'hasmethod',
        'getmodule',
        'getmethodclass',
        'getclassname',
        'getnamespace',
        // UserGuiTrait
        'prepareoutput',
        'rendertemplate',
        // @todo add new internal methods here + find a better way to do this
    ];
    /** @var array<string, MethodInterface|null> */
    private array $methods = [];

    /**
     * Summary of __construct
     * @param string $moduleName
     * @param TModule $parent
     */
    public function __construct(string $moduleName, ?ModuleInterface $parent = null)
    {
        $this->moduleName = $moduleName;
        $this->parent = $parent;
        $this->configure();
    }

    /**
     * Summary of configure
     * @return void
     */
    public function configure()
    {
        // ...
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
        if ($callType != 'api' && $this instanceof ApiMethodsInterface) {
            return false;
        }
        // Note: non-api methods can still be called as api functions here if needed
        //if ($callType == 'api' && !($this instanceof ApiMethodsInterface)) {
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
     * Summary of getModule
     * @return TModule
     */
    public function getModule(): ModuleInterface|null
    {
        return $this->parent;
    }

    /**
     * Summary of __call
     * @param string $funcName
     * @param array<mixed> $arguments
     * @return mixed
     */
    public function __call(string $funcName, array $arguments = [])
    {
        // call any single-method class that exists in the component namespace
        if (!array_key_exists($funcName, $this->methods)) {
            $className = $this->getClassName($funcName);
            if (class_exists($className)) {
                $this->methods[$funcName] = $this->getMethodClass($className);
            } else {
                $this->methods[$funcName] = null;
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
     * @template TMethodClass of MethodInterface
     * @param class-string<TMethodClass> $className
     * @return TMethodClass
     */
    protected function getMethodClass(string $className): MethodInterface
    {
        return new $className($this->moduleName, $this->itemtype, $this);
    }

    /**
     * Get single-method class name for module function with
     * conversion from snake_case to PascalCase . 'Method'
     * Example:
     * [$modName, $modType, $funcName] = ['myfancymodule', 'userapi', 'test_call'];
     * will become Xaraya\Modules\MyFancyModule\UserApi\TestCallMethod
     *
     * @param string $funcName
     * @return class-string<MethodClass<static>>
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
}
