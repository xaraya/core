<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Marco Canini
// Purpose of file: Variables utilities
// ----------------------------------------------------------------------

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
 * @param args[allowableHTML] a serialized array of allowed html tags
 * @param args[htmlentities]  fix html entities
 * @returns
 * @returns
 */
function xarVar_init($args, $whatElseIsGoingLoaded)
{
    global $xarVar_allowableHTML, $xarVar_fixHTMLEntities,
           $xarVar_enableCensoringWords, $xarVar_censoredWords,
           $xarVar_censoredWordsReplacers;
    /*
    $xarVar_allowableHTML = $args['allowableHTML'];
    $xarVar_fixHTMLEntities = $args['fixHTMLEntities'];

    $xarVar_enableCensoringWords = $args['enableCensoringWords'];
    $xarVar_censoredWords = $args['censoredWords'];
    $xarVar_censoredWordsReplacers = $args['censoredWordsReplacers'];

    return true;
    */

    $xarVar_allowableHTML = xarConfigGetVar('Site.Core.AllowableHTML');
    if (!isset($xarVar_allowableHTML) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return; // throw back exception
    }

    $xarVar_fixHTMLEntities = xarConfigGetVar('Site.Core.FixHTMLEntities');
    if (!isset($xarVar_fixHTMLEntities) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return; // throw back exception
    }

    $xarVar_enableCensoringWords = xarConfigGetVar('Site.Core.EnableCensoring');
    if (!isset($xarVar_enableCensoringWords) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return; // throw back exception
    }

    $xarVar_censoredWords = xarConfigGetVar('Site.Core.CensoredWords');
    if (!isset($xarVar_censoredWords) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return; // throw back exception
    }
    $xarVar_censoredWordsReplacers = xarConfigGetVar('Site.Core.CensoredWordReplacers');
    if (!isset($xarVar_censoredWordsReplacers) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return; // throw back exception
    }

    return true;
}

/**
 * Fetches the $name variable from input variables and validates it by applying the $validation rules.
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
 * @param name the variable name
 * @param validation the validation to be performed
 * @param value contains the converted value of fetched variable
 * @param defaultValue the default value
 * @param flags bitmask which modify the behaviour of function
 * @returns bool
 * @return true
 * @raise BAD_PARAM
 */
function xarVarFetch($name, $validation, &$value, $defaultValue = NULL, $flags = XARVAR_GET_OR_POST)
{
    assert('is_int($flags)');

    $allowOnlyMethod = NULL;
    if ($flags & XARVAR_GET_ONLY) $allowOnlyMethod = 'GET';
    if ($flags & XARVAR_POST_ONLY) $allowOnlyMethod = 'POST';
    $subject = xarRequestGetVar($name, $allowOnlyMethod);
    if ($subject == NULL || xarVarValidate($validation, $subject, $value) == false) {
        if ($flags & XARVAR_NOT_REQUIRED || isset($defaultValue)) {
            $value = $defaultValue;
        } else {
            // Raise an exception
            $msg = xarML('The required #(1) input variable couldn\'t be found or contains invalid data.', $name);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                           new SystemException($msg));
            return;
        }
    }
    return true;
}

