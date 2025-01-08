<?php

/**
 * Core functions available via methods (WIP)
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

use BadParameterException;
use xarConfigVars;
use xarController;
use xarMod;
use xarModVars;
use xarSec;
use xarSecurity;
use xarSession;
use xarUser;
use xarVar;

/**
 * For documentation purposes only - available via CoreTrait
 */
interface CoreInterface
{
    public function checkAccess(string $mask, string $action = ''): bool;
    public function getVar($name, $scope = 'module');
    public function fetchVar($name, $validation, &$value, $defaultValue = null, $flags = xarVar::GET_OR_POST, $prep = xarVar::PREP_FOR_NOTHING);
    public function genAuthKey(): string;
    public function confirmAuthKey(string $name = 'authid'): bool;
}

/**
 * Core functions available via methods
 */
trait CoreTrait
{
    public function checkAccess(string $mask, string $action = ''): bool
    {
        if (empty($mask) && !empty($action)) {
            return xarMod::checkAccess($this->moduleName, $action) ? true : false;
        }
        // @todo use $action for something here, and/or pass moduleName?
        return xarSecurity::check($mask) ? true : false;
    }

    public function getVar($name, $scope = 'module')
    {
        return match ($scope) {
            //'local' => $name,
            'module' => xarModVars::get($this->moduleName, $name),
            'user' => xarUser::getVar($name),
            'config' => xarConfigVars::get(null, $name),
            'session' => xarSession::getVar($name),
            'request' => xarController::getVar($name),
            default => throw new BadParameterException([$scope], 'Unknown scope #(1)'),
        };
    }

    public function fetchVar($name, $validation, &$value, $defaultValue = null, $flags = xarVar::GET_OR_POST, $prep = xarVar::PREP_FOR_NOTHING)
    {
        // Note: this should be restricted to GuiMethodsInterface
        return xarVar::fetch($name, $validation, $value, $defaultValue, $flags, $prep);
    }

    public function genAuthKey(): string
    {
        // Note: this should be restricted to GuiMethodsInterface
        return xarSec::genAuthKey($this->moduleName);
    }

    public function confirmAuthKey(string $name = 'authid'): bool
    {
        // Note: this should be restricted to GuiMethodsInterface
        return xarSec::confirmAuthKey($this->moduleName, $name);
    }
}
