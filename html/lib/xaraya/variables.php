<?php

/**
 * Variable utilities
 *
 * @package core\variables
 * @category Xaraya Web Applications Framework
 * @version 2.8.4
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marco Canini marco@xaraya.com
 * @author Flavio Botelho
 */

use Xaraya\Services\MemoryService;
use Xaraya\Services\VariablesService;
use Xaraya\Services\xar;

/**
 * Exception raised by the variables subsystem
 *
 * @package core\variables
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
**/
class VariableValidationException extends ValidationExceptions
{
    protected $message = 'The variable "#(1)" [Value: "#(2)"] did not comply with the required validation: "#(3)"';
}

/**
 * Exception raised by the variables subsystem
 *
 * @package core\exceptions
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @todo this exception is too weak
 *
**/
class ConfigurationException extends ConfigurationExceptions
{
    protected $message = 'There is an unknown configuration error detected.';
}

/**
 * Interface declaration for classes dealing with sets of variables
 *
 * @package core\variables
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marco Canini marco@xaraya.com
 * @author Flavio Botelho
 */
interface IxarVars
{
    public static function get($scope, $name);
    public static function set($scope, $name, $value);
    public static function delete($scope, $name);
}

/**
 * Base class for variable handling in core
 *
 * @package core\variables
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marcel van der Boom <mrb@hsdev.com>
 */

class xarVars extends xarObject {}

/**
 * Interface for variables service
 */
interface ixarVar
{
    public const ALLOW_NO_ATTRIBS = 1;
    public const ALLOW            = 2;

    public const GET_OR_POST      = 0;
    public const GET_ONLY         = 2;
    public const POST_ONLY        = 4;

    public const NOT_REQUIRED     = 64;
    public const DONT_SET         = 128;
    public const DONT_REUSE       = 256;
}

/**
 * @package core\variables
 * @deprecated 2.8.4 use xar::var() or xar::mem() instead
 */
class xarVar extends xarObject implements ixarVar
{
    /** @deprecated 2.8.4 use xarVarPrep::* instead */
    public const PREP_FOR_NOTHING = 0;
    public const PREP_FOR_DISPLAY = 1;
    public const PREP_FOR_HTML    = 2;
    public const PREP_FOR_STORE   = 16;
    public const PREP_TRIM        = 8;

    protected static ?MemoryService $memService = null;
    protected static ?VariablesService $varService = null;

    protected static function mem(): MemoryService
    {
        if (!isset(self::$memService)) {
            $xar = xar::getServicesClass();
            self::$memService = $xar->mem();
            self::$varService = $xar->var();
        }
        return self::$memService;
    }

    protected static function var(): VariablesService
    {
        if (!isset(self::$varService)) {
            $xar = xar::getServicesClass();
            self::$memService = $xar->mem();
            self::$varService = $xar->var();
        }
        return self::$varService;
    }

