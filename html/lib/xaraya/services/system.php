<?php

/**
 * System variables available via methods (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.6
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

    public function getVar(string $name): mixed;
    public function setVar(string $name, mixed $value): bool;
    public function delVar(string $name): mixed;
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
    private string $scope = sys::CONFIG;

    /**
     * Get system variable
     */
    public function getVar(string $name): mixed
    {
        $scope = $this->getScope();
        if (!isset($this->systemVars[$scope])) {
            $this->preload($scope);
        }

        // We need the system variable; complain if it's not there
        if (!isset($this->systemVars[$scope][$name])) {
            throw new Exception("xarSystemVars: Unknown system variable: '$name'.");
        }

        return $this->systemVars[$scope][$name];
    }

    /**
     * Set system variable
     */
    public function setVar(string $name, mixed $value): bool
    {
        $scope = $this->getScope();
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
    }

    /**
     * Delete system variable
     */
    public function delVar(string $name): bool
    {
        $scope = $this->getScope();
        // Not supported ?
        return false;
    }

    protected function preload(string $scope = sys::CONFIG)
    {
        $fileName = sys::varpath() . '/';
        if ($scope == sys::LOG) {
            $fileName .= 'logs/';
        }
        $fileName .= $scope;

        // We need the file; complain if it's not there
        if (!file_exists($fileName)) {
            throw new Exception("The system config file '$fileName' could not be found.");
        }

        /** @var array<string, mixed> $systemConfiguration */
        $systemConfiguration = [];
        // Make stuff from config.system.php available
        // NOTE: we can not use sys::import since the variable scope would be wrong.
        include $fileName;
        /** @phpstan-ignore-next-line */
        $this->systemVars[$scope] = $systemConfiguration;
    }

    /**
     * Get current scope when called as $this->sysConfig(sys::LAYOUT)->...
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * Override current scope when called as $this->sysConfig(sys::LAYOUT)->...
     * @param string $scope
     * @return void
     */
    public function setScope(string $scope): void
    {
        $this->scope = $scope;
    }

    /**
     * Create a specialized version of this service for a specific user ID.
     * @param mixed ...$args
     * @return ServiceInterface
     */
    public function specialize(...$args): ServiceInterface
    {
        $clone = clone $this;
        if (isset($args[0])) {
            $clone->setScope($args[0]);
        }
        return $clone;
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

    // @todo remove this when all specialize() methods are implemented
    public function __clone()
    {
        $this->scope = sys::CONFIG;
    }
}
