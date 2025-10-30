<?php

/**
 * System variables available via methods (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use sys;
use Exception;

/**
 * For documentation purposes only - available via SystemTrait
 */
interface SystemInterface extends ServiceInterface
{
    public const SLICE = 'system';

    public function getVar(string $scope, string $name): mixed;
    public function setVar(string $scope, string $name, mixed $value): bool;
    public function delVar(string $scope, string $name): mixed;
}

/**
 * System variables available via methods
 */
trait SystemTrait
{
    use ServiceTrait;
    public const SCOPE = 'System.Variables';
    /** @var array<string, array<string, mixed>> */
    private array $systemVars = [];

    /**
     * Get system variable
     */
    public function getVar(?string $scope, string $name): mixed
    {
        // --- LEGACY METHOD BODY ---
        if (!isset($scope)) {
            $scope = sys::CONFIG;
        }

        if (!isset($this->systemVars[$scope])) {
            $this->preload($scope);
        }

        // We need the system variable; complain if it's not there
        if (!isset($this->systemVars[$scope][$name])) {
            throw new Exception("xarSystemVars: Unknown system variable: '$name'.");
        }

        return $this->systemVars[$scope][$name];
        // --- END LEGACY METHOD BODY ---
        // return xarSystemVars::get($scope, $name, $default);
    }

    /**
     * Set system variable
     */
    public function setVar(string $scope, string $name, mixed $value): bool
    {
        // --- LEGACY METHOD BODY ---
        // Allow overriding system layout if needed
        if ($scope == sys::LAYOUT) {
            $this->systemVars[$scope][$name] = $value;
            return true;
        }
        // Allow overriding system config for testing if needed - see UserContextTest
        if ($this->getParent()->mem()->has('Testing:' . $scope, $name)) {
            $this->systemVars[$scope][$name] = $value;
            return true;
        }
        // Not supported ?
        return false;
        // --- END LEGACY METHOD BODY ---
        // return xarSystemVars::set($scope, $name, $value);
    }

    /**
     * Delete system variable
     */
    public function delVar(string $scope, string $name): bool
    {
        // --- LEGACY METHOD BODY ---
        // Not supported ?
        return false;
        // --- END LEGACY METHOD BODY ---
        // return xarSystemVars::delete($scope, $name);
    }

    protected function preload(string $scope)
    {
        // --- LEGACY METHOD BODY ---
        $fileName = sys::varpath() . '/';
        if ($scope == sys::LOG) {
            $fileName .= 'logs/';
        }
        $fileName .= $scope;

        // We need the file; complain if it's not there
        if (!file_exists($fileName)) {
            throw new Exception("The system config file '$fileName' could not be found.");
        }

        // Make stuff from config.system.php available
        // NOTE: we can not use sys::import since the variable scope would be wrong.
        include $fileName;
        /** @phpstan-ignore-next-line */
        $this->systemVars[$scope] = $systemConfiguration;
        // --- END LEGACY METHOD BODY ---
        // xarSystemVars::preload($scope);
    }
}

/**
 * Access xarSystemVars::* System methods (getVar, setVar, ...)
 *
 * Available methods:
 * - getVar()
 * - setVar()
 * - delVar()
 * - cache()
 * - ...
 *
 */
class SystemService implements SystemInterface
{
    use SystemTrait;
}
