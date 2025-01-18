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
use sys;
use EmptyParameterException;
use VariableValidationException;

sys::import('xaraya.services.servicetrait');

/**
 * For documentation purposes only - available via VariablesTrait
 */
interface VariablesInterface extends ServiceInterface
{
    /**
     * Fetch variable by name, with validation, variable, defaultValue, flags and prep
     *
     * @param string $name the variable name
     * @param string $validation the validation to be performed
     * @param mixed $variable contains the converted value of fetched variable by reference
     * @param mixed $defaultValue the default value (default null)
     * @param integer $flags bitmask which modify the behaviour of function (default xarVar::GET_OR_POST)
     * @param integer $prep will prep the value with xarVarPrepForDisplay, xarVarPrepHTMLDisplay, or dbconn->qstr()
     * @return mixed
     */
    public function fetch($name, $validation, &$variable, $defaultValue = null, $flags = xarVar::GET_OR_POST, $prep = xarVar::PREP_FOR_NOTHING): mixed;

    /**
     * Check variable by name: use existing value or get it by name if it is not already set, and validate the variable
     *
     * @param string $name the variable name
     * @param mixed $variable contains the converted value of fetched variable by reference
     * @param string $validation the validation to be performed (default 'isset')
     * @param mixed $defaultValue the default value (default null)
     * @return mixed
     */
    public function check($name, &$variable, $validation = 'isset', $defaultValue = null): mixed;

    /**
     * Find variable by name: set the value if there is one, and validate the variable
     *
     * @param string $name the variable name
     * @param mixed $variable contains the converted value of fetched variable by reference
     * @param string $validation the validation to be performed (default 'isset')
     * @param mixed $defaultValue the default value (default null)
     * @return mixed
     */
    public function find($name, &$variable, $validation = 'isset', $defaultValue = null): mixed;

    /**
     * Update variable by name: set the value if there is one or reset it, and validate the variable or throw exception
     *
     * @param string $name the variable name
     * @param mixed $variable contains the converted value of fetched variable by reference
     * @param string $validation the validation to be performed (default 'isset')
     * @param mixed $defaultValue the default value
     * @return mixed
     */
    public function update($name, &$variable, $validation = 'isset', $defaultValue = null): mixed;

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
    public function validate($validation, &$variable, $suppress = false, $name = '');

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

    public static function isCached(string $scope, string $name): bool;
    public static function getCached(string $scope, string $name): mixed;
    public static function setCached(string $scope, string $name, mixed $value): void;
    public static function delCached(string $scope, string $name): void;
}

/**
 * Variables available via methods
 * @template TParent of ServicesInterface
 */
trait VariablesTrait
{
    /** @use ServiceTrait<TParent> */
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
     * @param integer $prep will prep the value with xarVarPrepForDisplay, xarVarPrepHTMLDisplay, or dbconn->qstr()
     * @return mixed
     */
    public function fetch($name, $validation, &$variable, $defaultValue = null, $flags = xarVar::GET_OR_POST, $prep = xarVar::PREP_FOR_NOTHING): mixed
    {
        // Note: this should be restricted to gui methods
        return xarVar::fetch($name, $validation, $variable, $defaultValue, $flags, $prep);
    }

    /**
     * Check variable by name: use existing value or get it by name if it is not already set, and validate the variable
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
     * @param mixed $defaultValue the default value
     * @return mixed
     */
    public function check($name, &$variable, $validation = 'isset', $defaultValue = null): mixed
    {
        // Note: this should be restricted to gui methods
        return xarVar::fetch($name, $validation, $variable, $defaultValue, xarVar::DONT_SET, xarVar::PREP_FOR_NOTHING);
    }

    /**
     * Find variable by name: set the value if there is one, and validate the variable
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
     * @param mixed $defaultValue the default value
     * @return mixed
     */
    public function find($name, &$variable, $validation = 'isset', $defaultValue = null): mixed
    {
        // Note: this should be restricted to gui methods
        return xarVar::fetch($name, $validation, $variable, $defaultValue, xarVar::NOT_REQUIRED, xarVar::PREP_FOR_NOTHING);
    }

    /**
     * Update variable by name: set the value if there is one or reset it, and validate the variable or throw exception
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
     * @param mixed $defaultValue the default value
     * @return mixed
     */
    public function update($name, &$variable, $validation = 'isset', $defaultValue = null): mixed
    {
        // Note: this should be restricted to gui methods
        return xarVar::fetch($name, $validation, $variable, $defaultValue, xarVar::DONT_REUSE, xarVar::PREP_FOR_NOTHING);
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
    public function validate($validation, &$variable, $suppress = false, $name = '')
    {
        return xarVar::validate($validation, $variable, $suppress, $name);
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

    public static function isCached(string $scope, string $name): bool
    {
        return xarCoreCache::isCached($scope, $name);
    }

    public static function getCached(string $scope, string $name): mixed
    {
        return xarCoreCache::getCached($scope, $name);
    }

    public static function setCached(string $scope, string $name, mixed $value): void
    {
        xarCoreCache::setCached($scope, $name, $value);
    }

    public static function delCached(string $scope, string $name): void
    {
        xarCoreCache::delCached($scope, $name);
    }
}

/**
 * Access xarVar::* Variables methods (fetch, get, prep, ...)
 *
 * Available methods:
 * - fetch()
 * - check()
 * - find()
 * - update()
 * - validate()
 * - prep()
 * - prepHTML()
 * - ...
 *
 * @template TParent of ServicesInterface
 */
class VariablesService implements VariablesInterface
{
    /** @use VariablesTrait<TParent> */
    use VariablesTrait;
}
