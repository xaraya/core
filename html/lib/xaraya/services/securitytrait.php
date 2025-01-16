<?php

/**
 * Security available via methods (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.6.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use xarMod;
use xarSec;
use xarSecurity;
use sys;

sys::import('xaraya.services.servicetrait');

/**
 * For documentation purposes only - available via SecurityTrait
 */
interface SecurityInterface extends ServiceInterface
{
    /**
     * Check access based on security mask or module action
     */
    public function checkAccess(string $mask, string|int $action = '', mixed $instance = null): bool;

    /**
     * Generate authorisation key for this module
     */
    public function genAuthKey(): string;

    /**
     * Confirm authorisation key for this module
     */
    public function confirmAuthKey(string $name = 'authid'): bool;
}

/**
 * Security available via methods
 * @template TParent of ServicesInterface
 */
trait SecurityTrait
{
    /** @use ServiceTrait<TParent> */
    use ServiceTrait;

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
     * Generate authorisation key for this module
     */
    public function genAuthKey(): string
    {
        // Note: this should be restricted to gui methods
        return xarSec::genAuthKey($this->getModName());
    }

    /**
     * Confirm authorisation key for this module
     */
    public function confirmAuthKey(string $name = 'authid'): bool
    {
        // Note: this should be restricted to gui methods
        return xarSec::confirmAuthKey($this->getModName(), $name);
    }
}

/**
 * Access xarSec::* Security methods (checkAccess, genAuthKey, ...)
 *
 * Available methods:
 * - checkAccess()
 * - genAuthKey()
 * - confirmAuthKey()
 * - ...
 *
 * Required methods in parent:
 * - getModName()
 *
 * @template TParent of ServicesInterface
 */
class SecurityService implements SecurityInterface
{
    /** @use SecurityTrait<TParent> */
    use SecurityTrait;

    /**
     * Get name of the module from parent
     */
    public function getModName(): string
    {
        return $this->getParent()->getModName();
    }
}
