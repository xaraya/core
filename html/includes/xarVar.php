<?php
/**
 * File: $Id$
 * 
 * Variable utilities
 * 
 * @package variables
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Marco Canini m.canini@libero.it
 */

/**
 * Variables package defines
 */
define('XARVAR_ALLOW_NO_ATTRIBS', 1);
define('XARVAR_ALLOW', 2);

define('XARVAR_GET_OR_POST',  0);
define('XARVAR_GET_ONLY',     2);
define('XARVAR_POST_ONLY',    4);

define('XARVAR_NOT_REQUIRED', 64);
define('XARVAR_DONT_SET',     128);
define('XARVAR_DONT_REUSE',   256);

define('XARVAR_PREP_FOR_NOTHING', 0);
define('XARVAR_PREP_FOR_DISPLAY', 1);
define('XARVAR_PREP_FOR_HTML',    2);
define('XARVAR_PREP_FOR_STORE',   4);
define('XARVAR_PREP_TRIM',        8);

/**
 * Initialise the variable handling options
 *
 * Sets up allowable html and htmlentities options
 *
 * @access protected
 * @global xarVar_allowableHTML array
 * @global xarVar_fixHTMLEntities bool
 * @global xarVar_enableCensoringWords bool
 * @global xarVar_censoredWords array
 * @global xarVar_censoredWordsReplacers array
 * @param args array 
 * @param whatElseIsGoingLoaded integer
 * @return bool
 * @todo <johnny> fix the load level stuff here... it's inconsistant to the rest of the core
 * @todo <johnny> remove censored words and allowable HTML
 */
function xarVar_init($args, $whatElseIsGoingLoaded)
{
    /*
    $GLOBALS['xarVar_allowableHTML'] = $args['allowableHTML'];
    $GLOBALS['xarVar_fixHTMLEntities'] = $args['fixHTMLEntities'];

    return true;
    */
        $GLOBALS['xarVar_allowableHTML'] = xarConfigGetVar('Site.Core.AllowableHTML');
        if (!isset($GLOBALS['xarVar_allowableHTML']) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
            return; // throw back exception
        }

        $GLOBALS['xarVar_fixHTMLEntities'] = xarConfigGetVar('Site.Core.FixHTMLEntities');
        if (!isset($GLOBALS['xarVar_fixHTMLEntities']) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
            return; // throw back exception
        }

    return true;
}

/**
 * Fetches and validates in a Batch.
 *
 *   if (!xarVarFetch('reassign', 'checkbox',  $reassign, false, XARVAR_NOT_REQUIRED)) return;
 *   if (!xarVarFetch('repeat',   'int:1:100', $repeat,   1,     XARVAR_NOT_REQUIRED)) return;
 *
 *  Can be done thru xarVarBatchFetch with:
 *
 *  $result = xarVarBatchFetch(array('reassign','checkbox', 'reassign', false, XARVAR_NOT_REQUIRED),
 *                             array('repeat', 'int:1:100', 'repeat'));
 *
 * Notice that i didnt use XARVAR_NOT_REQUIRED because xarVarBatchFetch will trap the
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
 * @author Flavio Botelho
 * @access public
 * @param arrays The arrays storing information equivalent to the xarVarFetch interface
 * @return array With the respective exceptions in case of failure
 * @raise BAD_PARAM
 */
function xarVarBatchFetch() {

    $batch = func_get_args();
    
    $result_array = array();
    $no_errors    = true;

    foreach ($batch as $line) {
        $result_array[$line[2]] = array();
        $result = xarVarFetch($line[0], $line[1], $result_array[$line[2]]['value'], isset($line[3])?$line[3]:NULL, isset($line[4])?$line[4]:XARVAR_GET_OR_POST);
        
        if (!$result) {
            //Records the error presented in the given input variable
            $result_array[$line[2]]['error'] = xarExceptionValue();
            //Handle the Exception
            xarExceptionHandled();
            //Mark that we've got an error
            $no_errors = false;
        } else {
            $result_array[$line[2]]['error'] = '';
        }
    }
    
    //Chose this key name to avoid clashes and make it easy to go on if there is no
    //errors present in the Fetched variables.
    $result_array['no_errors'] = $no_errors;
    
    return $result_array;
}

