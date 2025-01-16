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
use sys;

sys::import('xaraya.services.servicetrait');

/**
 * For documentation purposes only - available via VariablesTrait
 */
interface VariablesInterface extends ServiceInterface
{
    /**
     * Fetch variable by name, with validation, default, flags and prep
     *
     * @param string $name the variable name
     * @param string $validation the validation to be performed
     * @param mixed $value contains the converted value of fetched variable
     * @param mixed $defaultValue the default value
     * @param integer $flags bitmask which modify the behaviour of function
     * @param integer $prep will prep the value with xarVarPrepForDisplay, xarVarPrepHTMLDisplay, or dbconn->qstr()
     * @return mixed
     */
    public function fetch($name, $validation, &$value, $defaultValue = null, $flags = xarVar::GET_OR_POST, $prep = xarVar::PREP_FOR_NOTHING): mixed;

    /**
     * Get variable by name
     *
     * Simplified fetch() with validation='isset', defaultValue=null, flags=xarVar::DONT_SET, prep=xarVar::PREP_FOR_NOTHING
     *
     * @uses xarVar::fetch()
     * @param string $name the variable name
     * @param mixed $value contains the converted value of fetched variable
     * @param string $validation the validation to be performed (default 'isset')
     * @return mixed
     */
    public function get($name, &$value, $validation = 'isset'): mixed;
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
     * Fetch variable by name, with validation, defaultValue, flags and prep
     *
     * @uses xarVar::fetch()
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
        // Note: this should be restricted to gui methods
        return xarVar::fetch($name, $validation, $value, $defaultValue, $flags, $prep);
    }

    /**
     * Get variable by name if the value is not already set
     *
     * Simplified fetch() with validation='isset', defaultValue=null, flags=xarVar::DONT_SET, prep=xarVar::PREP_FOR_NOTHING
     *
     * @uses xarVar::fetch()
     * @param string $name the variable name
     * @param mixed $value contains the converted value of fetched variable
     * @param string $validation the validation to be performed (default 'isset')
     * @return mixed
     */
    public function get($name, &$value, $validation = 'isset'): mixed
    {
        // Note: this should be restricted to gui methods
        return xarVar::fetch($name, 'isset', $value, null, xarVar::DONT_SET, xarVar::PREP_FOR_NOTHING);
    }

    /**
     * Prepare text for web display
     *
     * @param string ...$args
     * @return mixed
     */
    public function prep(...$args)
    {
        return xarVar::prepForDisplay(...$args);
    }
}

/**
 * Access xarVar::* Variables methods (fetch, get, prep, ...)
 *
 * Available methods:
 * - fetch()
 * - get()
 * - prep()
 * - ...
 *
 * @template TParent of ServicesInterface
 */
class VariablesService implements VariablesInterface
{
    /** @use VariablesTrait<TParent> */
    use VariablesTrait;
}