/**
 * Validates a variable performing the $validation test type on $subject.
 * The $validation parameter could be a string, in this case the
 * supported validation types are very basilar, they are the following:
 * 'id' matches a positive integer (0 excluded)
 * 'int:<min val>:<max val>' matches an integer between <min val> and <max val> (included), if <min val>
 *                           is not present no lower bound check is performed, the same applies to <max val>
 * 'float:<min val>:<max val>' matches a floating point number between <min val> and <max val> (included), if <min val>
 *                             is not present no lower bound check is performed, the same applies to <max val>
 * 'bool' matches a string that can be 'true' or 'false'
 * 'str:<min len>:<max len>' matches a string which has a lenght between <min len> and <max len>, if <min len>
 *                           is omitted no control is done on mininum lenght, the same applies to <max len>
 * 'regex:<pattern>' matches against the <pattern> regular expression, because preg_match is used internally the pattern
 *                   must be contained into delimiters (/ or !)
 * 'html:<level>' validates the subject by searching unallowed html tags, allowed tags are defined by specifying <level>
 *                that could be one of restricted, basic, enhanced, admin. This last level is not configurable and allows
 *                every tag
 *
 * After the validation is performed, $convValue (passed by reference) is assigned to $subject converted the proper type.
 * Please note that conversions from string to integer or float are done by using the PHP built-in cast conversions,
 * refer to this page for the details:
 * http://www.php.net/manual/en/language.types.string.html#language.types.string.conversion
 * The $validation parameter could also be an object, in this case it must implement the xarVarValidator
 * interface.
 *
 * @author Marco Canini
 * @param validation the validation to be performed
 * @param subject the subject on which the validation must be performed
 * @param convValue contains the converted value of $subject
 * @returns bool
 * @return true if the $subject validates correctly, false otherwise
 */
function xarVarValidate($validation, $subject, &$convValue)
{
    assert('is_string($validation) || is_object($validation)');
    assert('is_string($subject)');

    if (is_object($validation)) {
        return $validation->validate($subject, $convValue);
    }
    $valParams = explode(':', $validation);
    $valType = array_shift($valParams); 
    switch ($valType) {
        case 'id':
        $valParams = array('1', '');

        case 'int':
        assert('count($valParams) == 2');
        $value = (int) $subject;
        if (!empty($valParams[0])) {
            if ($value < (int) $valParams[0]) {
                return false;
            }
        }
        if (!empty($valParams[1])) {
            if ($value > (int) $valParams[1]) {
                return false;
            }
        }
        $convValue = $value;
        break;

        case 'float':
        assert('count($valParams) == 2');
        $value = (float) $subject;
        if (!empty($valParams[0])) {
            if ($value < (float) $valParams[0]) {
                return false;
            }
        }
        if (!empty($valParams[1])) {
            if ($value > (float) $valParams[1]) {
                return false;
            }
        }
        $convValue = $value;
        break;

        case 'bool':
        assert('count($valParams) == 0');
        if ($subject == 'true') {
            $convValue = true;
        } elseif ($subject == 'false') {
            $convValue = false;
        } else {
            return false;
        }
        break;

        case 'str':
        assert('count($valParams) == 2');
        $len = strlen($subject);
        if (!empty($valParams[0])) {
            if ($len < (int) $valParams[0]) {
                return false;
            }
        }
        if (!empty($valParams[1])) {
            if ($len > (int) $valParams[1]) {
                return false;
            }
        }
        $convValue = $subject;
        break;

        case 'regexp':
        assert('count($valParams) == 1');
        if (!preg_match($valParams[0], $subject)) {
            return false;
        }
        $convValue = $subject;
        break;

        case 'html':
        assert('count($valParams) == 1 && ($valParams[0] == "restricted" || $valParams[0] == "basic" ||
                $valParams[0] == "enhanced" || $valParams[0] == "admin")');
        if ($valParams[0] == 'admin') {
            break;
        }
        $allowedTags = xarVar__getAllowedTags($valParams[0]); 
        preg_match_all("|</?(\w+)(\s+.*?)?/?>|", $subject, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $tag = strtolower($match[1]);
            if (!isset($allowedTags[$tag])) {
                return false;
            } elseif (isset($match[2]) && $allowedTags[$tag] == XARVAR_ALLOW_NO_ATTRIBS && trim($match[2]) != '') {
                return false;
            }
        }
        break;
    }
    return true;
}

class xarVarValidator
{
    function validate($subject, &$convValue)
    {
    }
}

class xarVarGroupValidator extends xarVarValidator
{
    var $validations;
    
    function xarVarGroupValidator(/*...*/)
    {
        $this->validations = func_get_args();
    }
    
