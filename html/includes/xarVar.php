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
 * @subpackage Variable Utilities
 * @author Marco Canini m.canini@libero.it
 */

/**
 * Variables package defines
 */
define('XARVAR_ALLOW_NO_ATTRIBS', 1);
define('XARVAR_ALLOW', 2);

define('XARVAR_GET_OR_POST', 0);
define('XARVAR_GET_ONLY', 2);
define('XARVAR_POST_ONLY', 4);
define('XARVAR_NOT_REQUIRED', 64);

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
 * Fetches the $name variable from input variables and validates it by applying the $validation rules.
 *
 * See xarVarValidate for details about nature of $validation.
 * After the call the $value parameter passed by reference is set to the variable value converted to the proper type
 * according to the validation applied.
 * The $defaultValue provides a default value that is returned when the variable is not present or doesn't validate
 * correctly and the XARVAR_NOT_REQUIRED (see below) flag is set.
 * The $flag parameter is a bitmask between the following constants: XARVAR_GET_OR_POST, XARVAR_GET_ONLY,
 * XARVAR_POST_ONLY, XARVAR_NOT_REQUIRED.
 * You can force to get the variable only from GET parameters or POST parameters by setting the $flag parameter
 * to one of XARVAR_GET_ONLY or XARVAR_POST_ONLY.
 * You can force xarVarFetch function to not raise an exception when the variable is not present or invalid by setting
 * the $flag parameter to XARVAR_NOT_REQUIRED.
 * By default $flag is XARVAR_GET_OR_POST which means tha xarVarFetch will lookup both GET and POST parameters and
 * that if the variable is not present or doesn't validate correctly an exception will be raised.
 *
 * @author Marco Canini
 * @access public
 * @param name string the variable name
 * @param validation string the validation to be performed
 * @param value mixed contains the converted value of fetched variable
 * @param defaultValue mixed the default value
 * @param flags integer bitmask which modify the behaviour of function
 * @return mixed
 * @raise BAD_PARAM
 */
function xarVarFetch($name, $validation, &$value, $defaultValue = NULL, $flags = XARVAR_GET_OR_POST)
{
    //<nuncanada> XARVAR_NOT_REQUIRED is useless in the logic used here, just put the
    // default value and it will work fine
    // XARVAR_NOTREQUIRED should be some independent integer so it could be mixed(!) with XARVAR_GET_OR_POST
    // What about cookie/env/request/server variables?

    assert('is_int($flags)');

    $allowOnlyMethod = NULL;
    if ($flags & XARVAR_GET_ONLY) $allowOnlyMethod = 'GET';
    if ($flags & XARVAR_POST_ONLY) $allowOnlyMethod = 'POST';

    //This allows us to have a extract($args) before the xarVarFetch and still run
    //the variables thru the tests here.
    if (!isset($value)) {
        $value = xarRequestGetVar($name, $allowOnlyMethod);

        if ($value == NULL) {
            if ($flags & XARVAR_NOT_REQUIRED || isset($defaultValue)) {
                $value = $defaultValue;

                return true;
            } else {
                // Raise an exception
                $msg = xarML('The required input variable \'#(1)\' could not be found.', $name);
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
                return;
            }
        }
    }

    $result = xarVarValidate($validation, $value);

    if ($result === NULL) {return;} //SYSTEM_EXCEPTION -> throw back

    //USER_EXCEPTION -> find if there is a defaultValue set.
    if ($result === FALSE) {
        if (($flags & XARVAR_NOT_REQUIRED) || isset($defaultValue)) {
            $value = $defaultValue;
            xarExceptionHandled(); //<- We should have an USER_EXCEPTION on the stack
        } else {
            // Raise an exception
            $msg = xarML('The required input variable \'#(1)\' contained invalid data.', $name);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                            new SystemException($msg));
            return;
        }
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
function xarVarValidate($validation, &$subject) {
// <nuncanada> For now, i have moved all validations to html/modules/variable/validations
//             I think that will incentivate 3rd party devs to create and send new validations back to us..
//             As id/int/str are used in every page view, probably they should be here.
// <nuncanada> For more flexible validations it might be interesting to change how parameters are inserted
//             to an array?! Although the actual interface seems easier to use...
// Thinking about it better, this 'array' validation should be changed for 'list', it fits better what it does
// Array should be there too to check if its an array and give min/max bounds on it.

    $valParams = explode(':', $validation);
    $valType = xarVarPrepForOS(strtolower(array_shift($valParams)));
    
    if (empty($valType)) {
        // Raise an exception
        $msg = xarML('No validation type present.');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    $function_file = './modules/variable/validations/'.$valType.'.php';
    $function_name = 'variable_validations_'.$valType;

    if (!function_exists($function_name)) {
        if (file_exists($function_file)) {
            include_once($function_file);
        }
    }

    if (function_exists($function_name)) {
        $return = $function_name($subject, $valParams);
        //The helper functions already have a nicer interface, let´s change the main function too?
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
 *******************************************************************
 * <nuncanada>
 * This is a bad way to add security!
 * You have to quote/escape/treat the variable upon the change of interpretation, not when
 * getting them from the input!!
 *********************************************************************
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
 *******************************************************************
 * <nuncanada>
 * This is a bad way to add security!
 * You have to quote/escape/treat the variable upon the change of interpretation, not when
 * getting them from the input!!
 *********************************************************************
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
    // This search and replace finds the text 'x@y' and replaces
    // it with HTML entities, this provides protection against
    // email harvesters
    static $search = array('/(.)@(.)/se');

    static $replace = array('"&#" .
                            sprintf("%03d", ord("\\1")) .
                            ";&#064;&#" .
                            sprintf("%03d", ord("\\2")) . ";";');

    $resarray = array();
    foreach (func_get_args() as $var) {

        // Prepare var
        $var = htmlspecialchars($var);

        $var = preg_replace($search, $replace, $var);

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
    // IMO This makes no sense...
    // Because there are 2 possibilities:
    //  1) You have already checked if the html tags present in a certain string are ok
    //     before storing it, in this case, you should just display the text without
    //     any kind of preparation. (Validation without Preparation)
    // OR
    //  2) You havent checked and so, the only thing you should do is to escape whatever
    //     meaningful html character that is present.... (Preparation for strings without Validation)
    // If you want to add the possibility of adding html markup to a text, do 1.
    // Besides that you could have special transform hooks to transform text, but this is not the case
    // over here...

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
        $var = preg_replace($search, $replace, $var);
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
    // <nuncanada> I really dont like this kind of behaviour... It´s not consistent.
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
 * @returns void
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