    /**
     * Initialise the variable handling options
     *
     * Sets up allowable html and htmlentities options
     *
     * @param array<mixed> $args
     * @return boolean
     * @todo revisit naming of config_vars table
    **/
    public static function init(array $args = [])
    {
        // static cache for migration
        self::$memService = null;
        self::$varService = null;
        return self::var()->init($args);
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
     * @param mixed $batch
     * @return array<mixed> With the respective exceptions in case of failure
    **/
    public static function batchFetch(...$batch)
    {
        return self::var()->batchFetch(...$batch);
    }

    /**
     * Fetches the $name variable from input variables and validates it by applying the $validation rules.
     *
     *
     * @param string $name the variable name
     * @param string $validation the validation to be performed
     * @param mixed $value contains the converted value of fetched variable
     * @param mixed $defaultValue the default value
     * @param integer $flags bitmask which modify the behaviour of function
     * @param integer $prep will prep the value with xarVarPrep::text, xarVarPrep::html, or dbconn->qstr()
     * @throws EmptyParameterException
     * @throws VariableValidationException
     * @return true
     * @todo  get rid of the explicit value of xarVar::GET_OR_POST, use the bitmas (i.e. GET_OR_POST = GET + POST)
     * @todo  make dont_set and dont_reuse are too similar (conceptually) which make the code below confusing [phpdoc above implies REUSE is the default]
     * @todo  re-evaluate the prepping, prepforstore is deprecated for example, prep for display and prep for html are partially exclusive
    **/
    public static function fetch($name, $validation, &$value, $defaultValue = null, $flags = self::GET_OR_POST, $prep = xarVarPrep::NOTHING)
    {
        return self::var()->fetch($name, $validation, $value, $defaultValue, $flags, $prep);
    }

    /**
     * Validates a variable performing the $validation test type on $subject.
     *
     * @param mixed $validation the validation to be performed
     * @param string $subject the subject on which the validation must be performed, will be where the validated value will be returned
     * @throws EmptyParameterException
     * @throws VariableValidationException
     * @return boolean true if the $subject validates correctly, false otherwise
     * @deprecated 2.8.4 use xarVarPrep::validate() instead
     */
    public static function validate($validation, &$subject, $supress = false, $name = '')
    {
        return xarVarPrep::validate($validation, $subject, $supress, $name);
    }

    /**@+
     * Wrapper functions for var caching as in Xaraya 1 API
     * See the documentation of public xar::mem()->* methods for details
     *
     * @see xarCore
     */
    public static function isCached($scope, $name)
    {
        return self::mem()->has($scope, $name);
    }

    public static function getCached($scope, $name)
    {
        return self::mem()->get($scope, $name);
    }

    public static function setCached($scope, $name, $value)
    {
        self::mem()->set($scope, $name, $value);
    }

    public static function delCached($scope, $name)
    {
        self::mem()->del($scope, $name);
    }

    public static function flushCached($scope)
    {
        self::mem()->flush($scope);
    }

    public static function prepForDisplay(...$args)
    {
        // pass along the function arguments as is
        return xarVarPrep::text(...$args);
    }

    public static function prepHTMLDisplay(...$args)
    {
        // pass along the function arguments as is
        return xarVarPrep::html(...$args);
    }

    public static function prepEmailDisplay(...$args)
    {
        // pass along the function arguments as is
        return xarVarPrep::email(...$args);
    }

    public static function prepForOS(...$args)
    {
        // pass along the function arguments as is
        return xarVarPrep::path(...$args);
    }
}

/*
    ---------------------------------------------------------------------
    * xarVarPrep* -- the rest, only one of them is needed usually, maybe one to
         - escape XML
         - another to escape HTML.

    * Allowed HTML - how to handle that? imo it should be on input... The necessary function can be
      offered here. If it's an allowed html input, do not escape on the output.
                   - Why? Because the allowed html can change depending on the user - Would you
                     want to check everytime if the author user is able to send such html?
                   - The Allowed HTML can change between a post and it's view. That would display
                     escaped html, which shouldnt...
    ----------------------------------------------------------------------
*/

class xarVarPrep
{
    public const NOTHING = 0;
    public const TEXT    = 1;
    public const HTML    = 2;
    public const PATH    = 4;
    public const TRIM    = 8;
    public const STORE   = 16;

    public static $dbCharSet = 'utf8';
    public static $allowableHTML = [];
    public static $fixHTMLEntities = true;
    protected static bool $initialized = false;

    /**
     * Initialise the variable prep options
     *
     * Sets up allowable html and htmlentities options
     *
     * @param array<mixed> $args
     * @return boolean
     * @todo <mrb> remove the two settings allowablehtml and fixhtmlentities
    **/
    public static function init(array $args = [])
    {
        if (empty($args) && self::$initialized) {
            return true;
        }
        $xar = xar::getServicesClass();

        self::$dbCharSet = $xar->system()->getVar(sys::CONFIG, 'DB.Charset');
        self::$allowableHTML = $xar->config()->getVar('Site.Core.AllowableHTML', []);
        self::$fixHTMLEntities = $xar->config()->getVar('Site.Core.FixHTMLEntities', true);

        self::$initialized = true;
        return true;
    }

