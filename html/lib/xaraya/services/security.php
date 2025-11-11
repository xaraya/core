<?php

/**
 * Security available via methods (WIP)
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

namespace Xaraya\Services;

use xarSecurity;
use ForbiddenOperationException;

/**
 * For documentation purposes only - available via SecurityTrait
 */
interface SecurityInterface extends ServiceInterface
{
    public const SLICE = 'security';

    /**
     * Check access based on security mask or module action
     */
    public function checkAccess(string $mask, string|int $action = '', ?string $modName = null): bool;

    /**
     * Full xarSecurity::check() with mask, catch, component, instance, module, rolename, realm, level
     */
    public function check(string $mask, int $catch = 1, string $component = '', string $instance = '', string $module = '', string $rolename = '', int $realm = 0, int $level = 0): bool;

    /**
     * Generate authorisation key for this module
     */
    public function genAuthKey(?string $modName = null): string;

    /**
     * Confirm authorisation key by name for this module
     */
    public function confirmAuthKey(?string $modName = null, string $name = 'authid', $catch = false): bool;
}

/**
 * Security available via methods
 */
trait SecurityTrait
{
    use ServiceTrait;

    /**
     * Check access based on security mask or module action
     */
    public function checkAccess(string $mask, string|int $action = '', ?string $modName = null): bool
    {
        // if the mask is empty, use xar::mod()->checkAccess() - currently not used
        if (empty($mask) && !empty($action) && is_string($action)) {
            $modName ??= $this->getModName();
            $xar = $this->getParent();
            return $xar->mod()->checkAccess($modName, $action) ? true : false;
        }
        // @todo mainly legacy hook module - remove 2nd argument in call later?
        if (is_int($action) && $action === 0) {
            // we don't want to redirect here
            return $this->callSecurityCheck($mask, 0);
        }
        return $this->callSecurityCheck($mask);
    }

    /**
     * Call xarSecurity::check()
     * @param string $mask
     * @param int $catch
     * @return bool|never
     */
    protected function callSecurityCheck(string $mask, int $catch = 1): bool
    {
        // @todo handle redirect() + exit() in case of failure
        return xarSecurity::check($mask, $catch) ? true : false;
    }

    public function check(string $mask, int $catch = 1, string $component = '', string $instance = '', string $module = '', string $rolename = '', int $realm = 0, int $level = 0): bool
    {
        // @todo handle redirect() + exit() in case of failure
        return xarSecurity::check($mask, $catch, $component, $instance, $module, $rolename, $realm, $level) ? true : false;
    }

    /**
     * Generate authorisation key for this module
     */
    public function genAuthKey(?string $modName = null): string
    {
        // Note: this should be restricted to gui methods
        $modName ??= $this->getModName();
        $xar = $this->getParent();
        if (empty($modName)) {
            $modName = $xar->req()->getRequest()->getModule();
        }

        // Date gives extra security but leave it out for now
        // $key = $xar->session()->getVar('rand') . $modName . date ('YmdGi');
        $key = $xar->session()->getVar('rand') . strtolower($modName);

        // Encrypt key
        $authid = md5($key);

        // Tell xarCache not to cache this page
        $xar->cache()->noCache();

        // Return encrypted key
        return $authid;
    }

    /**
     * Confirm authorisation key for this module
     */
    public function confirmAuthKey(?string $modName = null, string $varName = 'authid', $catch = false): bool
    {
        // Note: this should be restricted to gui methods
        $modName ??= $this->getModName();
        $xar = $this->getParent();
        // We don't need this check for AJAX calls
        if ($xar->req()->getRequest()->isAjax()) {
            return true;
        }

        if (empty($modName)) {
            $modName = $xar->req()->getRequest()->getModule();
        }
        $authid = $xar->req()->getVar($varName);

        // Regenerate static part of key
        $partkey = $xar->session()->getVar('rand') . strtolower($modName);

        // Not using time-sensitive keys for the moment
        //    // Key life is 5 minutes, so search backwards and forwards 5
        //    // minutes to see if there is a match anywhere
        //    for ($i=-5; $i<=5; $i++) {
        //        $testdate  = mktime(date('G'), date('i')+$i, 0, date('m') , date('d'), date('Y'));
        //
        //        $testauthid = md5($partkey . date('YmdGi', $testdate));
        //        if ($testauthid == $authid) {
        //            // Match
        //
        //            // We've used up the current random
        //            // number, make up a new one
        //            srand((double) microtime(true) * 1000000.0);
        //            $xar->session()->setVar('rand', rand());
        //
        //            return true;
        //        }
        //    }
        if ((md5($partkey)) == $authid) {
            // Match - generate new random number for next key and leave happy
            srand((float) microtime(true) * 1000000.0);
            $xar->session()->setVar('rand', rand());
            return true;
        }
        // Not found, assume invalid
        if ($catch) {
            throw new ForbiddenOperationException();
        } else {
            return false;
        }
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
 */
class SecurityService implements SecurityInterface
{
    use SecurityTrait;

    /**
     * Get name of the module from parent
     */
    public function getModName(): string
    {
        return $this->getParent()->getModName();
    }
}
