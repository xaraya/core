<?php

/**
 * Variables available via methods (WIP)
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

use xarVar;
use xarVarPrep;
use sys;
use EmptyParameterException;
use ValidationExceptions;
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
     * @param integer $prep will prep the value with xarVarPrep::text, xarVarPrep::html, or dbconn->qstr()
     * @return true
     */
    public function fetch($name, $validation, &$variable, $defaultValue = null, $flags = xarVar::GET_OR_POST, $prep = xarVarPrep::NOTHING): true;

    /**
     * Get required variable by name: set the value if there is one, and validate the variable or throw excception
     *
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
     * Fetches and validates in a Batch.
     * @param mixed $batch
     * @return array<mixed> With the respective exceptions in case of failure
     */
    public function batchFetch(...$batch): array;

    /**
     * Prepare text for display, and convert all html special characters
     *
     * @param string ...$args
     * @return mixed
     * @deprecated 2.8.4 use $this->prep()->text() instead
     */
    public function prep(...$args);

    /**
     * Prepare text for HTML output, and allow some html special characters
     *
     * @param string ...$args
     * @return mixed
     * @deprecated 2.8.4 use $this->prep()->html() instead
     */
    public function prepHTML(...$args);

    /**
     * Prepare obfuscated e-mail output
     *
     * @param string ...$args
     * @return mixed
     * @deprecated 2.8.4 use $this->prep()->email() instead
     */
    public function prepEmail(...$args);

    /**
     * Prepare text for operating system path, and convert all special characters
     *
     * @param string ...$args
     * @return mixed
     * @deprecated 2.8.4 use $this->prep()->path() instead
     */
    public function prepPath(...$args);

    /** @deprecated 2.8.4 use xar::mem()->has() instead */
    public function isCached(string $scope, string $name): bool;
    /** @deprecated 2.8.4 use xar::mem()->get() instead */
    public function getCached(string $scope, string $name): mixed;
    /** @deprecated 2.8.4 use xar::mem()->set() instead */
    public function setCached(string $scope, string $name, mixed $value): void;
    /** @deprecated 2.8.4 use xar::mem()->del() instead */
    public function delCached(string $scope, string $name): void;
    /** @deprecated 2.8.4 use xar::mem()->hasPreload() instead */
    public function hasPreload(string $scope, ?string $name = null): bool;
    /** @deprecated 2.8.4 use xar::mem()->load() instead */
    public function loadCached(string $scope, ?string $name = null): bool;
    /** @deprecated 2.8.4 use xar::mem()->save() instead */
    public function saveCached(string $scope, ?string $name = null, ?string $source = null): bool;
}

/**
 * Variables available via methods
 */
trait VariablesTrait
{
    use ServiceTrait;

    protected bool $initialized = false;

    /**
     * Initialise the variable handling options
     *
     * Sets up allowable html and htmlentities options
     *
     * @param array<string, mixed> $config
     * @todo revisit naming of config_vars table
    **/
    public function init(array $config = []): bool
    {
        if (empty($config) && $this->initialized) {
            return true;
        }
        $db = $this->getParent()->db();
        // Configuration init needs to be done first
        $tables = [
            'config_vars' => $db->getPrefix() . '_module_vars',
        ];

        $db->importTables($tables);

        // Initialise the variable cache
        xarVarPrep::init($config);

        $this->initialized = true;
        return true;
    }

