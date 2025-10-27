<?php

/**
 * Variables available via methods (WIP)
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

use xarVar;
use xarCoreCache;
use xarController;
use sys;
use EmptyParameterException;
use VariableValidationException;

sys::import('xaraya.services.servicetrait');

/**
 * For documentation purposes only - available via VariablesTrait
 */
interface VariablesInterface extends ServiceInterface
{
    public const SLICE = 'variables';

    /**
     * Fetch variable by name, with validation, variable, defaultValue, flags and prep
     *
     * @param string $name the variable name
     * @param string $validation the validation to be performed
     * @param mixed $variable contains the converted value of fetched variable by reference
     * @param mixed $defaultValue the default value (default null)
     * @param integer $flags bitmask which modify the behaviour of function (default xarVar::GET_OR_POST)
     * @param integer $prep will prep the value with xarVar::prepForDisplay, xarVar::prepHTMLDisplay, or dbconn->qstr()
     * @return true
     */
    public function fetch($name, $validation, &$variable, $defaultValue = null, $flags = xarVar::GET_OR_POST, $prep = xarVar::PREP_FOR_NOTHING): true;

    /**
     * Get required variable by name: set the value if there is one, and validate the variable or throw excception
     *
     * @uses xarVar::fetch()
     * @param string $name the variable name
     * @param mixed $variable contains the converted value of fetched variable by reference
     * @param string $validation the validation to be performed (required here)
     * @param mixed $defaultValue the default value
     * @return true
     */
    public function get($name, &$variable, $validation, $defaultValue = null): true;

    /**
     * Check existing variable by name: use current value or get it by name if it is not already set, and validate the variable
     *
     * @param string $name the variable name
     * @param mixed $variable contains the converted value of fetched variable by reference
     * @param string $validation the validation to be performed (default 'isset')
     * @param mixed $defaultValue the default value (default null)
     * @return true
     */
    public function check($name, &$variable, $validation = 'isset', $defaultValue = null): true;

    /**
     * Find optional variable by name: set the value if there is one, and validate the variable
     *
     * @param string $name the variable name
     * @param mixed $variable contains the converted value of fetched variable by reference
     * @param string $validation the validation to be performed (default 'isset')
     * @param mixed $defaultValue the default value (default null)
     * @return true
     */
    public function find($name, &$variable, $validation = 'isset', $defaultValue = null): true;

    /**
     * Update required variable by name: set the value if there is one or reset it, and validate the variable or throw exception
     *
     * @param string $name the variable name
     * @param mixed $variable contains the converted value of fetched variable by reference
     * @param string $validation the validation to be performed (default 'isset')
     * @param mixed $defaultValue the default value
     * @return true
     */
    public function update($name, &$variable, $validation = 'isset', $defaultValue = null): true;

    /**
     * Validates a variable performing the $validation test type on $variable.
     *
     * @param mixed $validation the validation to be performed
     * @param mixed $variable the subject on which the validation must be performed, will be where the validated value will be returned
     * @param bool $suppress suppress any exception if the validation fails or not (default false)
     * @param string $name (optional) name of the variable for the exception message
     * @throws EmptyParameterException
     * @throws VariableValidationException
     * @return bool true if the $variable validates correctly, false otherwise
     */
    public function validate($validation, &$variable, $suppress = false, $name = ''): bool;

    /**
     * Prepare text for display, and convert all html special characters
     *
     * @param string ...$args
     * @return mixed
     */
    public function prep(...$args);

    /**
     * Prepare text for HTML output, and allow some html special characters
     *
     * @param string ...$args
     * @return mixed
     */
    public function prepHTML(...$args);

    /**
     * Prepare obfuscated e-mail output
     *
     * @param string ...$args
     * @return mixed
     */
    public function prepEmail(...$args);

    /**
     * Prepare text for operating system path, and convert all special characters
     *
     * @param string ...$args
     * @return mixed
     */
    public function prepPath(...$args);

    public function isCached(string $scope, string $name): bool;
    public function getCached(string $scope, string $name): mixed;
    public function setCached(string $scope, string $name, mixed $value): void;
    public function delCached(string $scope, string $name): void;
    public function hasPreload(string $scope, ?string $name = null): bool;
    public function loadCached(string $scope, ?string $name = null): bool;
    public function saveCached(string $scope, ?string $name = null, ?string $source = null): bool;
}

/**
 * Variables available via methods
 */
trait VariablesTrait
{
    use ServiceTrait;

