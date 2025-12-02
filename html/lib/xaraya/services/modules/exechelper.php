<?php

/**
 * Modules Service Helper for Module Execution
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services\Modules;

use Xaraya\Context\ContextInterface;
use Xaraya\Modules\ModuleInterface;
use Xaraya\Modules\ModuleClassInterface;
use Xaraya\Services\ServiceClass;
use xarCore;
use ixarMod;
use xarClassMap;
use sys;
use EmptyParameterException;
use FunctionNotFoundException;
use ModuleNotActiveException;
use ModuleNotFoundException;
use Exception;
use Throwable;

/**
 * Modules Service Helper for Module Execution
 */
class ExecHelper extends ServiceClass
{
    public const SLICE = 'modules.exec';

    /** @var array<string, object> */
    private $moduleClasses = [];
    protected $loadedModuleCache = [];
    protected $checkFunctionCache = [];
    protected $getMethodCache = [];

    /** @param array<string, mixed> $args */
    public function apiFunc(string $modName, string $modType, string $funcName, array $args): mixed
    {
        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }
        // @todo call module api class method directly if available
        return $this->callfunc($modName, $modType, $funcName, $args, 'api');
    }

    public function apiLoad(string $modName, string $modType, int $flags = ixarMod::LOAD_ANYSTATE): mixed
    {
        return $this->privateLoad($modName, $modType . 'api', $flags);
    }

    /** @param array<string, mixed> $args */
    public function guiFunc(string $modName, string $modType, string $funcName, array $args): mixed
    {
        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }
        $xar = $this->getParent();

        // Get a cache key for this module function if it's suitable for module caching
        $cacheKey = $xar->cache()->getModuleKey($modName, $modType, $funcName, $args);

        // Check if the module function is cached
        if ($xar->cache()->hasModule($cacheKey)) {
            // Return the cached module function output
            return $xar->cache()->getModule($cacheKey);
        }
        $context = $this->getContext();
        // Set module name and type in context if needed
        $context['module'] ??= $modName;
        $context['modtype'] ??= $modType;
        // @todo call module gui class method directly if available
        $tplData = $this->callFunc($modName, $modType, $funcName, $args, '');
        // If we have a string of data, we assume someone else did xarTpl* for us
        if (!is_array($tplData)) {
            if (!isset($tplData)) {
                $tplData = '';
            }
            // Set the output of the module function in cache
            $xar->cache()->setModule($cacheKey, $tplData);
            return $tplData;
        }

        // See if we have a special template to apply
        $templateName = null;
        if (isset($tplData['_bl_template'])) {
            $templateName = $tplData['_bl_template'];
        }

        // @todo Pass along the context for xar::tpl()->module() if needed
        $tplData['context'] ??= $this->getContext();

        // Create the output.
        $tplOutput = $xar->tpl()->module($modName, $modType, $funcName, $tplData, $templateName);

        // Set the output of the module function in cache
        $xar->cache()->setModule($cacheKey, $tplOutput);

        return $tplOutput;
    }

    public function load(string $modName, string $modType, $flags = ixarMod::LOAD_ONLYACTIVE): mixed
    {
        return $this->privateLoad($modName, $modType, $flags);
    }

    protected function callFunc($modName, $modType, $funcName, $args, $funcType = '')
    {
        assert(($funcType == "api" || $funcType == ""));
        $xar = $this->getParent();

        // Build function name
        $modFunc = "{$modName}_{$modType}{$funcType}_{$funcName}";
        if (empty($modName) || empty($funcName)) {
            // This is not a valid function syntax - CHECKME: also for api functions ?
            if ($funcType == "api") {
                throw new FunctionNotFoundException($modFunc);
            } else {
                return $xar->ctl()->notFound('Function not found');
            }
        }

        $info = $xar->mod()->getInfoHelper();

        // good thing this information is cached :)
        $modFileInfo = $info->getFileInfo($modName);
        if (empty($modFileInfo)) {
            // This is not a valid module - CHECKME: also for api functions ?
            if ($funcType == "api") {
                throw new FunctionNotFoundException($modFunc);
            } else {
                return $xar->ctl()->notFound('Function not found');
            }
        }

        // Call function
        $found = true;
        $isLoaded = true;
        $msg = '';
        if (!function_exists($modFunc)) {
            // attempt to load the module's api - this will load xaruserapi.php or xaruser.php etc. if they exist
            if ($funcType == 'api') {
                $this->apiLoad($modName, $modType);
            } else {
                try {
                    $this->load($modName, $modType);
                } catch (Exception $e) {
                    return $xar->ctl()->notFound('Function not found');
                }
            }
            $xar = $this->getParent();

            $xar->log()->info("xar::mod()->callFunc: Calling $modFunc");

            // let's check for that function again to be sure
            if (!function_exists($modFunc)) {
                // Q: who are we kidding with this? directory == modName always, no?
                $funcFile = sys::code() . 'modules/' . $modFileInfo['directory'] . '/xar' . $modType . $funcType . '/' . strtolower($funcName) . '.php';
                if (!file_exists($funcFile)) {
                    // @todo cache this if we ever get here again? Already cached internally for module class methods
                    // Note: pass modType . funcType as modType here for module classes, and use funcType to identify the callType (api or not)
                    $callable = $this->getModuleClassMethod($modName, $modType . $funcType, $funcName, $funcType);
                    if (!empty($callable)) {
                        return $this->callMethod($callable, $args);
                    }
                    // Valid syntax, but the function doesn't exist
                    if ($funcType == "api") {
                        throw new FunctionNotFoundException($modFunc);
                    } else {
                        return $xar->ctl()->notFound('Function not found');
                    }
                } else {
                    ob_start();
                    $r = sys::import('modules.' . $modName . '.xar' . $modType . $funcType . '.' . strtolower($funcName));
                    $error_msg = strip_tags(ob_get_contents());
                    ob_end_clean();

                    if (empty($r) || !$r) {
                        $msg = "Could not load function file: [#(1)].\n\n Error Caught:\n #(2)";
                        $params = [$funcFile, $error_msg];
                        $isLoaded = false;
                    }
                    if (!function_exists($modFunc)) {
                        $found = false;
                    }
                }
            }

            if ($found) {
                // Load the translations file, only if we have loaded the API function for the first time here.
                if ($xar->mls()->loadModuleTranslations($modName, $modType . $funcType, $funcName) === null) {
                    return;
                }
            }
        }

        if (!$found) {
            return $xar->ctl()->notFound('Function not found');
        }
        $this->getContext()?->tracePath(__METHOD__ . ': ' . $modFunc, $args);

        $funcResult = $modFunc($args, $this->getContext());
        return $funcResult;
    }

    protected function privateLoad($modName, $modType, $flags = 0)
    {
        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }

        // Make sure we access the cache with lower case key, return true when we already loaded
        $cacheKey = strtolower($modName . $modType);
        if (isset($this->loadedModuleCache[$cacheKey])) {
            return true;
        }
        $xar = $this->getParent();

        // Log it when it doesn't come from the cache
        $xar->log()->debug("xar::mod()->load: Loading $modName:$modType");

        $info = $xar->mod()->getInfoHelper();

        // allow inactive/non-upgraded modules in any state
        if ($flags & ixarMod::LOAD_ANYSTATE) {
            $modBaseInfo = $info->getFileInfo($modName);
        } else {
            $modBaseInfo = $info->getBaseInfo($modName);
        }
        // Not a valid module - throw exception
        if (empty($modBaseInfo)) {
            throw new ModuleNotFoundException($modName);
        }

        // Not a valid module state - throw exception
        if (!($flags & ixarMod::LOAD_ANYSTATE) && $modBaseInfo['state'] != ixarMod::STATE_ACTIVE) {
            throw new ModuleNotActiveException($modName);
        }

        // Not the correct version - throw exception unless we are upgrading
        if (!$info->checkVersion($modName) && !$xar->mem()->get('Upgrade', 'upgrading') && $modName != 'modules') {
            xarCore::exit('The core module "' . $modName . '" does not have the correct version. Please run the upgrade routine by clicking <a href="upgrade.php">here</a>');
            return false;
        }

        // Load the module files
        $modDir = $modBaseInfo['directory'];
        $fileName = sys::code() . 'modules/' . $modDir . '/xar' . $modType . '.php';

        // Assume failure
        if (file_exists($fileName)) {
            sys::import('modules.' . $modDir . '.xar' . $modType);
            $this->loadedModuleCache[$cacheKey] = true;
        } elseif (is_dir(sys::code() . 'modules/' . $modDir . '/xar' . $modType)) {
            // this is OK too - do nothing
            $this->loadedModuleCache[$cacheKey] = true;
        } else {
            // Do we have a module class handling this modType
            $instance = $this->getModule($modName);
            // returns null for DefaultModule() = no suitable class type
            $classType = $instance->getClassType($modType);
            if (isset($classType)) {
                // this is OK too - do nothing
                $this->loadedModuleCache[$cacheKey] = true;
            } else {
                // this is (not really) OK too - do nothing
                $this->loadedModuleCache[$cacheKey] = false;
                $xar->log()->info("xar::mod()->load: Loading $modName:$modType FAILED");
            }
        }

        // Load the module translations files (common functions, uncut functions etc.)
        if ($xar->mls()->loadModuleTranslations($modName, '', $modType) === null) {
            return;
        }

        $info = $xar->mod()->getInfoHelper();

        // Load database info
        $info->loadDbInfo($modName, $modDir);

        // Module loaded successfully, trigger the proper event
        if (preg_match('/(.*)?api$/', $modType)) {
            $xar->events()->notify('ModApiLoad', $modName, $this->getContext(), $xar);
        } else {
            $xar->events()->notify('ModLoad', $modName, $this->getContext(), $xar);
        }
        return true;
    }

    public function userapi(string $modName)
    {
        return $this->getModule($modName)->userapi();
    }

    public function usergui(string $modName)
    {
        return $this->getModule($modName)->usergui();
    }

    public function checkModuleFunction(string $tplmodule = 'dynamicdata', string $type = 'user', string $func = 'display', string $defaultmodule = 'dynamicdata'): string
    {
        $key = "$tplmodule:$type:$func";
        if (!isset($this->checkFunctionCache[$key])) {
            $file = sys::code() . 'modules/' . $tplmodule . '/xar' . $type . '/' . $func . '.php';
            if (file_exists($file)) {
                $this->checkFunctionCache[$key] = $tplmodule;
                return $this->checkFunctionCache[$key];
            }
            // Note: pass modType . funcType as modType here for module classes, and use callType (api or not)
            if (str_ends_with($type, 'api')) {
                $callType = 'api';
            } else {
                $callType = 'gui';
                // make sure configure() adds 'type' as well as 'typegui' to call types
                //$type .= 'gui';
            }
            // Note: component would use configure() with no context here
            $callable = $this->getModuleClassMethod($tplmodule, $type, $func, $callType);
            if (!empty($callable)) {
                $this->checkFunctionCache[$key] = $tplmodule;
            } else {
                $this->checkFunctionCache[$key] = $defaultmodule;
            }
        }
        return $this->checkFunctionCache[$key];
    }

    public function getModule(string $modName): ModuleInterface
    {
        if (!array_key_exists($modName, $this->moduleClasses)) {
            $result = xarClassMap::findModuleClass($modName);
            if (!empty($result) && class_exists($result['classname'])) {
                $class = $result['classname'];
                try {
                    $this->moduleClasses[$modName] = new $class($modName, $this->getContext(), $this->getParent());
                } catch (Throwable $e) {
                    $this->moduleClasses[$modName] = new \Xaraya\Modules\DefaultModule($modName, $this->getContext());
                    $xar = $this->getParent();
                    $xar->log()->warning("xar::mod()->getModule: Error loading $class for module $modName");
                }
            } else {
                $this->moduleClasses[$modName] = new \Xaraya\Modules\DefaultModule($modName, $this->getContext());
            }
        } else {
            $this->moduleClasses[$modName]->setContext($this->getContext());
        }
        return $this->moduleClasses[$modName];
    }

    public function getModuleClass(string $modName, string $modType): ?ModuleClassInterface
    {
        $module = $this->getModule($modName);
        $classType = $module->getClassType($modType);
        if (empty($classType)) {
            return null;
        }
        return $module->getComponent($classType);
    }

    public function getModuleClassMethod(string $modName, string $modType, string $funcName, string $callType): ?callable
    {
        $key = "$modName:$modType:$funcName:$callType";
        if (!array_key_exists($key, $this->getMethodCache)) {
            $xar = $this->getParent();
            $instance = $this->getModule($modName);
            // returns null for DefaultModule() = no suitable class method
            $this->getMethodCache[$key] = $instance->getCallableMethod($modType, $funcName, $callType);
            if (!isset($this->getMethodCache[$key])) {
                $xar->log()->info("xar::mod()->getModuleClassMethod: Missing method for $key");
            } else {
                // Load the translations file, only if we have loaded the function for the first time here.
                $xar->mls()->loadModuleTranslations($modName, $modType, $funcName);
            }
        }
        return $this->getMethodCache[$key];
    }

    /** @param array<string, mixed> $args */
    public function apiMethod(string $modName, string $modType, string $funcName, array $args): mixed
    {
        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }
        $callable = $this->getModuleClassMethod($modName, $modType, $funcName, 'api');
        if (empty($callable)) {
            throw new FunctionNotFoundException($funcName);
        }
        return $this->callMethod($callable, $args);
    }

    /** @param array<string, mixed> $args */
    public function guiMethod(string $modName, string $modType, string $funcName, array $args): mixed
    {
        if (empty($modName)) {
            throw new EmptyParameterException('modName');
        }
        $callable = $this->getModuleClassMethod($modName, $modType, $funcName, 'gui');
        if (empty($callable)) {
            throw new FunctionNotFoundException($funcName);
        }
        return $this->callMethod($callable, $args);
    }

    /** @param array<string, mixed> $args */
    public function callMethod(callable $callable, array $args): mixed
    {
        // this expects an instance in $callable[0]
        if (is_array($callable) && is_a($callable[0] ?? '', ContextInterface::class)) {
            $this->getContext()?->tracePath($callable[0]::class . '::' . $callable[1], $args);
            $callable[0]->setContext($this->getContext());
        }
        return $callable($args);
    }
}
