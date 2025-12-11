<?php

/**
 * Legacy module class without any components or methods
 *
 * @package core\modules
 * @subpackage modules
 * @category Xaraya Web Applications Framework
 * @version 2.9.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Modules;

use sys;
use Exception;
use FunctionNotFoundException;

/**
 * Class to handle legacy module functions as methods
 * @todo replace fallback methods in InfoHelper/ExecHelper/... someday
 * @see \Xaraya\Services\Modules\ExecHelper()
 */
class LegacyModuleClass implements ModuleClassInterface, UserApiInterface, UserGuiInterface, AdminApiInterface, AdminGuiInterface
{
    /** @use ModuleClassTrait<LegacyModule> */
    use ModuleClassTrait;

    protected $hasMethodCache = [];

    public function configure()
    {
        // we don't know modType in __construct yet, so we don't know what to load here (if anything)
    }

    /**
     * Summary of main - not called if $this->getModFunc() returns existing function
     * @param array<string, mixed> $args
     * @return array<mixed>|string|void
     */
    public function main(array $args = [])
    {
        if ($this->hasMethod('main', 'gui')) {
            // call module main function
            $callable = $this->getModFunc('main');
            if (!empty($callable)) {
                return $callable($args);
            }
        }
        return [
            'method' => __METHOD__,
            'module' => $this->getModName(),
            'type' => $this->getModType(),
            'func' => 'main',
            'args' => $args,
            //'context' => $this->getContext(),
        ];
    }

    /**
     * Build function name
     */
    public function getModFunc($funcName)
    {
        $modName = $this->getModName();
        $modType = $this->getModType();
        // convert camelCase to snake_case for legacy module functions
        if (preg_match('/[A-Z]/', $funcName) && !str_contains($funcName, '_')) {
            $funcName = preg_replace('/([A-Z]+)/', '_$1', $funcName);
        }
        $modFunc = strtolower("{$modName}_{$modType}_{$funcName}");
        return $modFunc;
    }

    public function hasMethod(string $funcName, string $callType = 'api'): bool
    {
        // restrict any internal _* methods (including magic methods)
        if (str_starts_with($funcName, '_')) {
            return false;
        }
        // find function - see callFunc()
        $modName = $this->getModName();
        $modType = $this->getModType();
        $modFunc = $this->getModFunc($funcName);
        if (isset($this->hasMethodCache[$modFunc])) {
            return $this->hasMethodCache[$modFunc];
        }
        if (function_exists($modFunc)) {
            $this->hasMethodCache[$modFunc] = true;
            return true;
        }
        // attempt to load the module's api - this will load xaruserapi.php or xaruser.php etc. if they exist
        if (!$this->loadModType($callType)) {
            $this->hasMethodCache[$modFunc] = false;
            return false;
        }
        // let's check for that function again to be sure
        if (function_exists($modFunc)) {
            $this->hasMethodCache[$modFunc] = true;
            return true;
        }
        // don't include $callType after $modType here
        $funcFile = sys::code() . 'modules/' . $modName . '/xar' . $modType . '/' . strtolower($funcName) . '.php';
        if (!file_exists($funcFile)) {
            $this->hasMethodCache[$modFunc] = false;
            return false;
        }
        // don't include $callType after $modType here
        ob_start();
        $r = sys::import('modules.' . $modName . '.xar' . $modType . '.' . strtolower($funcName));
        $error_msg = strip_tags(ob_get_contents());
        ob_end_clean();

        if (empty($r)) {
            // $msg = "Could not load function file: [#(1)].\n\n Error Caught:\n #(2)";
        }
        if (!function_exists($modFunc)) {
            $this->hasMethodCache[$modFunc] = false;
            return false;
        }
        $this->hasMethodCache[$modFunc] = true;
        return true;
    }

    public function getMethod(string $funcName, string $callType = 'api'): ?callable
    {
        // @todo should we check $callType on component level or method level - do we allow mix of both in class?
        if ($this->hasMethod($funcName, $callType)) {
            // Build function name
            $modFunc = $this->getModFunc($funcName);
            // use function name as callable here
            if (is_callable($modFunc)) {
                //return [$component, $funcName];
                return $modFunc;
            }
        }
        return null;
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
        $modFunc = $this->getModFunc($funcName);
        if (str_ends_with($this->getModType(), 'api')) {
            $callType = 'api';
        } else {
            $callType = '';
        }
        // call any module function that exists and is loaded by hasMethod()
        if (!array_key_exists($modFunc, $this->methods)) {
            if ($this->hasMethod($funcName, $callType)) {
                if (function_exists($modFunc)) {
                    $this->methods[$modFunc] = $modFunc;
                } else {
                    $this->methods[$modFunc] = null;
                    throw new FunctionNotFoundException($modFunc);
                }
            } else {
                $this->methods[$modFunc] = null;
                throw new FunctionNotFoundException($modFunc);
            }
        }
        if (!isset($this->methods[$modFunc])) {
            return;
        }
        // @todo pass along $this->getContext() as second argument here too?
        if (!empty($arguments)) {
            try {
                return $this->methods[$modFunc](...$arguments);
            } catch (Exception $e) {
                var_dump($e);
                return 'oops';
            }
        }
        try {
            return $this->methods[$modFunc]();
        } catch (Exception $e) {
            var_dump($e);
            return 'oops2';
        }
    }
}