    function validate($subject, &$convValue)
    {
        foreach ($this->validations as $validation) {
            if (!xarVarValidate($validation, $subject, $convValue)) {
                return false;
            }
        }
    }
}

/**
 * Cleans a variable.
 * Cleaning it up to try to ensure that hack attacks
 * don't work. Typically used for cleaning variables
 * coming from user input.
 *
 * @access public
 * @param var variable to clean
 * @returns string
 * @return prepared variable
 */
function xarVarCleanUntrusted($var)
{
    $search = array('|</?\s*SCRIPT.*?>|si',
                    '|</?\s*FRAME.*?>|si',
                    '|</?\s*OBJECT.*?>|si',
                    '|</?\s*META.*?>|si',
                    '|</?\s*APPLET.*?>|si',
                    '|</?\s*LINK.*?>|si',
                    '|</?\s*IFRAME.*?>|si',
                    '|STYLE\s*=\s*"[^"]*"|si');
    // <?
    $replace = array('');
    // Clean var
    $var = preg_replace($search, $replace, $var);

    return $var;
}

/**
 * clean user input
 *
 * Gets a global variable, cleaning it up to try to ensure that
 * hack attacks don't work. Can have as many parameters as needed.
 *
 * @access public
 * @param var name of variable to get
 * @returns mixed
 * @return mixed prepared variable if only one variable passed
 * in, otherwise an array of prepared variables
 */