    /**
     * Fetch variable by name, with validation, variable, defaultValue, flags and prep
     *
     * xarVar::GET_OR_POST  - fetch from GET or POST variables
     * xarVar::GET_ONLY     - fetch from GET variables only
     * xarVar::POST_ONLY    - fetch from POST variables only
     * xarVar::NOT_REQUIRED - allow the variable to be empty/not set, dont raise exception if it is
     * xarVar::DONT_REUSE   - if there is an existing value, do not reuse it
     * xarVar::DONT_SET     - if there is an existing value, use it
     *
     * @uses xarVar::fetch()
     * @param string $name the variable name
     * @param string $validation the validation to be performed
     * @param mixed $variable contains the converted value of fetched variable by reference
     * @param mixed $defaultValue the default value (default null)
     * @param integer $flags bitmask which modify the behaviour of function (default xarVar::GET_OR_POST)
     * @param integer $prep will prep the value with xarVar::prepForDisplay, xarVar::prepHTMLDisplay, or dbconn->qstr()
     * @return true
     */
    public function fetch($name, $validation, &$variable, $defaultValue = null, $flags = xarVar::GET_OR_POST, $prep = xarVar::PREP_FOR_NOTHING): true
    {
        // Note: this should be restricted to gui methods
        return xarVar::fetch($name, $validation, $variable, $defaultValue, $flags, $prep);
    }

    /**
     * Get required variable by name: set the value if there is one, and validate the variable or throw excception
     *
     * ```
     * $this->var()->get($name, $variable, $validation, $defaultValue=null)
     * ```
     * with flags = xarVar::GET_OR_POST - the variable must be in GET or POST
     * and prep = xarVar::PREP_FOR_NOTHING
     *
     * @uses xarVar::fetch()
     * @param string $name the variable name
     * @param mixed $variable contains the converted value of fetched variable by reference
     * @param string $validation the validation to be performed (required here)
     * @param mixed $defaultValue the default value (default null)
     * @return true
     */
    public function get($name, &$variable, $validation, $defaultValue = null): true
    {
        // Note: this should be restricted to gui methods
        return xarVar::fetch($name, $validation, $variable, $defaultValue);
    }

    /**
     * Check existing variable by name: use current value or get it by name if it is not already set, and validate the variable
     *
     * ```
     * $this->var()->check($name, $variable, $validation='isset', $defaultValue=null)
     * ```
     * with flags = xarVar::DONT_SET     - if there is an existing value, use it
     * and prep = xarVar::PREP_FOR_NOTHING
     *
     * @uses xarVar::fetch()
     * @param string $name the variable name
     * @param mixed $variable contains the converted value of fetched variable by reference
     * @param string $validation the validation to be performed (default 'isset')
     * @param mixed $defaultValue the default value (default null)
     * @return true
     */
    public function check($name, &$variable, $validation = 'isset', $defaultValue = null): true
    {
        // use current value or get it by name if it is not already set
        if (!isset($variable)) {
            // get variable from request if any
            $variable = $this->getRequestVar($name);
        }
        // validate the variable but do not throw exception
        $suppress = true;
        $validated = $this->validate($validation, $variable, $suppress, $name);
        // if not validated, use default value (can be null)
        if (!$validated) {
            $variable = $defaultValue;
        }
        return true;
        // Note: this should be restricted to gui methods
        //return xarVar::fetch($name, $validation, $variable, $defaultValue, xarVar::DONT_SET, xarVar::PREP_FOR_NOTHING);
    }

    /**
     * Find optional variable by name: set the value if there is one, and validate the variable
     * Note: this is functionally the same as check(), except here we don't expect variable to have value yet
     *
     * ```
     * $this->var()->find($name, $variable, $validation='isset', $defaultValue=null)
     * ```
     * with flags = xarVar::NOT_REQUIRED - allow the variable to be empty/not set, dont raise exception if it is
     * and prep = xarVar::PREP_FOR_NOTHING
     *
     * @uses xarVar::fetch()
     * @param string $name the variable name
     * @param mixed $variable contains the converted value of fetched variable by reference
     * @param string $validation the validation to be performed (default 'isset')
     * @param mixed $defaultValue the default value (default null)
     * @return true
     */
    public function find($name, &$variable, $validation = 'isset', $defaultValue = null): true
    {
        return $this->check($name, $variable, $validation, $defaultValue);
        // Note: this should be restricted to gui methods
        //return xarVar::fetch($name, $validation, $variable, $defaultValue, xarVar::NOT_REQUIRED, xarVar::PREP_FOR_NOTHING);
    }

