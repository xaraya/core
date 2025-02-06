<?php

/**
 * Config available via methods (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use xarConfigVars;
use sys;

sys::import('xaraya.services.servicetrait');
sys::import('xaraya.variables.config');

/**
 * For documentation purposes only - available via ConfigTrait
 */
interface ConfigInterface extends ServiceInterface
{
    public function getVar(string $varName, mixed $value = null): mixed;
    public function setVar(string $varName, mixed $value): bool;
    public function delVar(string $varName): mixed;
    public function cache(): void;
}

/**
 * Config available via methods
 */
trait ConfigTrait
{
    use ServiceTrait;

    /**
     * Get config variable
     */
    public function getVar(string $varName, mixed $value = null): mixed
    {
        return xarConfigVars::get(null, $varName, $value);
    }

    /**
     * Set config variable
     */
    public function setVar(string $varName, mixed $value): bool
    {
        return xarConfigVars::set(null, $varName, $value);
    }

    /**
     * Delete config variable
     */
    public function delVar(string $varName): bool
    {
        return xarConfigVars::delete(null, $varName);
    }

    /**
     * Cache config variables
     */
    public function cache(): void
    {
        xarConfigVars::cache();
    }
}

/**
 * Access xarConfigVars::* Config methods (getVar, setVar, ...)
 *
 * Available methods:
 * - getVar()
 * - setVar()
 * - delVar()
 * - cache()
 * - ...
 *
 */
class ConfigService implements ConfigInterface
{
    use ConfigTrait;
}
