<?php

/**
 * Handle module functions as methods
 *
 * @package core\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.5.5
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
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.hookstrait');

/**
 * For documentation purposes only - available via MethodsTrait
 */
interface MethodsInterface extends ContextInterface, HooksInterface
{
    public function hasMethod(string $funcName): bool;
}

/**
 * Trait to handle module functions as methods
 */
trait MethodsTrait
{
    use ContextTrait;
    use HooksTrait;

    /** @var array<string, object|null> */
    private array $methods = [];

    public function hasMethod(string $funcName): bool
    {
        // support regular class method or single-method class in namespace
        return method_exists($this, $funcName) || class_exists($this->getClassName($funcName));
    }

    public function __call(string $funcName, array $arguments = [])
    {
        if (!array_key_exists($funcName, $this->methods)) {
            $className = $this->getClassName($funcName);
            if (class_exists($className)) {
                $this->methods[$funcName] = new $className($this->moduleName);
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
     * Get single-method class name for module function with
     * conversion from snake_case to PascalCase . 'Method'
     * Example:
     * [$modName, $modType, $funcName] = ['myfancymodule', 'userapi', 'test_call'];
     * will become Xaraya\Modules\MyFancyModule\UserApi\TestCallMethod
     *
     * @param string $funcName
     * @return string
     */
    protected function getClassName(string $funcName): string
    {
        // this assumes that the method class uses the component name as namespace
        $methodName = str_replace('_', '', ucwords($funcName, '_'));
        // Xaraya\Modules\MyFancyModule\UserApi\GetMethod
        return $this->getNamespace() . '\\' . $methodName . 'Method';
    }

    protected function getNamespace(): string
    {
        // Xaraya\Modules\MyFancyModule\UserApi
        return $this::class;
    }

    /** Wrap some frequently used static method calls here */

    protected function checkAccess(string $mask, string $action = ''): bool
    {
        if (empty($mask) && !empty($action)) {
            return xarMod::checkAccess($this->moduleName, $action) ? true : false;
        }
        // @todo use $action for something here, and/or pass moduleName?
        return xarSecurity::check($mask) ? true : false;
    }

    protected function fetchVar($name, $validation, &$value, $defaultValue = null, $flags = xarVar::GET_OR_POST, $prep = xarVar::PREP_FOR_NOTHING)
    {
        return xarVar::fetch($name, $validation, $value, $defaultValue, $flags, $prep);
    }
}
