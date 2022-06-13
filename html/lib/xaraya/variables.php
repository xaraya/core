<?php
/**
 * Variable utilities
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
    static function get       ($scope, $name);
    static function set       ($scope, $name, $value);
    static function delete    ($scope, $name);
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
class xarVars extends xarObject
{

}

/**
 * Move public static functions to class
 * @package core\variables
 */
class xarVar extends xarObject
{
    const ALLOW_NO_ATTRIBS = 1;
    const ALLOW            = 2;

    const GET_OR_POST      = 0;
    const GET_ONLY         = 2;
    const POST_ONLY        = 4;

    const NOT_REQUIRED     = 64;
    const DONT_SET         = 128;
    const DONT_REUSE       = 256;

    const PREP_FOR_NOTHING = 0;
    const PREP_FOR_DISPLAY = 1;
    const PREP_FOR_HTML    = 2;
    const PREP_FOR_STORE   = 4;
    const PREP_TRIM        = 8;

    public static $allowableHTML = array();
    public static $fixHTMLEntities = true;

    /**
     * Initialise the variable handling options
     *
     * Sets up allowable html and htmlentities options
     *
     * 
     * @global xarVar_allowableHTML array
     * @global xarVar_fixHTMLEntities bool
     * @param args array
     * @return boolean
     * @todo <mrb> remove the two settings allowablehtml and fixhtmlentities
     * @todo revisit naming of config_vars table
    **/
    public static function init(array $args = array())
    {
        // Configuration init needs to be done first
        $tables = array('config_vars' => xarDB::getPrefix() . '_module_vars');

        xarDB::importTables($tables);

        // Initialise the variable cache
        sys::import('xaraya.variables.config');
	self::$allowableHTML = xarConfigVars::get(null, 'Site.Core.AllowableHTML', array());
	self::$fixHTMLEntities = xarConfigVars::get(null, 'Site.Core.FixHTMLEntities',true);

        return true;
    }

