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

use Xaraya\Context\ContextInterface;
use Xaraya\Context\ContextTrait;
use xarConfigVars;
use xarController;
use xarMLS;
use xarMod;
use xarModVars;
use xarSec;
use xarSecurity;
use xarSession;
use xarUser;
use xarVar;
use sys;
use BadParameterException;

sys::import('xaraya.context.contexttrait');

/**
 * For documentation purposes only - available via CoreTrait
 */
interface CoreInterface extends ContextInterface
{
    public function checkAccess(string $mask, string|int $action = '', mixed $instance = null): bool;
    public function getModName(): string;
    public function setModName(string $modName): void;
    public function getItemType(): int;
    public function setItemType(int $itemtype = 0): void;
    public function getModId(): int;
    public function getModVar(string $varName): mixed;
    public function setModVar(string $varName, mixed $value): bool;
    //public function getVar(string $name, string $scope = 'module'): mixed;
    /**
     * Summary of fetchVar
     * @param string $name the variable name
     * @param string $validation the validation to be performed
     * @param mixed $value contains the converted value of fetched variable
     * @param mixed $defaultValue the default value
     * @param integer $flags bitmask which modify the behaviour of function
     * @param integer $prep will prep the value with xarVarPrepForDisplay, xarVarPrepHTMLDisplay, or dbconn->qstr()
     * @return mixed
     */
    public function fetch($name, $validation, &$value, $defaultValue = null, $flags = xarVar::GET_OR_POST, $prep = xarVar::PREP_FOR_NOTHING): mixed;
    public function genAuthKey(): string;
    public function confirmAuthKey(string $name = 'authid'): bool;
    /** @param array<mixed> $args */
    public function getUrl(string $modType = 'user', string $funcName = 'main', array $args = []): string;
    /** @return bool|never */
    public function redirect(string $url, ?int $httpResponse = null);
    /**
     * Summary of translate
     * @param string $rawstring
     * @param mixed ...$args
     * @return string
     */
    public function translate($rawstring, ...$args): string;
    /** @return void|never */
    public function exit(int|string $status = 0);
}

/**
 * Core functions available via methods
 */
trait CoreTrait
{
    use ContextTrait;

    /**
     * Check access based on security mask or module action
     */
    public function checkAccess(string $mask, string|int $action = '', mixed $instance = null): bool
    {
        // if the mask is empty, use xarMod::checkAccess() - currently not used
        if (empty($mask) && !empty($action) && is_string($action)) {
            return xarMod::checkAccess($this->getModName(), $action) ? true : false;
        }
        // @todo mainly legacy hook module - remove 2nd argument in call later?
        if (is_int($action) && $action === 0) {
            // we don't want to redirect here
            return $this->callSecurityCheck($mask, 0);
        }
        // xarSecurity::check('ReadHitcountItem', 1, 'Item', "$modname:$itemtype:$objectid") etc.
        //if (is_string($action)) {
        //    // @todo how do we deal with $catch here? we have both 0 and 1 in modules
        //    return $this->callSecurityCheck($mask, 1, $action, $instance);
        //}
        return $this->callSecurityCheck($mask);
    }

    /**
     * Call xarSecurity::check()
     * @param string $mask
     * @param int $catch
     * @return bool|never
     */
    protected function callSecurityCheck(string $mask, int $catch = 1, string $component = '', string $instance = '')
    {
        // @todo handle redirect() + exit() in case of failure
        return xarSecurity::check($mask, $catch, $component, $instance) ? true : false;
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
     * Get item type in this module class
     */
    public function getItemType(): int
    {
        return $this->itemtype;
    }

    /**
     * Set item type in this module class
     */
    public function setItemType(int $itemtype = 0): void
    {
        $this->itemtype = $itemtype;
    }

    /**
     * Get module registry ID for this module
     */
    public function getModId(): int
    {
        // avoid getting module id from xarMod::getRegID() here
        //return xarMod::getRegId($this->getModName());
        $fileInfo = xarMod::getFileInfo($this->getModName());
        return $fileInfo['regid'];
    }

    /**
     * Get module variable for this module
     */
    public function getModVar(string $varName): mixed
    {
        return xarModVars::get($this->getModName(), $varName);
    }

    /**
     * Set module variable for this module
     */
    public function setModVar(string $varName, mixed $value): bool
    {
        return xarModVars::set($this->getModName(), $varName, $value);
    }

    /**
     * Fetch variable by name, with validation, default, flags and prep
     * @param string $name the variable name
     * @param string $validation the validation to be performed
     * @param mixed $value contains the converted value of fetched variable
     * @param mixed $defaultValue the default value
     * @param integer $flags bitmask which modify the behaviour of function
     * @param integer $prep will prep the value with xarVarPrepForDisplay, xarVarPrepHTMLDisplay, or dbconn->qstr()
     * @return mixed
     */
    public function fetch($name, $validation, &$value, $defaultValue = null, $flags = xarVar::GET_OR_POST, $prep = xarVar::PREP_FOR_NOTHING): mixed
    {
        // Note: this should be restricted to GuiMethodsInterface
        return xarVar::fetch($name, $validation, $value, $defaultValue, $flags, $prep);
    }

    /**
     * Generate authorisation key for this module
     */
    public function genAuthKey(): string
    {
        // Note: this should be restricted to GuiMethodsInterface
        return xarSec::genAuthKey($this->getModName());
    }

    /**
     * Confirm authorisation key for this module
     */
    public function confirmAuthKey(string $name = 'authid'): bool
    {
        // Note: this should be restricted to GuiMethodsInterface
        return xarSec::confirmAuthKey($this->getModName(), $name);
    }

    /**
     * Get url for this module type function
     * @param array<mixed> $args
     */
    public function getUrl(string $modType = 'user', string $funcName = 'main', array $args = []): string
    {
        return xarController::URL($this->getModName(), $modType, $funcName, $args);
    }

    /**
     * Send redirect to url and exit
     * @return bool|never
     */
    public function redirect(string $url, ?int $httpResponse = null)
    {
        return xarController::redirect($url, $httpResponse, $this->getContext());
    }

    /**
     * Translate string with optional arguments
     * @param string $rawstring
     * @param mixed ...$args
     */
    public function translate($rawstring, ...$args): string
    {
        return xarMLS::translate($rawstring, ...$args);
    }

    /**
     * Call exit() - override for non-blocking servers, php unit tests or elsewhere
     * @return void|never
     */
    public function exit(int|string $status = 0)
    {
        exit($status);
    }
}