/**
 * Fetches the $name variable from input variables and validates it by applying the $validation rules.
 *
 * 1st try to use the variable provided, if this is not set (Or the XARVAR_DONT_REUSE flag is used)
 * then it try to ge the variable from the input (POST/GET methods for now)
 *
 * Then tries to validate the variable thru xarVarValidate.  
 *
 * See xarVarValidate for details about nature of $validation.
 * After the call the $value parameter passed by reference is set to the variable value converted to the proper type
 * according to the validation applied.
 *
 * The $defaultValue provides a default value that is returned when the variable is not present or doesn't validate
 * correctly.
 *
 * The $flag parameter is a bitmask between the following constants: XARVAR_GET_OR_POST, XARVAR_GET_ONLY,
 * XARVAR_POST_ONLY, XARVAR_NOT_REQUIRED.
 *
 * You can force to get the variable only from GET parameters or POST parameters by setting the $flag parameter
 * to one of XARVAR_GET_ONLY or XARVAR_POST_ONLY.
 *
 * You can force xarVarFetch not to reuse the variable by setting  
 * the $flag parameter to XARVAR_DON_REUSE.
 *
 * By default $flag is XARVAR_GET_OR_POST which means tha xarVarFetch will lookup both GET and POST parameters and
 * that if the variable is not present or doesn't validate correctly an exception will be raised.
 *
 * The $prep flag will prepare $value by passing it to one of the following:
 *   XARVAR_PREP_FOR_NOTHING:    no prep (default)
 *   XARVAR_PREP_FOR_DISPLAY:    xarVarPrepForDisplay($value)
 *   XARVAR_PREP_FOR_HTML:       xarVarPrepHTMLDisplay($value)
 *   XARVAR_PREP_FOR_STORE:      xarVarPrepForStore($value)
 *   XARVAR_PREP_TRIM:           trim($value)
 *
 * @author Marco Canini
 * @access public
 * @param name string the variable name
 * @param validation string the validation to be performed
 * @param value mixed contains the converted value of fetched variable
 * @param defaultValue mixed the default value
 * @param flags integer bitmask which modify the behaviour of function
 * @param prep will prep the value with xarVarPrepForDisplay, xarVarPrepHTMLDisplay, or xarVarPrepForStore
 * @return mixed
 * @raise BAD_PARAM
 */