    /**
     * Fetch variable by name, with validation, variable, defaultValue, flags and prep
     *
     * 1st try to use the variable provided, if this is not set (Or the xarVar::DONT_REUSE flag is used)
     * then try to get the variable from the input (POST/GET methods for now)
     *
     * Then tries to validate the variable thru xarVarPrep::validate.
     *
     * See xarVarPrep::validate for details about nature of $validation.
     * After the call the $value parameter passed by reference is set to the variable value converted to the proper type
     * according to the validation applied.
     *
     * The $defaultValue provides a default value that is returned when the variable is not present or doesn't validate
     * correctly.
     *
     * The $flag parameter is a bitmask between the following constants:
     * xarVar::GET_OR_POST  - fetch from GET or POST variables
     * xarVar::GET_ONLY     - fetch from GET variables only
     * xarVar::POST_ONLY    - fetch from POST variables only
     * xarVar::NOT_REQUIRED - allow the variable to be empty/not set, dont raise exception if it is
     * xarVar::DONT_REUSE   - if there is an existing value, do not reuse it
     * xarVar::DONT_SET     - if there is an existing value, use it
     *
     * You can force to get the variable only from GET parameters or POST parameters by setting the $flag parameter
     * to one of xarVar::GET_ONLY or xarVar::POST_ONLY.
     *
     * You can force xar::var()->fetch not to reuse the variable by setting
     * the $flag parameter to xarVar::DON_REUSE.
     *
     * By default $flag is xarVar::GET_OR_POST which means that xar::var()->fetch will lookup both GET and POST parameters and
     * that if the variable is not present or doesn't validate correctly an exception will be raised.
     *
     * The $prep flag will prepare $value by passing it to one of the following:
     *   xarVarPrep::NOTHING:    no prep (default)
     *   xarVarPrep::TEXT:       xarVarPrep::text($value)
     *   xarVarPrep::HTML:       xarVarPrep::html($value)
     *   xarVarPrep::STORE:      dbconn->qstr($value)
     *   xarVarPrep::TRIM:       trim($value)
     *
     * @param string $name the variable name
     * @param string $validation the validation to be performed
     * @param mixed $variable contains the converted value of fetched variable by reference
     * @param mixed $defaultValue the default value (default null)
     * @param integer $flags bitmask which modify the behaviour of function (default xarVar::GET_OR_POST)
     * @param integer $prep will prep the value with xarVarPrep::text, xarVarPrep::html, or dbconn->qstr()
     * @return true
     */
    public function fetch($name, $validation, &$variable, $defaultValue = null, $flags = xarVar::GET_OR_POST, $prep = xarVarPrep::NOTHING): true
    {
        assert(is_int($flags));
        assert(empty($name) || preg_match("/^[a-zA-Z0-9_\[\]\"\x7f-\xff][a-zA-Z0-9_\[\]\"\x7f-\xff]*$/", $name));

        $allowOnlyMethod = null;
        if ($flags & xarVar::GET_ONLY) {
            $allowOnlyMethod = 'GET';
        }
        if ($flags & xarVar::POST_ONLY) {
            $allowOnlyMethod = 'POST';
        }

        // xarVar::DONT_SET does not set $variable, if there already is one
        // This allows us to have a extract($args) before the xarVar::fetch and still run
        // the variables thru the tests here.
        $oldValue = null;
        if (isset($variable) && $flags & xarVar::DONT_SET) {
            $oldValue = $variable;
        }

        // xarVar::DONT_REUSE fetches the variable, regardless
        // FIXME: this flag doesn't seem to work !?
        // mrb: what doesn't work then? seems ok within the given workings
        // --------v  this is kinda confusing though, especially when dont_set is used as flag.
        if (!isset($variable) || ($flags & xarVar::DONT_REUSE)) {
            $variable = $this->getRequestVar($name, $allowOnlyMethod);
        }

        // Suppress validation warnings when dont_set, not_required or a default value is specified
        $supress = (($flags & xarVar::DONT_SET) || ($flags & xarVar::NOT_REQUIRED) || isset($defaultValue));
        // Validate the $variable given
        $validated = $this->validate($validation, $variable, $supress, $name);

        if (!$validated) {
            // The value does not validate
            $variable = null; // we first make sure that this is what we expect to return

            // Perhaps the default or old can be returned?
            if (($flags & xarVar::NOT_REQUIRED) || isset($defaultValue)) {
                // CHECKME:  even for the xarVar::DONT_SET flag !?
                // if you set a non-null default value, assume you want to use it here
                $variable = $defaultValue;
            } elseif (($flags & xarVar::DONT_SET) && isset($oldValue) && $this->validate($validation, $oldValue, $supress)) {
                // with xarVar::DONT_SET, make sure we don't pass invalid old values back either
                $variable = $oldValue;
            }
        } else {
            // Value is ok, handle preparation of that value
            if ($prep & xarVarPrep::TEXT) {
                $variable = xarVarPrep::text($variable);
            }
            if ($prep & xarVarPrep::HTML) {
                $variable = xarVarPrep::html($variable);
            }

            // TODO: this is used nowhere, plus it introduces a db connection here which is of no use
            if ($prep & xarVarPrep::STORE) {
                $dbconn = $this->getParent()->db()->getConn();
                $variable = $dbconn->qstr($variable);
            }

            if ($prep & xarVarPrep::TRIM) {
                $variable = trim($variable);
            }
        }
        return true;
        // Note: this should be restricted to gui methods
        //return xarVar::fetch($name, $validation, $variable, $defaultValue, $flags, $prep);
    }

