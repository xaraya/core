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

use Xaraya\Services\Modules\ExecHelper;
use sys;
use Exception;

/**
 * Class to handle legacy module functions as methods
 * @todo replace fallback methods in InfoHelper/ExecHelper/... someday
 * @see \Xaraya\Services\Modules\ExecHelper()
 */
class LegacyModuleClass implements ModuleClassInterface, UserApiInterface, UserGuiInterface, AdminApiInterface, AdminGuiInterface
{
    /** @use ModuleClassTrait<LegacyModule> */
    use ModuleClassTrait;

    protected $loadedModuleCache = [];
    protected $hasMethodCache = [];

    public function configure()
    {
        // we don't know modType in __construct yet, so we don't know what to load here (if anything)
    }

    /**
     * Summary of main
     * @param array<string, mixed> $args
     * @return array<mixed>|string|void
     */
    public function main(array $args = [])
    {
        if ($this->hasMethod('main', 'gui')) {
            // @todo call module main function
            $callable = $this->getModule()->getCallableMethod($this->getModType(), 'main');
            if (!empty($callable)) {
                return $callable($args);
            }
        }
        $output = [
            'method' => __METHOD__,
            'args' => $args,
        ];
        return $this->mod()->prepare($output);
    }

    /**
     * Build function name
     */
    public function getModFunc($funcName)
    {
        $modName = $this->getModName();
        $modType = $this->getModType();
        $modFunc = "{$modName}_{$modType}_{$funcName}";
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
        if (!function_exists($modFunc)) {
            // attempt to load the module's api - this will load xaruserapi.php or xaruser.php etc. if they exist
            if (!$this->privateLoad($modName, $modType, $callType)) {
                $this->hasMethodCache[$modFunc] = false;
                return false;
            }
            // let's check for that function again to be sure
            if (!function_exists($modFunc)) {
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

                if (!function_exists($modFunc)) {
                    $this->hasMethodCache[$modFunc] = false;
                    return false;
                }
            }
        }
        $this->hasMethodCache[$modFunc] = true;
        return true;
    }

    /**
     * Summary of privateLoad
     * @todo replace with ModuleClassTrait method
     * @param mixed $modName
     * @param mixed $modType
     * @param mixed $callType
     * @return bool
     */
    protected function privateLoad($modName, $modType, $callType)
    {
        // Make sure we access the cache with lower case key, return true when we already loaded
        $cacheKey = strtolower($modName . ':' . $modType . $callType);
        if (isset($this->loadedModuleCache[$cacheKey])) {
            return $this->loadedModuleCache[$cacheKey];
        }
        /** @var ExecHelper $exec */
        $exec = $this->getStaticServices()->service('modules.exec');

        $loaded = false;
        if ($callType == 'api') {
            if (str_ends_with($modType, $callType)) {
                $exec->apiLoad($modName, substr($modType, 0, -3));
            } else {
                $exec->apiLoad($modName, $modType);
            }
            $loaded = true;
        } else {
            try {
                $exec->load($modName, $modType);
                $loaded = true;
            } catch (Exception $e) {
                $loaded = false;
            }
        }

        $this->loadedModuleCache[$cacheKey] = $loaded;
        if (!$loaded) {
            return $this->loadedModuleCache[$cacheKey];
        }

        // Load the module translations files (common functions, uncut functions etc.)
        if ($this->mls()->loadModuleTranslations($modName, '', $modType) === null) {
            return;
        }

        // Load database info
        $this->getModule()->loadDbInfo();

        // Module loaded successfully, trigger the proper event
        if (preg_match('/(.*)?api$/', $modType)) {
            $this->events()->notify('ModApiLoad', $modName);
        } else {
            $this->events()->notify('ModLoad', $modName);
        }
        return $this->loadedModuleCache[$cacheKey];
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
                }
            } else {
                $this->methods[$modFunc] = null;
            }
        }
        if (!isset($this->methods[$modFunc])) {
            return;
        }
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