    /**
     * Update required variable by name: set the value if there is one or reset it, and validate the variable or throw exception
     *
     * ```
     * $this->var()->update($name, $variable, $validation='isset', $defaultValue=null)
     * ```
     * with flags = xarVar::DONT_REUSE   - if there is an existing value, do not reuse it
     * and prep = xarVar::PREP_FOR_NOTHING
     *
     * @uses xarVar::fetch()
     * @param string $name the variable name
     * @param mixed $variable contains the converted value of fetched variable by reference
     * @param string $validation the validation to be performed (default 'isset')
     * @param mixed $defaultValue the default value (default null)
     * @return true
     */
    public function update($name, &$variable, $validation = 'isset', $defaultValue = null): true
    {
        // set the value if there is one or reset it
        $variable = $this->getRequestVar($name);
        // validate the variable or throw exception unless we have default value
        $suppress = false;
        if (isset($defaultValue)) {
            $suppress = true;
        }
        $validated = $this->validate($validation, $variable, $suppress, $name);
        // if not validated, use default value (if not null)
        if (!$validated) {
            $variable = $defaultValue;
        }
        return true;
        // Note: this should be restricted to gui methods
        //return xarVar::fetch($name, $validation, $variable, $defaultValue, xarVar::DONT_REUSE, xarVar::PREP_FOR_NOTHING);
    }

    /**
     * Validates a variable performing the $validation test type on $variable.
     *
     * @param mixed $validation the validation to be performed
     * @param mixed $variable the subject on which the validation must be performed, will be where the validated value will be returned
     * @param bool $suppress suppress any exception if the validation fails or not (default false)
     * @param string $name (optional) name of the variable for the exception message
     * @throws EmptyParameterException
     * @throws VariableValidationException
     * @return bool true if the $variable validates correctly, false otherwise
     */
    public function validate($validation, &$variable, $suppress = false, $name = ''): bool
    {
        return xarVar::validate($validation, $variable, $suppress, $name);
    }

    /**
     * Summary of getRequestVar
     * @param string $name
     * @param ?string $allowOnlyMethod
     * @return mixed
     */
    protected function getRequestVar(string $name, ?string $allowOnlyMethod = null): mixed
    {
        // @todo use context or ControllerService via parent someday?
        return xarController::getVar($name, $allowOnlyMethod);
    }

    /**
     * Prepare text for display, and convert all html special characters
     *
     * @param string ...$args
     * @return mixed
     */
    public function prep(...$args)
    {
        return xarVar::prepForDisplay(...$args);
    }

    /**
     * Prepare text for HTML output, and allow some html special characters
     *
     * @param string ...$args
     * @return mixed
     */
    public function prepHTML(...$args)
    {
        return xarVar::prepHTMLDisplay(...$args);
    }

    /**
     * Prepare obfuscated e-mail output
     *
     * @param string ...$args
     * @return mixed
     */
    public function prepEmail(...$args)
    {
        return xarVar::prepEmailDisplay(...$args);
    }

    /**
     * Prepare text for operating system path, and convert all special characters
     *
     * @param string ...$args
     * @return mixed
     */
    public function prepPath(...$args)
    {
        return xarVar::prepForOS(...$args);
    }

    public function isCached(string $scope, string $name): bool
    {
        return $this->getParent()->mem()->has($scope, $name);
    }

    public function getCached(string $scope, string $name): mixed
    {
        return $this->getParent()->mem()->get($scope, $name);
    }

    public function setCached(string $scope, string $name, mixed $value): void
    {
        $this->getParent()->mem()->set($scope, $name, $value);
    }

    public function delCached(string $scope, string $name): void
    {
        $this->getParent()->mem()->del($scope, $name);
    }

    public function hasPreload(string $scope, ?string $name = null): bool
    {
        return $this->getParent()->mem()->hasPreload($scope, $name);
    }

    public function loadCached(string $scope, ?string $name = null): bool
    {
        return $this->getParent()->mem()->load($scope, $name);
    }

    public function saveCached(string $scope, ?string $name = null, ?string $source = null): bool
    {
        return $this->getParent()->mem()->save($scope, $name, $source);
        // Saved in DD > Utilities > DB Connections = modules/dynamicdata/admingui/dbconfig.php
        // for all modules - see UtilApi::getAllDatabases()
        //xar::mem()->save('DynamicData', 'Databases');
    }
}

/**
 * Access xarVar::* Variables methods (fetch, get, prep, ...)
 *
 * Available methods:
 * - get() - xarVar::GET_OR_POST = Get required variable by name: set the value if there is one, and validate the variable or throw excception
 * - check() - xarVar::DONT_SET = Check existing variable by name: use current value or get it by name if it is not already set, and validate the variable
 * - find() - xarVar::NOT_REQUIRED = Find optional variable by name: set the value if there is one, and validate the variable
 * - update() - xarVar::DONT_REUSE = Update required variable by name: set the value if there is one or reset it, and validate the variable or throw exception
 * - fetch() - original xarVar::fetch() with different order of params than above
 * - validate()
 * - prep()
 * - prepHTML()
 * - ...
 *
 */
class VariablesService implements VariablesInterface
{
    use VariablesTrait;
}