    /**
     * Get required variable by name: set the value if there is one, and validate the variable or throw excception
     *
     * ```
     * $this->var()->get($name, $variable, $validation, $defaultValue=null)
     * ```
     * with flags = xarVar::GET_OR_POST - the variable must be in GET or POST
     * and prep = xarVarPrep::NOTHING
     *
     * @param string $name the variable name
     * @param mixed $variable contains the converted value of fetched variable by reference
     * @param string $validation the validation to be performed (required here)
     * @param mixed $defaultValue the default value (default null)
     * @return true
     */
    public function get($name, &$variable, $validation, $defaultValue = null): true
    {
        // Note: this should be restricted to gui methods
        return $this->fetch($name, $validation, $variable, $defaultValue);
    }

    /**
     * Check existing variable by name: use current value or get it by name if it is not already set, and validate the variable
     *
     * ```
     * $this->var()->check($name, $variable, $validation='isset', $defaultValue=null)
     * ```
     * with flags = xarVar::DONT_SET     - if there is an existing value, use it
     * and prep = xarVarPrep::NOTHING
     *
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
        //return xarVar::fetch($name, $validation, $variable, $defaultValue, xarVar::DONT_SET, xarVarPrep::NOTHING);
    }

    /**
     * Find optional variable by name: set the value if there is one, and validate the variable
     * Note: this is functionally the same as check(), except here we don't expect variable to have value yet
     *
     * ```
     * $this->var()->find($name, $variable, $validation='isset', $defaultValue=null)
     * ```
     * with flags = xarVar::NOT_REQUIRED - allow the variable to be empty/not set, dont raise exception if it is
     * and prep = xarVarPrep::NOTHING
     *
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
        //return xarVar::fetch($name, $validation, $variable, $defaultValue, xarVar::NOT_REQUIRED, xarVarPrep::NOTHING);
    }

    /**
     * Update required variable by name: set the value if there is one or reset it, and validate the variable or throw exception
     *
     * ```
     * $this->var()->update($name, $variable, $validation='isset', $defaultValue=null)
     * ```
     * with flags = xarVar::DONT_REUSE   - if there is an existing value, do not reuse it
     * and prep = xarVarPrep::NOTHING
     *
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
        //return xarVar::fetch($name, $validation, $variable, $defaultValue, xarVar::DONT_REUSE, xarVarPrep::NOTHING);
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
        return xarVarPrep::validate($validation, $variable, $suppress, $name);
    }

    /**
     * Summary of getRequestVar
     * @param string $name
     * @param ?string $allowOnlyMethod
     * @return mixed
     */
    protected function getRequestVar(string $name, ?string $allowOnlyMethod = null): mixed
    {
        return $this->getParent()->req()->getVar($name, $allowOnlyMethod);
    }

