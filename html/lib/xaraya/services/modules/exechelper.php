<?php

/**
 * Modules Service Helper for Module Execution
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.3
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services\Modules;

use Xaraya\Context\Context;
use Xaraya\Context\ContextInterface;
use Xaraya\Modules\ModuleInterface;
use Xaraya\Services\ServiceClass;
use xarMod;
use FunctionNotFoundException;

/**
 * Modules Service Helper for Module Execution
 */
class ExecHelper extends ServiceClass
{
    public const SLICE = 'modules.exec';

    /** @param array<string, mixed> $args */
    public function apiFunc(string $modName, string $modType, string $funcName, array $args, ?Context $context = null): mixed
    {
        return xarMod::apiFunc($modName, $modType, $funcName, $args, $context);
    }

    public function apiLoad(string $modName, string $modType, ?Context $context = null): mixed
    {
        return xarMod::apiLoad($modName, $modType, xarMod::LOAD_ANYSTATE, $context);
    }

    /** @param array<string, mixed> $args */
    public function guiFunc(string $modName, string $modType, string $funcName, array $args, ?Context $context = null): mixed
    {
        return xarMod::guiFunc($modName, $modType, $funcName, $args, $context);
    }

    public function load(string $modName, string $modType, ?Context $context = null): mixed
    {
        return xarMod::load($modName, $modType, xarMod::LOAD_ONLYACTIVE, $context);
    }

    public function checkModuleFunction(string $tplmodule = 'dynamicdata', string $type = 'user', string $func = 'display', string $defaultmodule = 'dynamicdata'): string
    {
        return xarMod::checkModuleFunction($tplmodule, $type, $func, $defaultmodule);
    }

    public function getModule(string $modName, ?Context $context = null): ModuleInterface
    {
        return xarMod::getModule($modName, $context);
    }

    public function getModuleClassMethod(string $modName, string $modType, string $funcName, string $callType, ?Context $context = null): ?callable
    {
        return xarMod::getModuleClassMethod($modName, $modType, $funcName, $callType, $context);
    }

    /** @param array<string, mixed> $args */
    public function callMethod(callable $callable, array $args, ?Context $context = null): mixed
    {
        // this expects an instance in $callable[0]
        if (is_array($callable) && is_a($callable[0] ?? '', ContextInterface::class)) {
            $callable[0]->setContext($context);
        }
        return $callable($args);
    }
}