    /**
     * Validates a variable performing the $validation test type on $variable.
     *
     * The $validation parameter could be a string, in this case the
     * supported validation types are very basilar, they are the following:
     *
     * 'id' matches a positive integer (0 excluded)
     *
     * 'int:<min val>:<max val>' matches an integer between <min val> and <max val> (included), if <min val>
     *                           is not present no lower bound check is performed, the same applies to <max val>
     *
     * 'float:<min val>:<max val>' matches a floating point number between <min val> and <max val> (included), if <min val>
     *                             is not present no lower bound check is performed, the same applies to <max val>
     *
     * 'bool' matches a string that can be 'true' or 'false'
     *
     * 'str:<min len>:<max len>' matches a string which has a lenght between <min len> and <max len>, if <min len>
     *                           is omitted no control is done on mininum lenght, the same applies to <max len>
     *
     * 'html:<level>' validates the subject by searching unallowed html tags, allowed tags are defined by specifying <level>
     *                that could be one of restricted, basic, enhanced, admin. This last level is not configurable and allows
     *                every tag
     *
     * 'array:<min elements>:<max elements>' validates if the subject is an array with the minimum and maximum
     *                                       of elements specified
     *
     * 'list' validates if the subject is a list
     * 'list: *other validation*' validates if the subject is an array, and if every element of the array
     *                            validates in the *other validation*
     *                          Example: xarVarPrep::validate('list:str:1:20', $strings_array);
     *
     * 'enum' validates if the subject is any of the parameters
     *                  Example: xarVarPrep::validate('enum:apple:orange:strawberry', $options);
     *
     * After the validation is performed, $convValue (passed by reference) is assigned to $subject converted the proper type.
     * Please note that conversions from string to integer or float are done by using the PHP built-in cast conversions,
     * refer to this page for the details:
     * http://www.php.net/manual/en/language.types.string.html#language.types.string.conversion
     *
     * The $validation parameter can be any of the implemented functions in html/modules/variable/validations/
     *
     * @param mixed $validation the validation to be performed
     * @param mixed $variable the subject on which the validation must be performed, will be where the validated value will be returned
     * @param bool $suppress suppress any exception if the validation fails or not (default false)
     * @param string $name (optional) name of the variable for the exception message
     * @throws EmptyParameterException
     * @throws VariableValidationException
     * @return bool true if the $variable validates correctly, false otherwise
     */
    public static function validate($validation, &$variable, $suppress = false, $name = ''): bool
    {
        $valParams = explode(':', $validation);
        $type = strtolower(array_shift($valParams));

        if (empty($type)) {
            throw new EmptyParameterException('type');
        }

        sys::import("xaraya.validations");
        $v = ValueValidations::get($type);

        try {
            // Now featuring without passing the name everywhere :-)
            $result = $v->validate($variable, $valParams);
            return $result;
        } catch (ValidationExceptions $e) {
            // If a validation exception occurred, we can optionally suppress it
            if (!$suppress) {
                // Rethrow with more verbose message
                if ($name == '') {
                    $name = '<unknown>';
                } // @todo MLS!
                throw new VariableValidationException([$name, $variable, $e->getMessage()]);
            }
        } catch (Exception $e) {
            // But not the others (note that this part is redundant)
            throw $e;
        }
        return false;
    }

    /**
     * Ready user output
     *
     * Gets a variable, cleaning it up such that the text is
     * shown exactly as expected. Can have as many parameters as desired.
     *
     * @param mixed $args
     * @return mixed prepared variable if only one variable passed
     * in, otherwise an array of prepared variables
     */
    public static function text(...$args)
    {
        $resarray = [];
        $charset = self::$dbCharSet;
        // stopgap for now. we need to agree on a naming convention for the charsets that won't confuse the hell out of everyone
        $charset = $charset == 'utf8' ? 'utf-8' : $charset;
        foreach ($args as $var) {
            if (is_bool($var)) {
                $var = $var ? 'true' : 'false';
            } elseif (!isset($var)) {
                $var = '';
            } else {
                // Prepare var
                try {
                    $var = htmlspecialchars($var, ENT_COMPAT, $charset);
                } catch (Exception $e) {
                    $var = htmlspecialchars($var);
                }
            }
            // Add to array
            $resarray[] = $var;
        }

        // Return vars
        if (count($args) == 1) {
            return $resarray[0];
        } else {
            return $resarray;
        }
    }

    /**
     * Ready HTML output
     *
     * Gets a variable, cleaning it up such that the text is
     * shown exactly as expected, except for allowed HTML tags which
     * are allowed through. Can have as many parameters as desired.
     *
     *
     * @param mixed $args
     * @return mixed prepared variable if only one variable passed
     * in, otherwise an array of prepared variables
     */
    public static function html(...$args)
    {
        // <nuncanada> Moving email obscurer functionality somewhere else : autolinks, transforms or whatever
        static $allowedtags = null;

        if (!isset($allowedtags)) {
            $allowedHTML = [];
            foreach (self::$allowableHTML as $k => $v) {
                if ($k == '!--') {
                    if ($v <> 0) {
                        $allowedHTML[] = "$k.*?--";
                    }
                } else {
                    switch ($v) {
                        case 0:
                            break;
                        case 1:
                            $allowedHTML[] = "/?$k\s*/?";
                            break;
                        case 2:
                            $allowedHTML[] = "/?$k(\s+[^>]*)?/?";
                            break;
                    }
                }
            }
            if (count($allowedHTML) > 0) {
                $allowedtags = '~<(' . join('|', $allowedHTML) . ')>~is';
            } else {
                $allowedtags = '';
            }
        }

        $resarray = [];
        foreach ($args as $var) {
            // Preparse var to mark the HTML that we want
            if (!empty($allowedtags)) {
                $var = preg_replace($allowedtags, "\022\\1\024", $var);
            }

            // Prepare var
            $var = htmlspecialchars($var);

            // Fix the HTML that we want
            /*
                    $var = preg_replace('/\022([^\024]*)\024/e',
                                        "'<' . strtr('\\1',
                                                        array('&gt;' => '>',
                                                            '&lt;' => '<',
                                                            '&quot;' => '\"',
                                                            '&amp;' => '&'))
                                        . '>';", $var);
            */
            $var = preg_replace_callback(
                '/\022([^\024]*)\024/',
                [self::class, 'htmlCallback'],
                $var
            );

            // Fix entities if required
            if (self::$fixHTMLEntities) {
                $var = preg_replace('/&amp;([a-z#0-9]+);/i', "&\\1;", $var);
            }

            // Add to array
            array_push($resarray, $var);
        }

        // Return vars
        if (count($args) == 1) {
            return $resarray[0];
        } else {
            return $resarray;
        }
    }

