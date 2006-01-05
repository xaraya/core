<?php
/**
 * Variable utilities
 *
 * @package variables
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Marco Canini marco@xaraya.com
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
 * @todo <mrb> remove the two settings allowablehtml and fixhtmlentities
 */
function xarVar_init($args, $whatElseIsGoingLoaded)
{
    /*
     * Initialise the variable cache
     */
    $GLOBALS['xarVar_cacheCollection'] = array();
    $GLOBALS['xarVar_allowableHTML'] = xarConfigGetVar('Site.Core.AllowableHTML');
    $GLOBALS['xarVar_fixHTMLEntities'] = xarConfigGetVar('Site.Core.FixHTMLEntities');

    // Subsystem initialized, register a handler to run when the request is over
    //register_shutdown_function ('xarVar__shutdown_handler');
    return true;
}

/**
 * Shutdown handler for xarVar subsystem
 *
 * @access private
 */
function xarVar__shutdown_handler()
{
    //xarLogMessage("xarVar shutdown handler");
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
function xarVarBatchFetch()
{

    $batch = func_get_args();

    $result_array = array();
    $no_errors    = true;

    foreach ($batch as $line) {
        $result_array[$line[2]] = array();
        $result = xarVarFetch($line[0], $line[1], $result_array[$line[2]]['value'], isset($line[3])?$line[3]:NULL, isset($line[4])?$line[4]:XARVAR_GET_OR_POST);

        if (!$result) {
            //Records the error presented in the given input variable
            $result_array[$line[2]]['error'] = xarCurrentError();
            //Handle the Exception
            xarErrorHandled();
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
 * The $flag parameter is a bitmask between the following constants: 
 * XARVAR_GET_OR_POST  - fetch from GET or POST variables
 * XARVAR_GET_ONLY     - fetch from GET variables only
 * XARVAR_POST_ONLY    - fetch from POST variables only
 * XARVAR_NOT_REQUIRED - allow the variable to be empty/not set, dont raise exception if it is
 * XARVAR_DONT_REUSE   - if there is an existing value, do not reused it
 * XARVAR_DONT_SET     - if there is an existing value, use it
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
 *  // FIXME: DELETE THIS once deprecation is complete
 *   XARVAR_PREP_FOR_STORE:      dbconn->qstr($value)
 *   XARVAR_PREP_TRIM:           trim($value)
 *
 * @author Marco Canini
 * @access public
 * @param name string the variable name
 * @param validation string the validation to be performed
 * @param value mixed contains the converted value of fetched variable
 * @param defaultValue mixed the default value
 * @param flags integer bitmask which modify the behaviour of function
 * @param prep will prep the value with xarVarPrepForDisplay, xarVarPrepHTMLDisplay, or dbconn->qstr()
 * @return mixed
 * @raise BAD_PARAM
 */
function xarVarFetch($name, $validation, &$value, $defaultValue = NULL, $flags = XARVAR_GET_OR_POST, $prep = XARVAR_PREP_FOR_NOTHING)
{
    assert('is_int($flags)');
    assert('empty($name) || preg_match("/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/", $name)');

    $allowOnlyMethod = NULL;
    if ($flags & XARVAR_GET_ONLY) $allowOnlyMethod = 'GET';
    if ($flags & XARVAR_POST_ONLY) $allowOnlyMethod = 'POST';

    if ($flags & XARVAR_DONT_SET) {
        if (isset($value)) {
            $oldValue = $value;
        } else {
            $oldValue = null;
        }
    }

    //This allows us to have a extract($args) before the xarVarFetch and still run
    //the variables thru the tests here.

// FIXME: this flag doesn't seem to work !?
    //The FLAG here, stops xarVarFetch from reusing the variable if already present
    if (!isset($value) || ($flags & XARVAR_DONT_REUSE)) {
        $value = xarRequestGetVar($name, $allowOnlyMethod);
    }

    if (($flags & XARVAR_DONT_SET) || ($flags & XARVAR_NOT_REQUIRED) || isset($defaultValue)) {
        $supress = true;
    } else {
        $supress = false;
    }

    $result = xarVarValidate($validation, $value, $supress, $name);

    if (xarCurrentErrorType()) {return;} //Throw back

    if (!$result) {
    // CHECKME:  even for the XARVAR_DONT_SET flag !?
        // if you set a non-null default value, assume you want to use it here
        if (($flags & XARVAR_NOT_REQUIRED) || isset($defaultValue)) {
            $value = $defaultValue;

        // with XARVAR_DONT_SET, make sure we don't pass invalid old values back either
        } elseif (($flags & XARVAR_DONT_SET) && isset($oldValue) && xarVarValidate($validation, $oldValue, $supress)) {
            $value = $oldValue;

        // make sure we don't pass any invalid values back
        } else {
            $value = null;
        }
    } else {
        // Check prep of $value
        if ($prep & XARVAR_PREP_FOR_DISPLAY) {
            $value = xarVarPrepForDisplay($value);
        }

        if ($prep & XARVAR_PREP_FOR_HTML) {
            $value = xarVarPrepHTMLDisplay($value);
        }

        if ($prep & XARVAR_PREP_FOR_STORE) {
            $dbconn =& xarDBGetConn();
            $value = $dbconn->qstr($value);
        }

        if ($prep & XARVAR_PREP_TRIM) {
            $value = trim($value);
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
function xarVarValidate($validation, &$subject, $supress = false, $name='')
{
// <nuncanada> For now, i have moved all validations to html/modules/variable/validations
//             I think that will incentivate 3rd party devs to create and send new validations back to us..
//             As id/int/str are used in every page view, probably they should be here.

    $valParams = explode(':', $validation);
    $valType = strtolower(array_shift($valParams));

    if (empty($valType)) throw new EmptyParameterException('valType');

    // {ML_include 'includes/validations/array.php'}
    // {ML_include 'includes/validations/bool.php'}
    // {ML_include 'includes/validations/checkbox.php'}
    // {ML_include 'includes/validations/email.php'}
    // {ML_include 'includes/validations/enum.php'}
    // {ML_include 'includes/validations/float.php'}
    // {ML_include 'includes/validations/fullemail.php'}
    // {ML_include 'includes/validations/html.php'}
    // {ML_include 'includes/validations/id.php'}
    // {ML_include 'includes/validations/int.php'}
    // {ML_include 'includes/validations/isset.php'}
    // {ML_include 'includes/validations/list.php'}
    // {ML_include 'includes/validations/mxcheck.php'}
    // {ML_include 'includes/validations/notempty.php'}
    // {ML_include 'includes/validations/regexp.php'}
    // {ML_include 'includes/validations/str.php'}

    $function_name = xarVarLoad ('validations', $valType);
    if (!$function_name) {return;}

    return $function_name($subject, $valParams, $supress, $name);
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

/**
 * Check if the value of a variable is available in cache or not
 * See the documentation of protected xarCore_IsCached for details
 *
 * @access public
 */
function xarVarIsCached($cacheKey, $name)
{
    return xarCore_IsCached($cacheKey, $name);
}

/**
 * Get the value of a cached variable
 * See the documentation of protected xarCore_GetCached for details
 *
 * @access public
 */
function xarVarGetCached($cacheKey, $name)
{
    return xarCore_GetCached($cacheKey, $name);
}

/**
 * Set the value of a cached variable
 * See the documentation of protected xarCore_SetCached for details
 *
 * @access public
 */
function xarVarSetCached($cacheKey, $name, $value)
{
    return xarCore_SetCached($cacheKey, $name, $value);
}

/**
 * Delete a cached variable
 * See the documentation of protected xarCore_DelCached for details
 *
 * @access public
 */
function xarVarDelCached($cacheKey, $name)
{
    return xarCore_DelCached($cacheKey, $name);
}

/**
 * Flush a particular cache (e.g. for session initialization)
 * See the documentation of protected xarCore_FlushCached for details
 *
 * @access public
 */
function xarVarFlushCached($cacheKey)
{
    return xarCore_FlushCached($cacheKey);
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
    // Get the allowed HTML from the config var.  At some
    // point this will be replaced by retrieving the
    // allowed HTML from the HTML module.
    $allowedHTML = array();
    foreach (xarConfigGetVar('Site.Core.AllowableHTML') as $k=>$v) {
        if ($v) {
            $allowedHTML[] = $k;
        }
    }

    return $allowedHTML;
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
 //FIXME: Theme vars seems to be useless, get rid of it.
function xarVar__GetVarByAlias($modName = NULL, $name, $uid = NULL, $prep = NULL, $type = 'modvar')
{
    if (empty($name)) throw new EmptyParameterException('name');

    // FIXME: <mrb> Has this a specific historic reason to do it like this?
    $missing = '*!*MiSSiNG*!*';

    if (empty($prep)) {
        $prep = XARVAR_PREP_FOR_NOTHING;
    }

    // Lets first check to see if any of our type vars are alread set in the cache.
    //If you change this, change it down there in the results for modvar and themevar
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
        $value = xarVarGetCached($cacheCollection, $cacheName);
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
    } elseif (xarVarIsCached($cacheCollection, 0)) {
        //variable missing.
        return;
    }


    // We didn't find it in the single var cache, let's check the cached collection by whole/name
    switch(strtolower($type)) {
    case 'themevar':
        if (xarVarIsCached('Theme.GetVarsByTheme', $modName)) return;
        if (xarVarIsCached('Theme.GetVarsByName', $cacheName)) return;
        break;
    case 'modvar':
        if (xarVarIsCached('Mod.GetVarsByModule', $modName)) return;
        if (xarVarIsCached('Mod.GetVarsByName', $cacheName)) return;
        break;
    default:
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


    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();
    $bindvars = array();

    switch(strtolower($type)) {
    case 'modvar':
    default:
        // Takes the right table basing on module mode
        if ($modBaseInfo['mode'] == XARMOD_MODE_SHARED) {
            $module_varstable = $tables['system/module_vars'];
        } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
            $module_varstable = $tables['site/module_vars'];
        }

        $query = "SELECT xar_name, xar_value FROM $module_varstable WHERE xar_modid = ?";
        $bindvars = array((int)$modBaseInfo['systemid']);
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

        $query = "SELECT xar_value FROM $module_uservarstable
                  WHERE xar_mvid = ? AND xar_uid = ?";
        $bindvars = array((int)$modvarid, (int)$uid);
        break;
    case 'themevar':
        // Takes the right table basing on theme mode
        if ($modBaseInfo['mode'] == XARTHEME_MODE_SHARED) {
            $theme_varsTable = $tables['theme_vars'];
        } elseif ($modBaseInfo['mode'] == XARTHEME_MODE_PER_SITE) {
            $theme_varsTable = $tables['site/theme_vars'];
        }

        //This was broken!!
        //Guess nobody is using these
        //Later on it was list($value) = $this->fields... But there are 3 fields here!!!
//        $query = "SELECT xar_value, xar_prime, xar_description
        $query = "SELECT xar_name, xar_value
                  FROM $theme_varsTable
                  WHERE xar_themename = ?";
        $bindvars = array($modName);
        break;
    case 'configvar':

        $config_varsTable = $tables['config_vars'];

        $query = "SELECT xar_value FROM $config_varsTable WHERE xar_name=?";
        $bindvars = array($name);
        break;

    }

    // TODO : Here used to be a resultset cache option, reconsider it
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery($bindvars,ResultSet::FETCHMODE_NUM);
    if (!$result) return;
    
    if ($result->getRecordCount() == 0) {
        $result->close(); unset($result);
        
        // If there is no such thing, return the global setting for moduservars
        if (strtolower($type) == 'moduservar') return xarModGetVar($modName, $name);
        
        xarVarSetCached($cacheCollection, $cacheName, $missing);
        return;
    }

    switch(strtolower($type)) {
        case 'themevar':
        case 'modvar':
            while ($result->next()) {
                xarVarSetCached($cacheCollection, $result->getString(1), $result->get(2));
            }
            //Special value to tell this select has already been run, any
            //variable not found now on is missing
             xarVarSetCached($cacheCollection, 0, true);
            //It should be here!
            if (xarVarIsCached($cacheCollection, $cacheName)) {
                $value = xarVarGetCached($cacheCollection, $cacheName);
            } else {
                xarVarSetCached($cacheCollection, $cacheName, $missing);
                return;
            }
        break;

        default:
            // We finally found it, update the appropriate cache
            //Couldnt we serialize and unserialize all variables?
            //would that be too time expensive?
            list($value) = $result->getRow();
            if($type == 'configvar') {
                $value = unserialize($value);
            }
            xarVarSetCached($cacheCollection, $cacheName, $value);
        break;
    }

    $result->Close();

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
    assert('!is_null($value); /* Not allowed to set a variable to NULL value */');
    if (empty($name)) throw new EmptyParameterException('name');

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

    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    switch(strtolower($type)) {
        case 'modvar':
            default:
            // Takes the right table basing on module mode
            if ($modBaseInfo['mode'] == XARMOD_MODE_SHARED) {
                $module_varstable = $tables['system/module_vars'];
            } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
                $module_varstable = $tables['site/module_vars'];
            }

            // We need the variable id
            unset($modvarid);
            $modvarid = xarModGetVarId($modName, $name);

            if($value === false) $value = 0;
            if($value === true) $value = 1;
            if(!$modvarid) {
                $seqId = $dbconn->GenId($module_varstable);
                $query = "INSERT INTO $module_varstable
                             (xar_id, xar_modid, xar_name, xar_value)
                          VALUES (?,?,?,?)";
                $bindvars = array($seqId, $modBaseInfo['systemid'],$name,(string)$value);
            } else {
                $query = "UPDATE $module_varstable SET xar_value = ? WHERE xar_id = ?";
                $bindvars = array((string)$value,$modvarid);
            }

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
            // FIXME: do we really want that ?
            xarModDelUserVar($modName,$name,$uid);

            // Only store setting if different from global setting
            if ($value != $modsetting) {
                $query = "INSERT INTO $module_uservarstable
                            (xar_mvid, xar_uid, xar_value)
                        VALUES (?,?,?)";
                $bindvars = array($modvarid, $uid, (string)$value);
            }
            break;
        case 'themevar':
            // Takes the right table basing on theme mode
            if ($modBaseInfo['mode'] == XARTHEME_MODE_SHARED) {
                $theme_varsTable = $tables['theme_vars'];
            } elseif ($modBaseInfo['mode'] == XARTHEME_MODE_PER_SITE) {
                $theme_varsTable = $tables['site/theme_vars'];
            }

            // FIXME: do we really want that ?
            xarThemeDelVar($modName, $name);

            $seqId = $dbconn->GenId($theme_varsTable);
            $query = "INSERT INTO $theme_varsTable
                         (xar_id, xar_themename,
                          xar_name, xar_prime,
                          xar_value, xar_description)
                      VALUES (?,?,?,?,?,?)";
            $bindvars = array($seqId, $modName, $name, $prime, (string)$value, $description);

            break;
        case 'configvar':

            // FIXME: do we really want that ?
            xarVar__DelVarByAlias($modname = NULL, $name, $uid = NULL, $type = 'configvar');

            $config_varsTable = $tables['config_vars'];

            //Here we serialize the configuration variables
            //so they can effectively contain more than one value
            $serialvalue = serialize($value);

            //Insert
            $seqId = $dbconn->GenId($config_varsTable);
            $query = "INSERT INTO $config_varsTable
                      (xar_id, xar_name, xar_value)
                      VALUES (?,?,?)";
            $bindvars = array($seqId, $name, $serialvalue);

            break;
    }

    if (!empty($query)){
        try {
            $dbconn->begin();
            $stmt = $dbconn->prepareStatement($query);
            $stmt->executeUpdate($bindvars);
            $dbconn->commit();
        } catch (SQLException $e) {
            $dbconn->rollback();
            throw $e;
        }
    }

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
    if (empty($name)) throw new EmptyParameterException('name');

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

    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    try {
        switch(strtolower($type)) {
        case 'modvar':
        default:
            // Delete all the user variables first
            $modvarid = xarModGetVarId($modName, $name);
            if($modvarid) {
                // Takes the right table basing on module mode
                if ($modBaseInfo['mode'] == XARMOD_MODE_SHARED) {
                    $module_uservarstable = $tables['system/module_uservars'];
                } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
                    $module_uservarstable = $tables['site/module_uservars'];
                }

                $query = "DELETE FROM $module_uservarstable WHERE xar_mvid = ?";
                $dbconn->execute($query,array((int)$modvarid));
            }
            // Takes the right table basing on module mode
            if ($modBaseInfo['mode'] == XARMOD_MODE_SHARED) {
                $module_varstable = $tables['system/module_vars'];
            } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
                $module_varstable = $tables['site/module_vars'];
            }
            // Now delete the module var itself
            $query = "DELETE FROM $module_varstable WHERE xar_modid = ? AND xar_name = ?";
            $dbconn->execute($query,array((int)$modBaseInfo['systemid'], $name));
            break;
        case 'moduservar':
            // Takes the right table basing on module mode
            if ($modBaseInfo['mode'] == XARMOD_MODE_SHARED) {
                $module_uservarstable = $tables['system/module_uservars'];
            } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
                $module_uservarstable = $tables['site/module_uservars'];
            }
            
            // We need the variable id
            $modvarid = xarModGetVarId($modName, $name);
            if(!$modvarid) return;
            
            $query = "DELETE FROM $module_uservarstable WHERE xar_mvid = ? AND xar_uid = ?";
            $bindvars = array((int)$modvarid, (int)$uid);
            $dbconn->execute($query,$bindvars);
            break;
        case 'themevar':
            // Takes the right table basing on theme mode
            if ($modBaseInfo['mode'] == XARTHEME_MODE_SHARED) {
                $theme_varsTable = $tables['system/theme_vars'];
            } elseif ($modBaseInfo['mode'] == XARTHEME_MODE_PER_SITE) {
                $theme_varsTable = $tables['site/theme_vars'];
            }

            $query = "DELETE FROM $theme_varsTable WHERE xar_themename = ?  AND xar_name = ?";
            $bindvars = array($modName,$name);
            $dbconn->execute($query,$bindvars);
            break;
        case 'configvar':
            $config_varsTable = $tables['config_vars'];
            $query = "DELETE FROM $config_varsTable WHERE xar_name = ?";
            $bindvars = array($name);
            $dbconn->execute($query,$bindvars);
            break;
        }
        // All done, commit
        $dbconn->commit();
    } catch (SQLException $e) {
        $dbconn->rollback();
        throw $e;
    }


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

/**
 * Changes one variable from one context to another
 *
 * @access public
 * @param string The string to be Converted
 * @param sourceContext The name of the module
 * @param targetContext The name of the module
 * @return string the string in the new context
 * @raise EMPTY_PARAM
 * @todo  Would it be useful to be able to transform arrays of strings at once?
 */
function xarVarTransform ($string, $sourceContext, $targetContext)
{
    if (empty($sourceContext)) throw new EmptyParameterException('sourceContext');
    if (empty($targetContext)) throw new EmptyParameterException('targetContext');
    $transform_type = $sourceContext.'_to_'.$targetContext;
    $function_name = xarVarLoad ('transforms', $transform_type);

    if (!$function_name) {return;}

    return $function_name ($string);
}

/**
 * Loads variable's drivers. Should be changed to module space latter on.
 *
 * @access private
 * @param string The drivers directory
 * @param filename The name file to be used
 * @return string the function anme
 * @raise BAD_PARAM
 */
function xarVarLoad ($includes_type, $filename)
{

    $filename = xarVarPrepForOS($filename);

    $function_file = './includes/'.$includes_type.'/'.$filename.'.php';
    $function_name = 'variable_'.$includes_type.'_'.$filename;

    if (!function_exists($function_name)) {
        if (file_exists($function_file)) {
            include_once($function_file);
        }
    }

    if (!function_exists($function_name)) {
        // Raise an exception
        $msg = 'The #(1) type \'#(2)\' could not be found.';
        $params = arrary($includes_type, $filename);
        throw new BadParameterException($params,$msg);
    }

    return $function_name;
}

/**
 * Escapes on variable for the use in a specific context
 *
 * @access public
 * @param string The string to be Converted
 * @param targetContext The name of the context to escape for
 * @return string the string escape for the context
 * @raise EMPTY_PARAM
 * @todo Would it be useful to be able to transform arrays of strings at once?
 */
function xarVarEscape ($string, $targetContext, $extras = array())
{
    if (empty($targetContext)) throw new EmptyParameterException('targetContext');

    $function_name = xarVarLoad ('escapes', $targetContext);
    if (!$function_name) {return;}

    return $function_name ($string, $extras);
}

/*
    ---------------------------------------------------------------------
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
 * @deprecated
 */
function xarVarCleanUntrusted($var)
{
    // Issue a WARNING as this function is deprecated
    xarLogMessage('Using deprecated function xarVarCleanUntrusted, use ??? instead',XARLOG_LEVEL_WARNING);
    $search = array('|</?\s*SCRIPT[^>]*>|si',
                    '|</?\s*FRAME[^>]*>|si',
                    '|</?\s*OBJECT[^>]*>|si',
                    '|</?\s*META[^>]*>|si',
                    '|</?\s*APPLET[^>]*>|si',
                    '|</?\s*LINK[^>]*>|si',
                    '|</?\s*IFRAME[^>]*>|si',
                    '|STYLE\s*=\s*"[^"]*"|si');
    // short open tag <  followed by ? (we do it like this otherwise our qa tests go bonkers)
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
 * @deprec
 */
function xarVarCleanFromInput()
{
    // Issue a WARNING as this function is deprecated
    xarLogMessage('Using deprecated function xarVarCleanFromInput, use xarVarFetch instead',XARLOG_LEVEL_WARNING);
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
    $resarray = array();
    foreach (func_get_args() as $var) {
        // Prepare var
        $var = htmlspecialchars($var);
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
    static $allowedtags = NULL;

    if (!isset($allowedtags)) {
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
 * Ready obfuscated e-mail output
 *
 * Gets a variable, cleaning it up such that e-mail addresses are
 * slightly obfuscated against e-mail harvesters.
 *
 * @access public
 * @return mixed prepared variable if only one variable passed
 * in, otherwise an array of prepared variables
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
    // <nuncanada> I really dont like this kind of behaviour... It's not consistent.
    if (func_num_args() == 1) {
        return $args[0];
    } else {
        return $args;
    }
}
?>