    /**
     * Fetches and validates in a Batch.
     *
     *   xarVar::fetch('reassign', 'checkbox',  $reassign, false, xarVar::NOT_REQUIRED);
     *   xarVar::fetch('repeat',   'int:1:100', $repeat,   1,     xarVar::NOT_REQUIRED);
     *
     *  Can be done thru xarVar::batchFetch with:
     *
     *  $result = xarVar::batchFetch(array('reassign','checkbox', 'reassign', false, xarVar::NOT_REQUIRED),
     *                             array('repeat', 'int:1:100', 'repeat'));
     *
     * Notice that i didnt use xarVar::NOT_REQUIRED because xarVar::batchFetch will trap the
     * thrown exceptions for me in the result array, thus allowing me to get this easily
     * back to the GUI warning the user that the variable didn't validate and for what reason
     *
     * if ($result['no_errors']) {
     *     //No Errors!
     *     $results[variable name]['value'] holds the inputs with the apropriate types
     * } else {
     *     //Errors Found, go back to the GUI and use the $result to display the errors
     *     // in the right place
     *     $results[variable name]['value'] holds the input values
     *     $results[variable name]['error'] holds the Error Message ('' in case of none)
     *  }
     *
     * @param mixed $batch
     * @return array<mixed> With the respective exceptions in case of failure
     */
    public function batchFetch(...$batch): array
    {
        $result_array = [];
        $no_errors    = true;

        foreach ($batch as $line) {
            $result_array[$line[2]] = [];
            try {
                $result = $this->fetch($line[0], $line[1], $result_array[$line[2]]['value'], $line[3] ?? null, $line[4] ?? xarVar::GET_OR_POST);
                $result_array[$line[2]]['error'] = '';
            } catch (ValidationExceptions $e) { // Only catch validation exceptions, the rest should be thrown
                //Records the error presented in the given input variable
                $result_array[$line[2]]['error'] = $e->getMessage();
                //Mark that we've got an error
                $no_errors = false;
            }
        }

        //Chose this key name to avoid clashes and make it easy to go on if there is no
        //errors present in the Fetched variables.
        $result_array['no_errors'] = $no_errors;

        return $result_array; // TODO: Is it the responsability of the callee to further handle this? If they dont => security risk.
    }

    /**
     * Prepare text for display, and convert all html special characters
     *
     * @param string ...$args
     * @return mixed
     * @deprecated 2.8.4 use $this->prep()->text() instead
     */
    public function prep(...$args)
    {
        return xarVarPrep::text(...$args);
    }

    /**
     * Prepare text for HTML output, and allow some html special characters
     *
     * @param string ...$args
     * @return mixed
     * @deprecated 2.8.4 use $this->prep()->html() instead
     */
    public function prepHTML(...$args)
    {
        return xarVarPrep::html(...$args);
    }

    /**
     * Prepare obfuscated e-mail output
     *
     * @param string ...$args
     * @return mixed
     * @deprecated 2.8.4 use $this->prep()->email() instead
     */
    public function prepEmail(...$args)
    {
        return xarVarPrep::email(...$args);
    }

    /**
     * Prepare text for operating system path, and convert all special characters
     *
     * @param string ...$args
     * @return mixed
     * @deprecated 2.8.4 use $this->prep()->path() instead
     */
    public function prepPath(...$args)
    {
        return xarVarPrep::path(...$args);
    }

    /** @deprecated 2.8.4 use xar::mem()->has() instead */
    public function isCached(string $scope, string $name): bool
    {
        return $this->getParent()->mem()->has($scope, $name);
    }

    /** @deprecated 2.8.4 use xar::mem()->get() instead */
    public function getCached(string $scope, string $name): mixed
    {
        return $this->getParent()->mem()->get($scope, $name);
    }

    /** @deprecated 2.8.4 use xar::mem()->set() instead */
    public function setCached(string $scope, string $name, mixed $value): void
    {
        $this->getParent()->mem()->set($scope, $name, $value);
    }

    /** @deprecated 2.8.4 use xar::mem()->del() instead */
    public function delCached(string $scope, string $name): void
    {
        $this->getParent()->mem()->del($scope, $name);
    }

    /** @deprecated 2.8.4 use xar::mem()->hasPreload() instead */
    public function hasPreload(string $scope, ?string $name = null): bool
    {
        return $this->getParent()->mem()->hasPreload($scope, $name);
    }

    /** @deprecated 2.8.4 use xar::mem()->load() instead */
    public function loadCached(string $scope, ?string $name = null): bool
    {
        return $this->getParent()->mem()->load($scope, $name);
    }

    /** @deprecated 2.8.4 use xar::mem()->save() instead */
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
 * - validate() - or use $this->prep()->validate() instead
 * - prep() - @deprecated use $this->prep()->text() instead
 * - prepHTML() - @deprecated use $this->prep()->html() instead
 * - ...
 *
 */
class VariablesService implements VariablesInterface
{
    use VariablesTrait;
}