// FIXME: <marco> This function will not work if the security system is not loaded!
function xarVarCleanFromInput()
{
    $search = array('|</?\s*SCRIPT.*?>|si',
                    '|</?\s*FRAME.*?>|si',
                    '|</?\s*OBJECT.*?>|si',
                    '|</?\s*META.*?>|si',
                    '|</?\s*APPLET.*?>|si',
                    '|</?\s*LINK.*?>|si',
                    '|</?\s*IFRAME.*?>|si',
                    '|STYLE\s*=\s*"[^"]*"|si');
    // <?
    $replace = array('');

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
        if (!function_exists('xarSecAuthAction') || !xarSecAuthAction(0, '::', '::', ACCESS_ADMIN)) {
            $var = preg_replace($search, $replace, $var);
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
 * ready user output
 *
 * Gets a variable, cleaning it up such that the text is
 * shown exactly as expected. Can have as many parameters as desired.
 *
 * @access public
 * @param var variable to prepare
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
 * ready HTML output
 *
 * Gets a variable, cleaning it up such that the text is
 * shown exactly as expected, except for allowed HTML tags which
 * are allowed through. Can have as many parameters as desired.
 *
 * @access public
 * @param var variable to prepare
 * @return mixed prepared variable if only one variable passed
 * in, otherwise an array of prepared variables
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarVarPrepHTMLDisplay()
{
    global $xarVar_allowableHTML, $xarVar_fixHTMLEntities;

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

    static $allowedHTML = NULL;

    if (!isset($allowedHTML)) {
        $allowedHTML = array();

        foreach($xarVar_allowableHTML as $k=>$v) {
            switch($v) {
                case 0:
                    break;
                case 1:
                    $allowedHTML[] = "|<(/?$k)\s*/?>|i";
                    break;
                case 2:
                    $allowedHTML[] = "|<(/?$k(\s+.*?)?)>|i";
                    break;
            }
        }
    }

    $resarray = array();
    foreach (func_get_args() as $var) {
        // Preparse var to mark the HTML that we want
        $var = preg_replace($allowedHTML, "\022\\1\024", $var);

        // Prepare var
        $var = htmlspecialchars($var);
        $var = preg_replace($search, $replace, $var);

        // Fix the HTML that we want
        $var = preg_replace('/\022([^\024]*)\024/e',
                               "'<' . strtr('\\1',
                                            array('&gt;' => '>',
                                                  '&lt;' => '<',
                                                  '&quot;' => '\"',
                                                  '&amp;' => '&'))
                               . '>';", $var);

        // Fix entities if required
        if ($xarVar_fixHTMLEntities) {
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

/**
 * ready database output
 *
 * Gets a variable, cleaning it up such that the text is
 * stored in a database exactly as expected. Can have as many parameters as desired.
 *
 * @access public
 * @param var variable to prepare
 * @return mixed prepared variable if only one variable passed
 * in, otherwise an array of prepared variables
 */
function xarVarPrepForStore()
{
    $resarray = array();
    foreach (func_get_args() as $var) {

        // Prepare var
        if (!get_magic_quotes_runtime()) {
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
 * ready operating system output
 *
 * Gets a variable, cleaning it up such that any attempts
 * to access files outside of the scope of the Xaraya
 * system is not allowed. Can have as many parameters as desired.
 *
 * @access public
 * @param var variable to prepare
 * @return mixed prepared variable if only one variable passed
 * in, otherwise an array of prepared variables
 */
function xarVarPrepForOS()
{
    static $search = array('!\.\./!si',  // .. (directory traversal)
                           '!^.*://!si', // .*:// (start of URL)
                           '!/!si',      // Forward slash (directory traversal)
                           '!\\\\!si');  // Backslash (directory traversal)

    static $replace = array('',
                            '',
                            '_',
                            '_');

    $resarray = array();
    foreach (func_get_args() as $var) {

        // Parse out bad things
        $var = preg_replace($search, $replace, $var);

        // Prepare var
        if (!get_magic_quotes_runtime()) {
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
 * remove censored words.
 *
 * Removes all censored words from the variables handed to the function.
 * Can have as many parameters as desired.
 *
 * @access public
 * @param var variable to prepare
 * @return mixed prepared variable if only one variable passed
 * in, otherwise an array of prepared variables
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarVarCensor()
{
    global $xarVar_enableCensoringWords, $xarVar_censoredWords, $xarVar_censoredWordsReplacers;

    if (!$xarVar_enableCensoringWords) {
        $args = func_get_args();
        if (func_num_args() == 1) {
            return $args[0];
        } else {
            return $args;
        }
    }

    static $search = array();
    if (empty($search)) {
        $repSearch = array('/o/i',
                           '/e/i',
                           '/a/i',
                           '/i/i');
        $repReplace = array('0',
                            '3',
                            '@',
                            '1');

        foreach ($xarVar_censoredWords as $censoredWord) {
            // Simple word
            $search[] = "/\b$censoredWord\b/i";

            // Common replacements
            $mungedword = preg_replace($repSearch, $repReplace, $censoredWord);
            if ($mungedword != $censoredWord) {
                $search[] = "/\b$mungedword\b/";
            }
        }
    }

    $resarray = array();
    foreach (func_get_args() as $var) {

        if ($xarVar_enableCensoringWords) {
            // Parse out nasty words
            $var = preg_replace($search, $xarVar_censoredWordsReplacers, $var);
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
 * functions providing variable caching (within a single page request)
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

/**
 * Initialise the variable cache
 */
$GLOBALS['xarVar_cacheCollection'] = array();

/**
 * check if the value of a variable is available in cache or not
 *
 * @access public
 * @param key the key identifying the particular cache you want to access
 * @param name the name of the variable in that particular cache
 * @returns bool
 * @return true if the variable is available in cache, false if not
 */
function xarVarIsCached($cacheKey, $name)
{
    global $xarVar_cacheCollection;
    if (!isset($xarVar_cacheCollection[$cacheKey])) {
        $xarVar_cacheCollection[$cacheKey] = array();
        return false;
    }
    return isset($xarVar_cacheCollection[$cacheKey][$name]);
}

/**
 * get the value of a cached variable
 *
 * @access public
 * @param key the key identifying the particular cache you want to access
 * @param name the name of the variable in that particular cache
 * @returns mixed
 * @return value of the variable, or void if variable isn't cached
 */
function xarVarGetCached($cacheKey, $name)
{
    global $xarVar_cacheCollection;
    if (!isset($xarVar_cacheCollection[$cacheKey][$name])) {
        return;
    }
    return $xarVar_cacheCollection[$cacheKey][$name];
}

/**
 * set the value of a cached variable
 *
 * @access public
 * @param key the key identifying the particular cache you want to access
 * @param name the name of the variable in that particular cache
 * @param value the new value for that variable
 * @returns void
 */
function xarVarSetCached($cacheKey, $name, $value)
{
    global $xarVar_cacheCollection;
    $xarVar_cacheCollection[$cacheKey][$name] = $value;
}

/**
 * delete a cached variable
 *
 * @access public
 * @param key the key identifying the particular cache you want to access
 * @param name the name of the variable in that particular cache
 * @returns void
 */
function xarVarDelCached($cacheKey, $name)
{
    global $xarVar_cacheCollection;
    // TODO: check if we don't need to work with $GLOBALS here for some PHP ver
    if (isset($xarVar_cacheCollection[$cacheKey][$name])) {
        unset($xarVar_cacheCollection[$cacheKey][$name]);
    }
}

/**
 * flush a particular cache (e.g. for session initialization)
 *
 * @access public
 * @param key the key identifying the particular cache you want to wipe out
 * @returns void
 */
function xarVarFlushCached($cacheKey)
{
    global $xarVar_cacheCollection;
    // TODO: check if we don't need to work with $GLOBALS here for some PHP ver
    if (isset($xarVar_cacheCollection[$cacheKey])) {
        unset($xarVar_cacheCollection[$cacheKey]);
    }
}

// PROTECTED FUNCTIONS

/**
 * stripslashes on multidimensional arrays.
 *
 * Used in conjunction with xarVarCleanFromInput
 *
 * @access protected
 * @param any variables or arrays to be stripslashed
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

// PRIVATE FUNCTIONS

function xarVar__getAllowedTags($level)
{
    static $restricted = NULL;
    static $basic = NULL;
    static $enhanced = NULL;
    switch ($level) {
        case 'restricted':
            $restricted = unserialize('a:15:{s:3:"!--";i:2;s:1:"b";i:2;s:10:"blockquote";i:2;s:2:"br";i:1;s:6:"center";i:1;s:2:"em";i:1;s:2:"hr";i:1;s:1:"i";i:1;s:2:"li";i:1;s:2:"ol";i:1;s:1:"p";i:2;s:3:"pre";i:1;s:6:"strong";i:1;s:2:"tt";i:1;s:2:"ul";i:1;}');
            return $restricted;
        break;
        case 'basic':
            $basic = unserialize('a:21:{s:3:"!--";i:2;s:1:"a";i:2;s:1:"b";i:2;s:10:"blockquote";i:2;s:2:"br";i:1;s:6:"center";i:1;s:3:"div";i:1;s:2:"em";i:1;s:2:"hr";i:2;s:1:"i";i:2;s:2:"li";i:2;s:2:"ol";i:2;s:1:"p";i:2;s:3:"pre";i:2;s:6:"strong";i:2;s:2:"tt";i:2;s:2:"ul";i:2;s:5:"table";i:2;s:2:"td";i:2;s:2:"th";i:2;s:2:"tr";i:2;}');
            return $basic;
        break;
        case 'enhanced':
            $enhanced = unserialize('a:21:{s:3:"!--";i:2;s:1:"a";i:2;s:1:"b";i:2;s:10:"blockquote";i:2;s:2:"br";i:1;s:6:"center";i:1;s:3:"div";i:2;s:2:"em";i:1;s:2:"hr";i:2;s:1:"i";i:2;s:2:"li";i:2;s:2:"ol";i:2;s:1:"p";i:2;s:3:"pre";i:2;s:6:"strong";i:2;s:2:"tt";i:2;s:2:"ul";i:2;s:5:"table";i:2;s:2:"td";i:2;s:2:"th";i:2;s:2:"tr";i:2;}');
            return $enhanced;
        break;
    }
}

?>
