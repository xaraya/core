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
function pnVar_init($args)
{
    global $pnVar_allowableHTML, $pnVar_fixHTMLEntities,
           $pnVar_enableCensoringWords, $pnVar_censoredWords,
           $pnVar_censoredWordsReplacers;
    /*
    $pnVar_allowableHTML = $args['allowableHTML'];
    $pnVar_fixHTMLEntities = $args['fixHTMLEntities'];

    $pnVar_enableCensoringWords = $args['enableCensoringWords'];
    $pnVar_censoredWords = $args['censoredWords'];
    $pnVar_censoredWordsReplacers = $args['censoredWordsReplacers'];

    return true;
    */

    $pnVar_allowableHTML = pnConfigGetVar('AllowableHTML');
    if (!isset($allowableHTML) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return; // throw back exception
    }

    $pnVar_fixHTMLEntities = pnConfigGetVar('htmlentities');
    if (!isset($pnVar_fixHTMLEntities) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return; // throw back exception
    }

    $pnVar_enableCensoringWords = pnConfigGetVar('CensorMode');
    if (!isset($pnVar_enableCensoringWords) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return; // throw back exception
    }

    $pnVar_censoredWords = pnConfigGetVar('CensorList');
    if (!isset($pnVar_censoredWords) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return; // throw back exception
    }
    $pnVar_censoredWordsReplacers = pnConfigGetVar('CensorReplace');
    if (!isset($replace) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return; // throw back exception
    }

    return true;
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
function pnVarCleanUntrusted($var)
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
// FIXME: <marco> This function will not work is the security system is not loaded!
function pnVarCleanFromInput()
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

        $var = pnRequestGetVar($name);
        if (!isset($var)) {
            array_push($resarray, NULL);
            continue;
        }

        // TODO: <marco> Document this security check!
        if (!function_exists('pnSecAuthAction') || !pnSecAuthAction(0, '::', '::', ACCESS_ADMIN)) {
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
function pnVarPrepForDisplay()
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
function pnVarPrepHTMLDisplay()
{
    global $pnVar_allowableHTML, $pnVar_fixHTMLEntities;

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

        foreach($pnVar_allowableHTML as $k=>$v) {
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
        if ($pnVar_fixHTMLEntities) {
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
function pnVarPrepForStore()
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
 * to access files outside of the scope of the PostNuke
 * system is not allowed. Can have as many parameters as desired.
 *
 * @access public
 * @param var variable to prepare
 * @return mixed prepared variable if only one variable passed
 * in, otherwise an array of prepared variables
 */
function pnVarPrepForOS()
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
function pnVarCensor()
{
    global $pnVar_enableCensoringWords, $pnVar_censoredWords, $pnVar_censoredWordsReplacers;

    if (!$pnVar_enableCensoringWords) {
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

        foreach ($pnVar_censoredWords as $censoredWord) {
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

        if ($pnVar_enableCensoringWords) {
            // Parse out nasty words
            $var = preg_replace($search, $pnVar_censoredWordsReplacers, $var);
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
 * if (pnVarIsCached('MyCache', 'myvar')) {
 *     $var = pnVarGetCached('MyCache', 'myvar');
 * }
 * ...
 * pnVarSetCached('MyCache', 'myvar', 'this value');
 * ...
 * pnVarDelCached('MyCache', 'myvar');
 * ...
 * pnVarFlushCached('MyCache');
 * ...
 *
 */

/**
 * Initialise the variable cache
 */
$GLOBALS['pnVar_cacheCollection'] = array();

/**
 * check if the value of a variable is available in cache or not
 *
 * @access public
 * @param key the key identifying the particular cache you want to access
 * @param name the name of the variable in that particular cache
 * @returns bool
 * @return true if the variable is available in cache, false if not
 */
function pnVarIsCached($cacheKey, $name)
{
    global $pnVar_cacheCollection;
    if (!isset($pnVar_cacheCollection[$cacheKey])) {
        $pnVar_cacheCollection[$cacheKey] = array();
        return false;
    }
    return isset($pnVar_cacheCollection[$cacheKey][$name]);
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
function pnVarGetCached($cacheKey, $name)
{
    global $pnVar_cacheCollection;
    if (!isset($pnVar_cacheCollection[$cacheKey][$name])) {
        return;
    }
    return $pnVar_cacheCollection[$cacheKey][$name];
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
function pnVarSetCached($cacheKey, $name, $value)
{
    global $pnVar_cacheCollection;
    $pnVar_cacheCollection[$cacheKey][$name] = $value;
}

/**
 * delete a cached variable
 *
 * @access public
 * @param key the key identifying the particular cache you want to access
 * @param name the name of the variable in that particular cache
 * @returns void
 */
function pnVarDelCached($cacheKey, $name)
{
    global $pnVar_cacheCollection;
    // TODO: check if we don't need to work with $GLOBALS here for some PHP ver
    if (isset($pnVar_cacheCollection[$cacheKey][$name])) {
        unset($pnVar_cacheCollection[$cacheKey][$name]);
    }
}

/**
 * flush a particular cache (e.g. for session initialization)
 *
 * @access public
 * @param key the key identifying the particular cache you want to wipe out
 * @returns void
 */
function pnVarFlushCached($cacheKey)
{
    global $pnVar_cacheCollection;
    // TODO: check if we don't need to work with $GLOBALS here for some PHP ver
    if (isset($pnVar_cacheCollection[$cacheKey])) {
        unset($pnVar_cacheCollection[$cacheKey]);
    }
}

// PROTECTED FUNCTIONS

/**
 * stripslashes on multidimensional arrays.
 *
 * Used in conjunction with pnVarCleanFromInput
 *
 * @access protected
 * @param any variables or arrays to be stripslashed
 */
function pnVar_stripSlashes(&$var)
{
    if(!is_array($var)) {
        $var = stripslashes($var);
    } else {
        array_walk($var,'pnVar_stripSlashes');
    }
}

?>