    /**
     * Fetches and validates in a Batch.
     *
     *   if (!xarVar::fetch('reassign', 'checkbox',  $reassign, false, xarVar::NOT_REQUIRED)) return;
     *   if (!xarVar::fetch('repeat',   'int:1:100', $repeat,   1,     xarVar::NOT_REQUIRED)) return;
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
     *
     * 
     * @param arrays The arrays storing information equivalent to the xarVar::fetch interface
     * @return array With the respective exceptions in case of failure
    **/
    public static function batchFetch()
    {

        $batch = func_get_args();

        $result_array = array();
        $no_errors    = true;

        foreach ($batch as $line) {
            $result_array[$line[2]] = array();
            try {
                $result = self::fetch($line[0], $line[1], $result_array[$line[2]]['value'], isset($line[3])?$line[3]:NULL, isset($line[4])?$line[4]:self::GET_OR_POST);
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
     * Fetches the $name variable from input variables and validates it by applying the $validation rules.
     *
     * 1st try to use the variable provided, if this is not set (Or the xarVar::DONT_REUSE flag is used)
     * then try to get the variable from the input (POST/GET methods for now)
     *
     * Then tries to validate the variable thru xarVar::validate.
     *
     * See xarVar::validate for details about nature of $validation.
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
     * You can force xarVar::fetch not to reuse the variable by setting
     * the $flag parameter to xarVar::DON_REUSE.
     *
     * By default $flag is xarVar::GET_OR_POST which means tha xarVar::fetch will lookup both GET and POST parameters and
     * that if the variable is not present or doesn't validate correctly an exception will be raised.
     *
     * The $prep flag will prepare $value by passing it to one of the following:
     *   xarVar::PREP_FOR_NOTHING:    no prep (default)
     *   xarVar::PREP_FOR_DISPLAY:    xarVarPrepForDisplay($value)
     *   xarVar::PREP_FOR_HTML:       xarVarPrepHTMLDisplay($value)
     *   xarVar::PREP_FOR_STORE:      dbconn->qstr($value)
     *   xarVar::PREP_TRIM:           trim($value)
     *
     * 
     * @param name string the variable name
     * @param validation string the validation to be performed
     * @param value mixed contains the converted value of fetched variable
     * @param defaultValue mixed the default value
     * @param flags integer bitmask which modify the behaviour of function
     * @param prep will prep the value with xarVarPrepForDisplay, xarVarPrepHTMLDisplay, or dbconn->qstr()
     * @return mixed
     * @todo  get rid of the explicit value of xarVar::GET_OR_POST, use the bitmas (i.e. GET_OR_POST = GET + POST)
     * @todo  make dont_set and dont_reuse are too similar (conceptually) which make the code below confusing [phpdoc above implies REUSE is the default]
     * @todo  re-evaluate the prepping, prepforstore is deprecated for example, prep for display and prep for html are partially exclusive
    **/
    public static function fetch($name, $validation, &$value, $defaultValue = NULL, $flags = self::GET_OR_POST, $prep = self::PREP_FOR_NOTHING)
    {
        assert(is_int($flags));
        assert(empty($name) || preg_match("/^[a-zA-Z0-9_\[\]\"\x7f-\xff][a-zA-Z0-9_\[\]\"\x7f-\xff]*$/", $name));

        $allowOnlyMethod = null;
        if ($flags & self::GET_ONLY) $allowOnlyMethod = 'GET';
        if ($flags & self::POST_ONLY) $allowOnlyMethod = 'POST';

        // xarVar::DONT_SET does not set $value, if there already is one
        // This allows us to have a extract($args) before the xarVar::fetch and still run
        // the variables thru the tests here.
        $oldValue = null;
        if (isset($value) && $flags & self::DONT_SET) $oldValue = $value;

        // xarVar::DONT_REUSE fetches the variable, regardless
        // FIXME: this flag doesn't seem to work !?
        // mrb: what doesn't work then? seems ok within the given workings
        // --------v  this is kinda confusing though, especially when dont_set is used as flag.
        if (!isset($value) || ($flags & self::DONT_REUSE)) {
            $value = xarController::getVar($name, $allowOnlyMethod);
        }

        // Suppress validation warnings when dont_set, not_required or a default value is specified
        $supress = (($flags & self::DONT_SET) || ($flags & self::NOT_REQUIRED) || isset($defaultValue));
        // Validate the $value given
        $validated = self::validate($validation, $value, $supress, $name);

        if (!$validated) {
            // The value does not validate
            $value = null; // we first make sure that this is what we expect to return

            // Perhaps the default or old can be returned?
            if (($flags & self::NOT_REQUIRED) || isset($defaultValue)) {
                // CHECKME:  even for the xarVar::DONT_SET flag !?
                // if you set a non-null default value, assume you want to use it here
                $value = $defaultValue;
            } elseif (($flags & self::DONT_SET) && isset($oldValue) && self::validate($validation, $oldValue, $supress)) {
                // with xarVar::DONT_SET, make sure we don't pass invalid old values back either
                $value = $oldValue;
            }
        } else {
            // Value is ok, handle preparation of that value
            if ($prep & self::PREP_FOR_DISPLAY) $value = xarVarPrepForDisplay($value);
            if ($prep & self::PREP_FOR_HTML)    $value = xarVarPrepHTMLDisplay($value);

            // TODO: this is used nowhere, plus it introduces a db connection here which is of no use
            if ($prep & self::PREP_FOR_STORE) {
                $dbconn = xarDB::getConn();
                $value = $dbconn->qstr($value);
            }

            if ($prep & self::PREP_TRIM) $value = trim($value);
        }
        return true;
    }

    /**
     * Validates a variable performing the $validation test type on $subject.
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
     *                          Example: xarVar::validate('list:str:1:20', $strings_array);
     *
     * 'enum' validates if the subject is any of the parameters
     *                  Example: xarVar::validate('enum:apple:orange:strawberry', $options);
     *
     * After the validation is performed, $convValue (passed by reference) is assigned to $subject converted the proper type.
     * Please note that conversions from string to integer or float are done by using the PHP built-in cast conversions,
     * refer to this page for the details:
     * http://www.php.net/manual/en/language.types.string.html#language.types.string.conversion
     *
     * The $validation parameter can be any of the implemented functions in html/modules/variable/validations/
     *
     * 
     * @param validation mixed the validation to be performed
     * @param subject string the subject on which the validation must be performed, will be where the validated value will be returned
     * @throws EmptyParameterException
     * @return boolean true if the $subject validates correctly, false otherwise
     */
    public static function validate($validation, &$subject, $supress = false, $name = '')
    {
        $valParams = explode(':', $validation);
        $type = strtolower(array_shift($valParams));

        if (empty($type)) throw new EmptyParameterException('type');

        sys::import("xaraya.validations");
        $v = ValueValidations::get($type);

        try {
            // Now featuring without passing the name everywhere :-)
            $result = $v->validate($subject, $valParams);
            return $result;
        } catch (ValidationExceptions $e) {
            // If a validation exception occurred, we can optionally suppress it
            if(!$supress) {
                // Rethrow with more verbose message
                if($name == '') $name = '<unknown>'; // @todo MLS!
                throw new VariableValidationException(array($name,$subject,$e->getMessage()));
            }
        } catch(Exception $e) {
            // But not the others (note that this part is redundant)
            throw $e;
        }
    }

    /**@+
     * Wrapper functions for var caching as in Xaraya 1 API
     * See the documentation of protected xarCoreCache::*Cached for details
     * 
     * @see xarCore
     */
    public static function isCached($scope, $name)
    {
        return xarCoreCache::isCached($scope, $name);
    }

    public static function getCached($scope, $name)
    {
        return xarCoreCache::getCached($scope, $name);
    }

    public static function setCached($scope, $name, $value)
    {
        return xarCoreCache::setCached($scope, $name, $value);
    }

    public static function delCached($scope, $name)
    {
        return xarCoreCache::delCached($scope, $name);
    }

    public static function flushCached($scope)
    {
        return xarCoreCache::flushCached($scope);
    }

    public static function prepForDisplay(...$args)
    {
        // pass along the function arguments as is
        return xarVarPrepForDisplay(...$args);
    }

    public static function prepHTMLDisplay(...$args)
    {
        // pass along the function arguments as is
        return xarVarPrepHTMLDisplay(...$args);
    }

    public static function prepEmailDisplay(...$args)
    {
        // pass along the function arguments as is
        return xarVarPrepEmailDisplay(...$args);
    }

    public static function prepForOS(...$args)
    {
        // pass along the function arguments as is
        return xarVarPrepForOS(...$args);
    }
}

/*
    ---------------------------------------------------------------------
    @todo LOOK AT  THIS, IT SEEMS ABANDONED, except for the transform of entities from named to numeric
    Everything below should be remade, working thru xarVarEscape or xarVarTransform
    * xarVarCleanFromInput
    * xarVarCleanUntrusted
    should disappear, there is nothing to prevent from input, that's not the way to add security.

    They produce a false feeling of security... Handy for stopping script kids, but the holes
    are still there, just harder to find.

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

/**
 * Ready user output
 *
 * Gets a variable, cleaning it up such that the text is
 * shown exactly as expected. Can have as many parameters as desired.
 *
 * 
 * @return mixed prepared variable if only one variable passed
 * in, otherwise an array of prepared variables
 */
function xarVarPrepForDisplay()
{
    $resarray = array();
    $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');
    // stopgap for now. we need to agree on a naming convention for the charsets that won't confuse the hell out of everyone
    $charset = $charset == 'utf8' ? 'utf-8' : $charset;
    foreach (func_get_args() as $var) {
        if (is_bool($var)) {
            $var = $var ? 'true' : 'false';
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
    if (func_num_args() == 1) {
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
 * @return mixed prepared variable if only one variable passed
 * in, otherwise an array of prepared variables
 */
function xarVarPrepHTMLDisplay()
{
// <nuncanada> Moving email obscurer functionality somewhere else : autolinks, transforms or whatever
    static $allowedtags = NULL;

    if (!isset($allowedtags)) {
        $allowedHTML = array();
        foreach(xarVar::$allowableHTML as $k=>$v) {
            if ($k == '!--') {
                if ($v <> 0) {
                    $allowedHTML[] = "$k.*?--";
                }
            } else {
                switch($v) {
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
            $allowedtags = '~<(' . join('|',$allowedHTML) . ')>~is';
        } else {
            $allowedtags = '';
        }
    }

    $resarray = array();
    foreach (func_get_args() as $var) {
        // Preparse var to mark the HTML that we want
        if (!empty($allowedtags))
            $var = preg_replace($allowedtags, "\022\\1\024", $var);

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
        $var = preg_replace_callback('/\022([^\024]*)\024/',
                                     'xarVarPrepHTMLDisplay__callback',
                                     $var);

        // Fix entities if required
        if (xarVar::$fixHTMLEntities) {
            $var = preg_replace('/&amp;([a-z#0-9]+);/i', "&\\1;", $var);
        }

        // Add to array
        array_push($resarray, $var);
    }

    // Return vars
    if (func_num_args() == 1) {
        return $resarray[0];
    } else {
        return $resarray;
    }
}

function xarVarPrepHTMLDisplay__callback($matches)
{
    return '<' . strtr($matches[1],
                       array('&gt;' => '>',
                             '&lt;' => '<',
                             '&quot;' => '"',
                             '&amp;' => '&'))
           . '>';
}

/**
 * Ready obfuscated e-mail output
 *
 * Gets a variable, cleaning it up such that e-mail addresses are
 * slightly obfuscated against e-mail harvesters.
 *
 * 
 * @return mixed prepared variable if only one variable passed
 * in, otherwise an array of prepared variables
 * @todo this looks like something for the mail module or an EmailAddress class somewhere
 */
function xarVarPrepEmailDisplay()
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
    $resarray = array();
    foreach (func_get_args() as $var) {
        // Prepare var
//        $var = preg_replace($search, $replace, $var);
        $var = strtr($var,array('@' => '&#064;'));
        // Add to array
        array_push($resarray, $var);
    }

    // Return vars
    if (func_num_args() == 1) {
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
 * @return mixed prepared variable if only one variable passed
 * in, otherwise an array of prepared variables
 *
 * @todo the / also prevents relative access in some cases (template tag for example)
 * @todo this puts responsibility on callee to know how things work, and gets a mangled name back, not very nice
 * @todo make it have 1 return type
 */
function xarVarPrepForOS()
{
    static $special_characters = array(':'  => ' ',  // c:\foo\bar
                                       '/'  => ' ',  // /etc/passwd
                                       '\\' => ' ',  // \\financialserver\fire.these.people
                                       '..' => ' ',  // ../../../etc/passwd
                                       '?'  => ' ',  // wildcard
                                       '*'  => ' '); // wildcard

    $args = func_get_args();

    foreach ($args as $key => $var) {
        // Remove out bad characters
        $args[$key] = strtr($var, $special_characters);
    }


    // Return vars
    if (func_num_args() == 1) {
        return $args[0];
    } else {
        return $args;
    }
}

// Legacy calls - import by default for now...
sys::import('xaraya.legacy.variables');