function xarVarFetch($name, $validation, &$value, $defaultValue = NULL, $flags = XARVAR_GET_OR_POST, $prep = XARVAR_PREP_FOR_NOTHING)
{
    assert('is_int($flags)');

    $allowOnlyMethod = NULL;
    if ($flags & XARVAR_GET_ONLY) $allowOnlyMethod = 'GET';
    if ($flags & XARVAR_POST_ONLY) $allowOnlyMethod = 'POST';

    //This allows us to have a extract($args) before the xarVarFetch and still run
    //the variables thru the tests here.
    //The FLAG here, stops xarVarFetch from reusing the variable if already present
    if (!isset($value) || ($flags & XARVAR_DONT_REUSE)) {
        $value = xarRequestGetVar($name, $allowOnlyMethod);
    }

    if (($flags & XARVAR_DONT_SET) || ($flags & XARVAR_NOT_REQUIRED) || isset($defaultValue)) {
        $supress = true;
    } else {
        $supress = false;
    }

    $result = xarVarValidate($validation, $value, $supress);
    
    if (xarExceptionMajor()) {return;} //Throw back

    // Check prep of $value
    if ($prep & XARVAR_PREP_FOR_DISPLAY) {
       $value = xarVarPrepForDisplay($value);
    }

    if ($prep & XARVAR_PREP_FOR_HTML) {
        $value = xarVarPrepHTMLDisplay($value);
    }

    if ($prep & XARVAR_PREP_FOR_STORE) {
        $value = xarVarPrepForStore($value);
    }

    if ($prep & XARVAR_PREP_TRIM) {
        $value = trim($value);
    }

    if ((!$result) &&
        (($flags & XARVAR_NOT_REQUIRED) || isset($defaultValue))) {
        $value = $defaultValue;
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
 *                            validate in the *other validation*
 *                          Example: xarVarValidate('list:str:1:20', $strings_array);
 *
 * 'enum' validates if the subject is any of the parameters
 *                  Example: xarVarValidate('enum:apple:orange:strawberry', $options);
 *
 * After the validation is performed, $convValue (passed by reference) is assigned to $subject converted the proper type.
 * Please note that conversions from string to integer or float are done by using the PHP built-in cast conversions,
 * refer to this page for the details:
 * http://www.php.net/manual/en/language.types.string.html#language.types.string.conversion
 *
 * The $validation parameter can be any of the implemented functions in html/modules/variable/validations/
 *
 * @author Marco Canini
 * @access public
 * @param validation mixed the validation to be performed
 * @param subject string the subject on which the validation must be performed, will be where the validated value will be returned
 * @return bool true if the $subject validates correctly, false otherwise
 */
function xarVarValidate($validation, &$subject, $supress = false) {
// <nuncanada> For now, i have moved all validations to html/modules/variable/validations
//             I think that will incentivate 3rd party devs to create and send new validations back to us..
//             As id/int/str are used in every page view, probably they should be here.

    $valParams = explode(':', $validation);
    $valType = xarVarPrepForOS(strtolower(array_shift($valParams)));
    
    if (empty($valType)) {
        // Raise an exception
        $msg = xarML('No validation type present.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    $function_file = './includes/validations/'.$valType.'.php';
    $function_name = 'variable_validations_'.$valType;

    if (!function_exists($function_name)) {
        if (file_exists($function_file)) {
            include_once($function_file);
        }
    }

    if (function_exists($function_name)) {
        $return = $function_name($subject, $valParams, $supress);
        //The helper functions already have a nicer interface, let?s change the main function too?
        return $return;
    } else {
        // Raise an exception
        $msg = xarML('The validation type \'#(1)\' couldn\'t be found.', $valType);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }
}

/**
 * Cleans a variable.
 *
 *
 * Cleaning it up to try to ensure that hack attacks
 * don't work. Typically used for cleaning variables
 * coming from user input.
 *
 * @access public
 * @param var variable to clean
 * @return string prepared variable
 */
function xarVarCleanUntrusted($var)
{
    $search = array('|</?\s*SCRIPT[^>]*>|si',
                    '|</?\s*FRAME[^>]*>|si',
                    '|</?\s*OBJECT[^>]*>|si',
                    '|</?\s*META[^>]*>|si',
                    '|</?\s*APPLET[^>]*>|si',
                    '|</?\s*LINK[^>]*>|si',
                    '|</?\s*IFRAME[^>]*>|si',
                    '|STYLE\s*=\s*"[^"]*"|si');
    // <?
    $replace = array('');
    // Clean var
    $var = preg_replace($search, $replace, $var);

    return $var;
}

/**
 * Clean user input
 *
 * Gets a global variable, cleaning it up to try to ensure that
 * hack attacks don't work. Can have as many parameters as needed.
 *
 * @access public
 * @return mixed prepared variable if only one variable passed in, otherwise an array of prepared variables
 * @todo <marco> FIXME: This function will not work if the security system is not loaded!
 */
function xarVarCleanFromInput()
{
    $resarray = array();
    foreach (func_get_args() as $name) {
        if (empty($name)) {
            // you sure you want to return like this ?
            return;
        }

        $var = xarRequestGetVar($name);
        if (!isset($var)) {
            array_push($resarray, NULL);
            continue;
        }

        // TODO: <marco> Document this security check!
        if (!function_exists('xarSecurityCheck') || !xarSecurityCheck('AdminAll',0)) {
            $var = xarVarCleanUntrusted($var);
        }

        // Add to result array
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
 * Ready user output
 *
 * Gets a variable, cleaning it up such that the text is
 * shown exactly as expected. Can have as many parameters as desired.
 *
 * @access public
 * @return mixed prepared variable if only one variable passed
 * in, otherwise an array of prepared variables
 */
function xarVarPrepForDisplay()
{
// <nuncanada> Moving email obscurer functionality somewhere else : autolinks, transforms or whatever
/*
    // This search and replace finds the text 'x@y' and replaces
    // it with HTML entities, this provides protection against
    // email harvesters
    static $search = array('/(.)@(.)/se');

    static $replace = array('"&#" .
                            sprintf("%03d", ord("\\1")) .
                            ";&#064;&#" .
                            sprintf("%03d", ord("\\2")) . ";";');

*/
    $resarray = array();
    foreach (func_get_args() as $var) {

        // Prepare var
        $var = htmlspecialchars($var);

//        $var = preg_replace($search, $replace, $var);

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
 * Ready HTML output
 *
 * Gets a variable, cleaning it up such that the text is
 * shown exactly as expected, except for allowed HTML tags which
 * are allowed through. Can have as many parameters as desired.
 *
 * @access public
 * @return mixed prepared variable if only one variable passed
 * in, otherwise an array of prepared variables
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarVarPrepHTMLDisplay()
{
// <nuncanada> Moving email obscurer functionality somewhere else : autolinks, transforms or whatever
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
    static $allowedtags = NULL;

    if (!isset($allowedHTML)) {
        $allowedHTML = array();
        foreach($GLOBALS['xarVar_allowableHTML'] as $k=>$v) {
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
//        $var = preg_replace($search, $replace, $var);
//        $var = strtr($var,array('@' => '&#064;'));

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
        if ($GLOBALS['xarVar_fixHTMLEntities']) {
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
 * Ready database output
 *
 * Gets a variable, cleaning it up such that the text is
 * stored in a database exactly as expected. Can have as many parameters as desired.
 *
 * @access public
 * @return mixed prepared variable if only one variable passed
 * in, otherwise an array of prepared variables
 * @todo are we allowing arrays and objects for real?
 */
function xarVarPrepForStore()
{
    //Does the quoting change from database to database?
    //If so, this should be done thru ADODB instead of an API functions like this

    $resarray = array();
    foreach (func_get_args() as $var) {

        // Prepare var
        if (!get_magic_quotes_runtime()) {
            // FIXME: allow other than strings?
            $var = addslashes($var);
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

/**
 * Ready operating system output
 *
 * Gets a variable, cleaning it up such that any attempts
 * to access files outside of the scope of the Xaraya
 * system is not allowed. Can have as many parameters as desired.
 *
 * @access public
 * @return mixed prepared variable if only one variable passed
 * in, otherwise an array of prepared variables
 */
function xarVarPrepForOS()
{
    static $special_characters = array(':'  => ' ',
                                       '/'  => ' ',
                                       '\\' => ' ',
                                       '..' => ' ',
                                       '?'  => ' ',
                                       '*'  => ' ');

    $args = func_get_args();
    
    foreach ($args as $key => $var) {
        // Remove out bad characters
        $args[$key] = strtr($var, $special_characters);
    }
    

    // Return vars
    // <nuncanada> I really dont like this kind of behaviour... It?s not consistent.
    if (func_num_args() == 1) {
        return $args[0];
    } else {
        return $args;
    }
}

/*
 * Functions providing variable caching (within a single page request)
 *
 * Example :
 *
 * if (xarVarIsCached('MyCache', 'myvar')) {
 *     $var = xarVarGetCached('MyCache', 'myvar');
 * }
 * ...
 * xarVarSetCached('MyCache', 'myvar', 'this value');
 * ...
 * xarVarDelCached('MyCache', 'myvar');
 * ...
 * xarVarFlushCached('MyCache');
 * ...
 *
 */

/*
 * Initialise the variable cache
 */
$GLOBALS['xarVar_cacheCollection'] = array();

/**
 * Check if the value of a variable is available in cache or not
 *
 * @access public
 * @global xarVar_cacheCollection array
 * @param key string the key identifying the particular cache you want to access
 * @param name string the name of the variable in that particular cache
 * @return true bool if the variable is available in cache, false if not
 */
function xarVarIsCached($cacheKey, $name)
{
    if (!isset($GLOBALS['xarVar_cacheCollection'][$cacheKey])) {
        $GLOBALS['xarVar_cacheCollection'][$cacheKey] = array();
        return false;
    }
    return isset($GLOBALS['xarVar_cacheCollection'][$cacheKey][$name]);
}

/**
 * Get the value of a cached variable
 *
 * @access public
 * @global xarVar_cacheCollection array
 * @param key string the key identifying the particular cache you want to access
 * @param name string the name of the variable in that particular cache
 * @return mixed value of the variable, or void if variable isn't cached
 */
function xarVarGetCached($cacheKey, $name)
{
    if (!isset($GLOBALS['xarVar_cacheCollection'][$cacheKey][$name])) {
        return;
    }
    return $GLOBALS['xarVar_cacheCollection'][$cacheKey][$name];
}

/**
 * Set the value of a cached variable
 *
 * @access public
 * @global xarVar_cacheCollection array
 * @param key string the key identifying the particular cache you want to access
 * @param name string the name of the variable in that particular cache
 * @param value string the new value for that variable
 * @return void
 */
function xarVarSetCached($cacheKey, $name, $value)
{
    $GLOBALS['xarVar_cacheCollection'][$cacheKey][$name] = $value;
}

/**
 * Delete a cached variable
 *
 * @access public
 * @global xarVar_cacheCollection array
 * @param key the key identifying the particular cache you want to access
 * @param name the name of the variable in that particular cache
 */
function xarVarDelCached($cacheKey, $name)
{
    // TODO: check if we don't need to work with $GLOBALS here for some PHP ver
    if (isset($GLOBALS['xarVar_cacheCollection'][$cacheKey][$name])) {
        unset($GLOBALS['xarVar_cacheCollection'][$cacheKey][$name]);
    }
}

/**
 * Flush a particular cache (e.g. for session initialization)
 *
 * @access public
 * @global xarVar_cacheCollection array
 * @param cacheKey the key identifying the particular cache you want to wipe out
 */
function xarVarFlushCached($cacheKey)
{
    // TODO: check if we don't need to work with $GLOBALS here for some PHP ver
    if (isset($GLOBALS['xarVar_cacheCollection'][$cacheKey])) {
        unset($GLOBALS['xarVar_cacheCollection'][$cacheKey]);
    }
}


/**
 * Stripslashes on multidimensional arrays.
 *
 * Used in conjunction with xarVarCleanFromInput
 *
 * @access protected
 * @param &var any variables or arrays to be stripslashed
 */
function xarVar_stripSlashes(&$var)
{
    if(!is_array($var)) {
        $var = stripslashes($var);
    } else {
        array_walk($var,'xarVar_stripSlashes');
    }
}

function xarVar_addSlashes($var)
{
    return str_replace(array("\\",'"'), array("\\\\",'\"'), $var);
}

/**
 * Get allowed tags based on $level
 *
 * @access private
 * @static restricted array
 * @static basic array
 * @static enhanced array
 * @param level string
 * @return array
 */

function xarVar__getAllowedTags($level)
{
    static $restricted = NULL;
    static $basic = NULL;
    static $enhanced = NULL;
    switch ($level) {
        case 'restricted':
            if ($restricted == NULL) {
                $restricted = unserialize('a:15:{s:3:"!--";i:2;s:1:"b";i:2;s:10:"blockquote";i:2;s:2:"br";i:1;s:6:"center";i:1;s:2:"em";i:1;s:2:"hr";i:1;s:1:"i";i:1;s:2:"li";i:1;s:2:"ol";i:1;s:1:"p";i:2;s:3:"pre";i:1;s:6:"strong";i:1;s:2:"tt";i:1;s:2:"ul";i:1;}');
            }
            return $restricted;
        break;
        case 'basic':
            if ($basic == NULL) {
                $basic = unserialize('a:21:{s:3:"!--";i:2;s:1:"a";i:2;s:1:"b";i:2;s:10:"blockquote";i:2;s:2:"br";i:1;s:6:"center";i:1;s:3:"div";i:1;s:2:"em";i:1;s:2:"hr";i:2;s:1:"i";i:2;s:2:"li";i:2;s:2:"ol";i:2;s:1:"p";i:2;s:3:"pre";i:2;s:6:"strong";i:2;s:2:"tt";i:2;s:2:"ul";i:2;s:5:"table";i:2;s:2:"td";i:2;s:2:"th";i:2;s:2:"tr";i:2;}');
            }
            return $basic;
        break;
        case 'enhanced':
            if ($enhanced == NULL) {
                $enhanced = unserialize('a:21:{s:3:"!--";i:2;s:1:"a";i:2;s:1:"b";i:2;s:10:"blockquote";i:2;s:2:"br";i:1;s:6:"center";i:1;s:3:"div";i:2;s:2:"em";i:1;s:2:"hr";i:2;s:1:"i";i:2;s:2:"li";i:2;s:2:"ol";i:2;s:1:"p";i:2;s:3:"pre";i:2;s:6:"strong";i:2;s:2:"tt";i:2;s:2:"ul";i:2;s:5:"table";i:2;s:2:"td";i:2;s:2:"th";i:2;s:2:"tr";i:2;}');
            }
            return $enhanced;
        break;
    }
    return array();
}

/**
 * Get a public variable
 *
 * @access private
 * @param modName The name of the module or theme
 * @param name The name of the variable
 * @param uid The user id for variable
 * @param prep determines the prepping for the variable
 * @param type determines type of variable to process
 * @return mixed The value of the variable or void if variable doesn't exist
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarVar__GetVarByAlias($modName = NULL, $name, $uid = NULL, $prep = NULL, $type = 'modvar')
{
    if (empty($name)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'name');
        return;
    }

    // FIXME: <mrb> Has this a specific historic reason to do it like this?
    $missing = '*!*MiSSiNG*!*';

    if (empty($prep)) {
        $prep = XARVAR_PREP_FOR_NOTHING;
    }

    // Lets first check to see if any of our type vars are alread set in the cache.
    $cacheName = $name;
    switch(strtolower($type)) {
    case 'moduservar':
        $cacheCollection = 'ModUser.Variables.' . $modName;
        $cacheName = $uid . $name;
        break;
    case 'themevar':
        $cacheCollection = 'Theme.Variables.' . $modName;  // This is kinda confusing
        break;
    case 'configvar':
        $cacheCollection = 'Config.Variables';
        break;
    default:
        $cacheCollection = 'Mod.Variables.' . $modName;
        break;
    }
    if (xarVarIsCached($cacheCollection, $cacheName)) {
        $value = xarVarGetCached($cacheCollection, $name);
        if ($value === $missing) {
            return;
        } else {
            if ($prep == XARVAR_PREP_FOR_DISPLAY){
                $value = xarVarPrepForDisplay($value);
            } elseif ($prep == XARVAR_PREP_FOR_HTML){
                $value = xarVarPrepHTMLDisplay($value);
            }
            return $value;
        }
    }
    
    // We didn't find it in the single var cache, let's check the cached collection by whole/name
    switch(strtolower($type)) {
    case 'themevar':
        if (xarVarIsCached('Theme.GetVarsByTheme', $modName)) return;
        if (xarVarIsCached('Theme.GetVarsByName', $name)) return;
        break;
    case 'modvar':
    default:
        if (xarVarIsCached('Mod.GetVarsByModule', $modName)) return;
        if (xarVarIsCached('Mod.GetVarsByName', $name)) return;
        break;
    }
    

    // Still no luck, let's do the hard work then
    switch(strtolower($type)) {
    case 'themevar':
        $baseinfotype = 'theme';
        break;
    default:
        $baseinfotype = 'module';
        break;
        
    }
    if($type != 'configvar') {
        $modBaseInfo = xarMod_getBaseInfo($modName, $baseinfotype);
        if (!isset($modBaseInfo)) {
            return; // throw back
        }
    }
    

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();
    
    switch(strtolower($type)) {
    case 'modvar':
    default:
        // Takes the right table basing on module mode
        if ($modBaseInfo['mode'] == XARMOD_MODE_SHARED) {
            $module_varstable = $tables['system/module_vars'];
        } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
            $module_varstable = $tables['site/module_vars'];
        }
        
        $query = "SELECT xar_value
                      FROM $module_varstable
                      WHERE xar_modid = '" . xarVarPrepForStore($modBaseInfo['systemid']) . "'
                      AND xar_name = '" . xarVarPrepForStore($name) . "'";
        break;
    case 'moduservar':
        // Takes the right table basing on module mode
        if ($modBaseInfo['mode'] == XARMOD_MODE_SHARED) {
            $module_uservarstable = $tables['system/module_uservars'];
        } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
            $module_uservarstable = $tables['site/module_uservars'];
        }
        unset($modvarid);
        $modvarid = xarModGetVarId($modName, $name);
        if (!$modvarid) return;
        
        $query = "SELECT xar_value
                      FROM $module_uservarstable
                      WHERE xar_mvid = '" . xarVarPrepForStore($modvarid) . "'
                      AND xar_uid ='" . xarVarPrepForStore($uid). "'";
        break;
    case 'themevar':
        // Takes the right table basing on theme mode
        if ($ModBaseInfo['mode'] == XARTHEME_MODE_SHARED) {
            $theme_varsTable = $tables['theme_vars'];
        } elseif ($ModBaseInfo['mode'] == XARTHEME_MODE_PER_SITE) {
            $theme_varsTable = $tables['site/theme_vars'];
        }
        
        $query = "SELECT xar_value,
                      xar_prime,
                      xar_description
                      FROM $theme_varsTable
                      WHERE xar_themename = '" . xarVarPrepForStore($themeName) . "'
                      AND xar_name = '" . xarVarPrepForStore($name) . "'";
        break;
    case 'configvar':
        
        $config_varsTable = $tables['config_vars'];
        
        $query = "SELECT xar_value
                      FROM $config_varsTable
                      WHERE xar_name='" . xarVarPrepForStore($name) . "'";
        
        break;
        
    }
    
    // TODO : Explain the cache logic behind this, why exclude moduservars?
    // TODO : why have cache period 1 week ?
    if (xarCore_getSystemVar('DB.UseADODBCache')){
        switch(strtolower($type)) {
        case 'modvar':
        case 'themevar':
        case 'configvar':
            $result =& $dbconn->CacheExecute(3600*24*7,$query);
            if (!$result) return;
            break;
        case 'moduservar':
            $result =& $dbconn->Execute($query);
            if (!$result) return;
            break;
        }
    } else {
        $result =& $dbconn->Execute($query);
        if (!$result) return;
    }
    
    $setTo = $missing;
    switch(strtolower($type)) {
    case 'moduservar':
        // If there is no such thing, return the global setting.
        if ($result->EOF) {
            $result->Close();
            // return global setting
            return xarModGetVar($modName, $name);
        }
        break;
    case 'themevar':
        $cacheCollection = 'Theme.Variables.' . $modName ;  // <mrb> kinda confusing here
        break;
    case 'configvar':
        $cacheCollection = 'Config.Variables';
        $setTo = NULL;
        break;
    default:
        $cacheCollection = 'Mod.Variables.' . $modName;
        break;
    }
    if ($result->EOF) {
        $result->Close();
        xarVarSetCached($cacheCollection, $name, $setTo);
        return;
    }
    
    
    list($value) = $result->fields;
    $result->Close();
    
    // We finally found it, update the appropriate cache
    switch(strtolower($type)) {
        case 'modvar':
            default:
            xarVarSetCached('Mod.Variables.' . $modName, $name, $value);
            break;
        case 'moduservar':
            xarVarSetCached('ModUser.Variables.' . $modName, $cachename, $value);
            break;
        case 'themevar':
            xarVarSetCached('Theme.Variables.' . $modName, $name, $value);
            break;
        case 'configvar':
            $value = unserialize($value);
            xarVarSetCached('Config.Variables', $name, $value);
            break;

    }
    
    // Optionally prepare it
    // FIXME: This may sound convenient now, feels wrong though, prepping introduces
    //        an unnecessary dependency here.
    if ($prep == XARVAR_PREP_FOR_DISPLAY){
        $value = xarVarPrepForDisplay($value);
    } elseif ($prep == XARVAR_PREP_FOR_HTML){
        $value = xarVarPrepHTMLDisplay($value);
    }

    return $value;
}

/**
 * Set a module variable
 *
 * @access public
 * @param modName The name of the module
 * @param name The name of the variable
 * @param value The value of the variable
 * @return bool true on success
 * @raise DATABASE_ERROR, BAD_PARAM
 * @todo  We could delete the user vars for the module with the new value to save space?
 */
function xarVar__SetVarByAlias($modName = NULL, $name, $value, $prime = NULL, $description = NULL, $uid = NULL, $type = 'modvar')
{
    if (empty($name)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'name');
        return;
    }

    switch(strtolower($type)) {
        case 'modvar':
        case 'moduservar':
            default:
            $modBaseInfo = xarMod_getBaseInfo($modName);
            if (!isset($modBaseInfo)) return; // throw back
            break;
        case 'themevar':
            $modBaseInfo = xarMod_getBaseInfo($modName, $baseinfotype = 'theme');
            if (!isset($modBaseInfo)) return; // throw back
            break;
        case 'configvar':
            break;
    }

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    switch(strtolower($type)) {
        case 'modvar':
            default:
            // Takes the right table basing on module mode
            if ($modBaseInfo['mode'] == XARMOD_MODE_SHARED) {
                $module_varstable = $tables['system/module_vars'];
            } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
                $module_varstable = $tables['site/module_vars'];
            }

            xarModDelVar($modName, $name);

            $seqId = $dbconn->GenId($module_varstable);
            $query = "INSERT INTO $module_varstable
                         (xar_id,
                          xar_modid,
                          xar_name,
                          xar_value)
                      VALUES
                         ('$seqId',
                          '" . xarVarPrepForStore($modBaseInfo['systemid']) . "',
                          '" . xarVarPrepForStore($name) . "',
                          '" . xarVarPrepForStore($value) . "');";

            break;
        case 'moduservar':
            // Takes the right table basing on module mode
            if ($modBaseInfo['mode'] == XARMOD_MODE_SHARED) {
                $module_uservarstable = $tables['system/module_uservars'];
            } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
                $module_uservarstable = $tables['site/module_uservars'];
            }

            // Get the default setting to compare the value against.
            $modsetting = xarModGetVar($modName, $name);

            // We need the variable id
            unset($modvarid);
            $modvarid = xarModGetVarId($modName, $name);
            if(!$modvarid) return;

            // First delete it.
            xarModDelUserVar($modName,$name,$uid);

            // Only store setting if different from global setting
            if ($value != $modsetting) {
                $query = "INSERT INTO $module_uservarstable
                            (xar_mvid, 
                             xar_uid, 
                             xar_value)
                        VALUES
                         ('" . xarVarPrepForStore($modvarid) . "',
                          '" . xarVarPrepForStore($uid) . "',
                          '" . xarVarPrepForStore($value) . "');";
            }
            break;
        case 'themevar':
            // Takes the right table basing on theme mode
            if ($modBaseInfo['mode'] == XARTHEME_MODE_SHARED) {
                $theme_varsTable = $tables['theme_vars'];
            } elseif ($modBaseInfo['mode'] == XARTHEME_MODE_PER_SITE) {
                $theme_varsTable = $tables['site/theme_vars'];
            }

            xarThemeDelVar($modName, $name);

            $seqId = $dbconn->GenId($theme_varsTable);
            $query = "INSERT INTO $theme_varsTable
                         (xar_id,
                          xar_themename,
                          xar_name,
                          xar_prime,
                          xar_value,
                          xar_description)
                      VALUES
                         ('$seqId',
                          '" . xarVarPrepForStore($modName) . "',
                          '" . xarVarPrepForStore($name) . "',
                          '" . xarVarPrepForStore($prime) . "',
                          '" . xarVarPrepForStore($value) . "',
                          '" . xarVarPrepForStore($description) . "');";

            break;
        case 'configvar':

            xarVar__DelVarByAlias($modname = NULL, $name, $uid = NULL, $type = 'configvar');

            $config_varsTable = $tables['config_vars'];

            //Here we serialize the configuration variables
            //so they can effectively contain more than one value
            $value = serialize($value);

            //Insert
            $seqId = $dbconn->GenId($config_varsTable);
            $query = "INSERT INTO $config_varsTable
                      (xar_id,
                       xar_name,
                       xar_value)
                      VALUES ('$seqId',
                              '" . xarVarPrepForStore($name) . "',
                              '" . xarVarPrepForStore($value). "')";

            break;
    }

    if (xarCore_getSystemVar('DB.UseADODBCache')){
        $result =& $dbconn->CacheFlush();
    }

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    switch(strtolower($type)) {
        case 'modvar':
            default:
            xarVarSetCached('Mod.Variables.' . $modName, $name, $value);
            break;
        case 'moduservar':
            $cachename = $uid . $name;
            xarVarSetCached('ModUser.Variables.' . $modName, $cachename, $value);
            break;
        case 'themevar':
            xarVarSetCached('Theme.Variables.' . $modName, $name, $value);
            break;
        case 'configvar':
                xarVarSetCached('Config.Variables', $name, $value);
            break;
    }

    return true;
}

/**
 * Delete a public variable
 *
 * @access private
 * @param modName The name of the module
 * @param name The name of the variable
 * @return bool true on success
 * @raise DATABASE_ERROR, BAD_PARAM
 * @todo Add caching for user variables?
 */
function xarVar__DelVarByAlias($modName = NULL, $name, $uid = NULL, $type = 'modvar')
{
    if (empty($name)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'name');
        return;
    }

    switch(strtolower($type)) {
        case 'modvar':
        case 'moduservar':
            default:
            $modBaseInfo = xarMod_getBaseInfo($modName);
            if (!isset($modBaseInfo)) return; // throw back
            break;
        case 'themevar':
            $modBaseInfo = xarMod_getBaseInfo($modName, $baseinfotype = 'theme');
            if (!isset($modBaseInfo)) return; // throw back
            break;
        case 'configvar':
            break;
    }

    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    switch(strtolower($type)) {
        case 'moduservar':
            default:
            // Takes the right table basing on module mode
            if ($modBaseInfo['mode'] == XARMOD_MODE_SHARED) {
                $module_uservarstable = $tables['system/module_uservars'];
            } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
                $module_uservarstable = $tables['site/module_uservars'];
            }

            // Delete the user variables first
            $modvarid = xarModGetVarId($modName, $name);
            if(!$modvarid) return;

            // MrB: we could use xarModDelUserVar in a loop here, but this is
            //      much faster.
            $query = "DELETE FROM $module_uservarstable
                      WHERE xar_mvid = '" . xarVarPrepForStore($modvarid) . "'";
            $result =& $dbconn->Execute($query);
            if(!$result) return;

            continue;
        case 'modvar':
            // Takes the right table basing on module mode
            if ($modBaseInfo['mode'] == XARMOD_MODE_SHARED) {
                $module_varstable = $tables['system/module_vars'];
            } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
                $module_varstable = $tables['site/module_vars'];
            }
            // Now delete the module var
            $query = "DELETE FROM $module_varstable
                      WHERE xar_modid = '" . xarVarPrepForStore($modBaseInfo['systemid']) . "'
                      AND xar_name = '" . xarVarPrepForStore($name) . "'";
            break;
        case 'themevar':
            // Takes the right table basing on theme mode
            if ($modBaseInfo['mode'] == XARTHEME_MODE_SHARED) {
                $theme_varsTable = $tables['system/theme_vars'];
            } elseif ($modBaseInfo['mode'] == XARTHEME_MODE_PER_SITE) {
                $theme_varsTable = $tables['site/theme_vars'];
            }

            $query = "DELETE FROM $theme_varsTable
                      WHERE xar_themename = '" . xarVarPrepForStore($modName) . "'
                      AND xar_name = '" . xarVarPrepForStore($name) . "'";
            break;
        case 'configvar':
            $config_varsTable = $tables['config_vars'];
            $query = "DELETE FROM $config_varsTable
                      WHERE xar_name = '" . xarVarPrepForStore($name) . "'";
            break;
    }

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    switch(strtolower($type)) {
        case 'modvar':
            default:
                xarVarDelCached('Mod.Variables.' . $modName, $name);
            break;
        case 'moduservar':
                $cachename = $uid . $name;
                xarVarDelCached('ModUser.Variables.' . $modName, $cachename);
            break;
        case 'themevar':
                xarVarDelCached('Theme.Variables.' . $modName, $name);
            break;
        case 'configvar':
                xarVarDelCached('Config.Variables.', $name);
            break;
    }

    return true;
}

function xarVarTransformHTML2XML ($text) {

    //Taken from Reverend's Jim feedparser
    //http://revjim.net/code/feedParser/feedParser-0.5.phps

    static $entities = array(
        '&nbsp'   => "&#160;",
        '&iexcl'  => "&#161;",
        '&cent'   => "&#162;",
        '&pound'  => "&#163;",
        '&curren' => "&#164;",
        '&yen'    => "&#165;",
        '&brvbar' => "&#166;",
        '&sect'   => "&#167;",
        '&uml'    => "&#168;",
        '&copy'   => "&#169;",
        '&ordf'   => "&#170;",
        '&laquo'  => "&#171;",
        '&not'    => "&#172;",
        '&shy' =>    "&#173;",
        '&reg' =>    "&#174;",
        '&macr' =>   "&#175;",
        '&deg' =>    "&#176;",
        '&plusmn' => "&#177;",
        '&sup2' =>   "&#178;",
        '&sup3' =>   "&#179;",
        '&acute' =>  "&#180;",
        '&micro' =>  "&#181;",
        '&para' =>   "&#182;",
        '&middot' => "&#183;",
        '&cedil' =>  "&#184;",
        '&sup1' =>   "&#185;",
        '&ordm' =>   "&#186;",
        '&raquo' =>  "&#187;",
        '&frac14' => "&#188;",
        '&frac12' => "&#189;",
        '&frac34' => "&#190;",
        '&iquest' => "&#191;",
        '&Agrave' => "&#192;",
        '&Aacute' => "&#193;",
        '&Acirc' =>  "&#194;",
        '&Atilde' => "&#195;",
        '&Auml' =>   "&#196;",
        '&Aring' =>  "&#197;",
        '&AElig' =>  "&#198;",
        '&Ccedil' => "&#199;",
        '&Egrave' => "&#200;",
        '&Eacute' => "&#201;",
        '&Ecirc' =>  "&#202;",
        '&Euml' =>   "&#203;",
        '&Igrave' => "&#204;",
        '&Iacute' => "&#205;",
        '&Icirc' =>  "&#206;",
        '&Iuml' =>   "&#207;",
        '&ETH' =>    "&#208;",
        '&Ntilde' => "&#209;",
        '&Ograve' => "&#210;",
        '&Oacute' => "&#211;",
        '&Ocirc' =>  "&#212;",
        '&Otilde' => "&#213;",
        '&Ouml' =>   "&#214;",
        '&times' =>  "&#215;",
        '&Oslash' => "&#216;",
        '&Ugrave' => "&#217;",
        '&Uacute' => "&#218;",
        '&Ucirc' =>  "&#219;",
        '&Uuml' =>   "&#220;",
        '&Yacute' => "&#221;",
        '&THORN' =>  "&#222;",
        '&szlig' =>  "&#223;",
        '&agrave' => "&#224;",
        '&aacute' => "&#225;",
        '&acirc' =>  "&#226;",
        '&atilde' => "&#227;",
        '&auml' =>   "&#228;",
        '&aring' =>  "&#229;",
        '&aelig' =>  "&#230;",
        '&ccedil' => "&#231;",
        '&egrave' => "&#232;",
        '&eacute' => "&#233;",
        '&ecirc' =>  "&#234;",
        '&euml' =>   "&#235;",
        '&igrave' => "&#236;",
        '&iacute' => "&#237;",
        '&icirc' =>  "&#238;",
        '&iuml' =>   "&#239;",
        '&eth' =>    "&#240;",
        '&ntilde' => "&#241;",
        '&ograve' => "&#242;",
        '&oacute' => "&#243;",
        '&ocirc' =>  "&#244;",
        '&otilde' => "&#245;",
        '&ouml' =>   "&#246;",
        '&divide' => "&#247;",
        '&oslash' => "&#248;",
        '&ugrave' => "&#249;",
        '&uacute' => "&#250;",
        '&ucirc' =>  "&#251;",
        '&uuml' =>   "&#252;",
        '&yacute' => "&#253;",
        '&thorn' =>  "&#254;",
        '&yuml' =>   "&#255;"
    );

    return strtr($text, $entities);

}
?>