    public static function htmlCallback($matches)
    {
        return '<' . strtr(
            $matches[1],
            ['&gt;' => '>',
                '&lt;' => '<',
                '&quot;' => '"',
                '&amp;' => '&']
        )
            . '>';
    }

    /**
     * Ready obfuscated e-mail output
     *
     * Gets a variable, cleaning it up such that e-mail addresses are
     * slightly obfuscated against e-mail harvesters.
     *
     *
     * @param mixed $args
     * @return mixed prepared variable if only one variable passed
     * in, otherwise an array of prepared variables
     * @todo this looks like something for the mail module or an EmailAddress class somewhere
     */
    public static function email(...$args)
    {
        /*
            // This search and replace finds the text 'x@y' and replaces
            // it with HTML entities, this provides protection against
            // email harvesters
            //
            // Note that the use of \024 and \022 are needed to ensure that
            // this does not break HTML tags that might be around either
            // the username or the domain name
            static $search = array('/([^\024])@([^\022])/se');

            static $replace = array('"&#" .
                                    sprintf("%03d", ord("\\1")) .
                                    ";&#064;&#" .
                                    sprintf("%03d", ord("\\2")) . ";";');

        */
        $resarray = [];
        foreach ($args as $var) {
            // Prepare var
            //        $var = preg_replace($search, $replace, $var);
            $var = strtr($var, ['@' => '&#064;']);
            // Add to array
            array_push($resarray, $var);
        }

        // Return vars
        if (count($args) == 1) {
            return $resarray[0];
        } else {
            return $resarray;
        }
    }

    /**
     * Ready operating system output
     *
     * Gets a variable, cleaning it up such that any attempts
     * to access files outside of the scope of the Xaraya
     * system is not allowed. Can have as many parameters as desired.
     *
     *
     * @param mixed $args
     * @return mixed prepared variable if only one variable passed
     * in, otherwise an array of prepared variables
     *
     * @todo the / also prevents relative access in some cases (template tag for example)
     * @todo this puts responsibility on callee to know how things work, and gets a mangled name back, not very nice
     * @todo make it have 1 return type
     */
    public static function path(...$args)
    {
        static $special_characters = [':'  => ' ',  // c:\foo\bar
            '/'  => ' ',  // /etc/passwd
            '\\' => ' ',  // \\financialserver\fire.these.people
            '..' => ' ',  // ../../../etc/passwd
            '?'  => ' ',  // wildcard
            '*'  => ' ']; // wildcard

        foreach ($args as $key => $var) {
            // Remove out bad characters
            $args[$key] = strtr($var, $special_characters);
        }


        // Return vars
        if (count($args) == 1) {
            return $args[0];
        } else {
            return $args;
        }
    }
}

/**
 * Ready user output
 * @deprecated 2.8.4 use xarVarPrep::text() instead
 */
function xarVarPrepForDisplay(...$args)
{
    return xarVarPrep::text(...$args);
}

/**
 * Ready HTML output
 * @deprecated 2.8.4 use xarVarPrep::html() instead
 */
function xarVarPrepHTMLDisplay(...$args)
{
    return xarVarPrep::html(...$args);
}

/**
 * Ready obfuscated e-mail output
 * @deprecated 2.8.4 use xarVarPrep::email() instead
 */
function xarVarPrepEmailDisplay(...$args)
{
    return xarVarPrep::email(...$args);
}

/**
 * Ready operating system output
 * @deprecated 2.8.4 use xarVarPrep::path() instead
 */
function xarVarPrepForOS(...$args)
{
    return xarVarPrep::path(...$args);
}
