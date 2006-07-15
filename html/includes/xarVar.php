<?php
/**
 * Variable utilities
 *
 * @package variables
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Marco Canini marco@xaraya.com
 */

/**
 * Exceptions for this subsystem
 *
 */

class VariableValidationException extends ValidationExceptions
{
    protected $message = 'The variable "#(1)" [Value: "#(2)"] did not comply with the required validation: "#(3)"';
}

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
 * @param args array
 * @param whatElseIsGoingLoaded integer
 * @return bool
 * @todo <johnny> fix the load level stuff here... it's inconsistant to the rest of the core
 * @todo <mrb> remove the two settings allowablehtml and fixhtmlentities
 */
function xarVar_init(&$args, $whatElseIsGoingLoaded)
{
    /*
     * Initialise the variable cache
     */
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
 */
function xarVarBatchFetch()
{

    $batch = func_get_args();

    $result_array = array();
    $no_errors    = true;

    foreach ($batch as $line) {
        $result_array[$line[2]] = array();
        try {
            $result = xarVarFetch($line[0], $line[1], $result_array[$line[2]]['value'], isset($line[3])?$line[3]:NULL, isset($line[4])?$line[4]:XARVAR_GET_OR_POST);
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
 */
function xarVarFetch($name, $validation, &$value, $defaultValue = NULL, $flags = XARVAR_GET_OR_POST, $prep = XARVAR_PREP_FOR_NOTHING)
{
    assert('is_int($flags); /* Flags passed to xarVarFetch are not of integer type */');
    assert('empty($name) || preg_match("/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/", $name); /* Variable name does not match expression for valid variable names */');

    $allowOnlyMethod = NULL;
    if ($flags & XARVAR_GET_ONLY) $allowOnlyMethod = 'GET';
    if ($flags & XARVAR_POST_ONLY) $allowOnlyMethod = 'POST';

    //This allows us to have a extract($args) before the xarVarFetch and still run
    //the variables thru the tests here.
    if ($flags & XARVAR_DONT_SET) {
        $oldValue = null;
        if (isset($value)) $oldValue = $value;
    }

    // FIXME: this flag doesn't seem to work !?
    // The FLAG here, stops xarVarFetch from reusing the variable if already present
    if (!isset($value) || ($flags & XARVAR_DONT_REUSE)) {
        $value = xarRequest::getVar($name, $allowOnlyMethod);
    }

    // TODO: use try/catch clause to implement the suppressing, letting the validators except at will.
    $supress = false;
    if (($flags & XARVAR_DONT_SET) || ($flags & XARVAR_NOT_REQUIRED) || isset($defaultValue)) {
        // TODO: when fetching an optional var using ----^ the exception is not thrown when the variable
        // fetched does not pass validation, i doubt we want that. Means that an optional fetch never validates and
        // always gets the default value?
        $supress = true;
    } 
    // Validate the value
    $result = xarVarValidate($validation, $value, $supress, $name);

    if (!$result) {
        // First make sure we don't pass any invalid values back
        $value = null;
        // CHECKME:  even for the XARVAR_DONT_SET flag !?
        if (($flags & XARVAR_NOT_REQUIRED) || isset($defaultValue)) {
            // if you set a non-null default value, assume you want to use it here
            $value = $defaultValue;
        } elseif (($flags & XARVAR_DONT_SET) && isset($oldValue) && xarVarValidate($validation, $oldValue, $supress)) {
            // with XARVAR_DONT_SET, make sure we don't pass invalid old values back either
            $value = $oldValue;
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
 * @throws EmptyParameterException
 * @return bool true if the $subject validates correctly, false otherwise
 */
function xarVarValidate($validation, &$subject, $supress = false, $name='')
{
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
 * Wrapper functions for var caching as in Xaraya 1 API
 * See the documentation of protected xarCore::*Cached for details
 *
 * @access public
 * @see xarCore
 */
function xarVarIsCached($cacheKey,  $name)         { return xarCore::isCached($cacheKey, $name);         }
function xarVarGetCached($cacheKey, $name)         { return xarCore::getCached($cacheKey, $name);        }
function xarVarSetCached($cacheKey, $name, $value) { return xarCore::setCached($cacheKey, $name, $value);}
function xarVarDelCached($cacheKey, $name)         { return xarCore::delCached($cacheKey, $name);        }
function xarVarFlushCached($cacheKey)              { return xarCore::flushCached($cacheKey);             }


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
 * @throws EmptyParameterException
 */
function xarVar__GetVarByAlias($modName = NULL, $name, $itemid = NULL, $prep = NULL, $type = 'modvar')
{
    if (empty($name)) throw new EmptyParameterException('name');
    if (empty($prep)) $prep = XARVAR_PREP_FOR_NOTHING;
    
    // Lets first check to see if any of our type vars are alread set in the cache.
    $cacheName = $name;
    switch($type) {
    case 'moditemvar':
        $cacheCollection = 'ModItem.Variables.' . $modName;
        $cacheName = $itemid . $name;
        break;
    case 'configvar':
        $cacheCollection = 'Config.Variables';
        break;
    case 'modvar':
    default:
        $cacheCollection = 'Mod.Variables.' . $modName;
        break;
    }

    if (xarVarIsCached($cacheCollection, $cacheName)) {
        $value = xarVarGetCached($cacheCollection, $cacheName);
        if (!isset($value)) {
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
    // TODO: caching for the other types
    switch($type) {
    case 'modvar':
        if (xarVarIsCached('Mod.GetVarsByModule', $modName)) return;
        if (xarVarIsCached('Mod.GetVarsByName', $cacheName)) return;
        break;
    default:
        break;
    }

    // Still no luck, let's do the hard work then
    $baseinfotype = 'module';

    if($type != 'configvar') {
        $modBaseInfo = xarMod::getBaseInfo($modName, $baseinfotype);
        if (!isset($modBaseInfo)) return; // throw back
    } else {
        $modBaseInfo['systemid'] = 0;
    }


    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();
    $bindvars = array();

    switch($type) {
    case 'configvar':
    case 'modvar':
    default:
        $module_varstable = $tables['module_vars'];
        $query = "SELECT xar_name, xar_value FROM $module_varstable WHERE xar_modid = ?";
        $bindvars = array((int)$modBaseInfo['systemid']);
        break;
     case 'moditemvar':
         $module_itemvarstable = $tables['module_itemvars'];
         unset($modvarid);
         $modvarid = xarModVars::getId($modName, $name);
         if (!$modvarid) return;

         $query = "SELECT xar_value FROM $module_itemvarstable WHERE xar_mvid = ? AND xar_itemid = ?";
         $bindvars = array((int)$modvarid, (int)$itemid);
         break;
    }

    // TODO : Here used to be a resultset cache option, reconsider it
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery($bindvars,ResultSet::FETCHMODE_NUM);

    if ($result->getRecordCount() == 0) {
        $result->close(); unset($result);

        // If there is no such thing, return the global setting for moditemvars
        if ($type == 'moditemvar') return xarModVars::get($modName, $name);
        return;
    }

    switch($type) {
        case 'configvar':
        case 'modvar':
            while ($result->next()) {
                $value = $result->get(2); // Unlike creole->set this does *not* unserialize/escape automatically
                if($type == 'configvar') $value = unserialize($value);
                xarVarSetCached($cacheCollection, $result->getString(1), $value);
            }
            //Special value to tell this select has already been run, any
            //variable not found now on is missing
             xarVarSetCached($cacheCollection, 0, true);
            //It should be here!
            if (xarVarIsCached($cacheCollection, $cacheName)) {
                $value = xarVarGetCached($cacheCollection, $cacheName);
            } else {
                return;
            }
            break;
        default:
            // We finally found it, update the appropriate cache
            $result->next();
            list($value) = $result->getRow();
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
 * @throws EmptyParameterException, ModuleNotFoundException, VariableNotFoundException, SQLException
 * @todo  We could delete the user vars for the module with the new value to save space?
 */
function xarVar__SetVarByAlias($modName = NULL, $name, $value, $prime = NULL, $description = NULL, $itemid = NULL, $type = 'modvar')
{
    assert('!is_null($value); /* Not allowed to set a variable to NULL value */');
    if (empty($name)) throw new EmptyParameterException('name');

    switch($type) {
    case 'modvar':
    case 'moditemvar':
    default:
        $modBaseInfo = xarMod::getBaseInfo($modName);
        if(!isset($modBaseInfo)) throw new ModuleNotFoundException($modName);
        break;
    case 'configvar':
        $modBaseInfo['systemid'] = 0;
        break;
    }

    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    switch($type) {
        case 'modvar':
        default:
            $module_varstable = $tables['module_vars'];
            // We need the variable id
            unset($modvarid);
            $modvarid = xarModVars::getId($modName, $name);

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
        case 'moditemvar':
            $module_itemvarstable = $tables['module_itemvars'];

            // Get the default setting to compare the value against.
            $modsetting = xarModVars::get($modName, $name);

            // We need the variable id
            unset($modvarid);
            $modvarid = xarModVars::getId($modName, $name);
            if(!$modvarid) throw new VariableNotFoundException($name);

            // First delete it.
            // FIXME: do we really want that ?
            xarModUserVars::delete($modName,$name,$itemid);

            // Only store setting if different from global setting
            if ($value != $modsetting) {
                $query = "INSERT INTO $module_itemvarstable
                            (xar_mvid, xar_itemid, xar_value)
                        VALUES (?,?,?)";
                $bindvars = array($modvarid, $itemid, (string)$value);
            }
            break;
        case 'configvar':
            // FIXME: do we really want that ?
            // This way, worst case: 3 queries:
            // 1. deleting it
            // 2. Getting a new id (for some backends)
            // 3. inserting it.
            // Question is wether we want to invent new configvars on the fly or not
            xarVar__DelVarByAlias($modname = NULL, $name, $itemid = NULL, $type = 'configvar');

            $config_varsTable = $tables['config_vars'];

            //Here we serialize the configuration variables
            //so they can effectively contain more than one value
            $serialvalue = serialize($value);

            //Insert
            $seqId = $dbconn->GenId($config_varsTable);
            $query = "INSERT INTO $config_varsTable
                      (xar_id, xar_modid, xar_name, xar_value)
                      VALUES (?,?,?,?)";
            $bindvars = array($seqId, $modBaseInfo['systemid'], $name, $serialvalue);

            break;
    }

    if (!empty($query)){
        $stmt = $dbconn->prepareStatement($query);
        $stmt->executeUpdate($bindvars);
    }

    switch($type) {
        case 'modvar':
            default:
            xarVarSetCached('Mod.Variables.' . $modName, $name, $value);
            break;
        case 'moditemvar':
            $cachename = $itemid . $name;
            xarVarSetCached('ModItem.Variables.' . $modName, $cachename, $value);
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
 * @throws EmptyParameterException
 * @todo Add caching for user variables?
 */
function xarVar__DelVarByAlias($modName = NULL, $name, $itemid = NULL, $type = 'modvar')
{
    if (empty($name)) throw new EmptyParameterException('name');

    switch($type) {
        case 'modvar':
        case 'moditemvar':
            default:
            $modBaseInfo = xarMod::getBaseInfo($modName);
            if (!isset($modBaseInfo)) return; // throw back
            break;
        case 'configvar':
            $modBaseInfo['systemid'] = 0;
            break;
    }

    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    switch($type) {
    case 'modvar':
    default:
        // Delete all the user variables first
        $modvarid = xarModVars::getId($modName, $name);
        if($modvarid) {
            $module_itemvarstable = $tables['module_itemvars'];
            $query = "DELETE FROM $module_itemvarstable WHERE xar_mvid = ?";
            $dbconn->execute($query,array((int)$modvarid));
        }
        $module_varstable = $tables['module_vars'];
        // Now delete the module var itself
        $query = "DELETE FROM $module_varstable WHERE xar_modid = ? AND xar_name = ?";
        $bindvars = array($modBaseInfo['systemid'],$name);
        break;
    case 'moditemvar':
        $module_itemvarstable = $tables['module_itemvars'];
        // We need the variable id
        $modvarid = xarModVars::getId($modName, $name);
        if(!$modvarid) return;

        $query = "DELETE FROM $module_itemvarstable WHERE xar_mvid = ? AND xar_itemid = ?";
        $bindvars = array((int)$modvarid, (int)$itemid);
        break;
    case 'configvar':
        $config_varsTable = $tables['config_vars'];
        $query = "DELETE FROM $config_varsTable WHERE xar_name = ? AND xar_modid=?";
        $bindvars = array($name,$modBaseInfo['systemid']);
        break;
    }
    $dbconn->execute($query,$bindvars);

    switch($type) {
        case 'modvar':
            default:
                xarVarDelCached('Mod.Variables.' . $modName, $name);
            break;
        case 'moditemvar':
                $cachename = $itemid . $name;
                xarVarDelCached('ModItem.Variables.' . $modName, $cachename);
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
 * @throws EmptyParameterException
 * @todo  Would it be useful to be able to transform arrays of strings at once?
 * @todo  This is a bit weird, perhaps use a factory class and hide the loading details?
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
 * @throws BadParameterException
 * @todo also a bit weird
 * @see xarVarTransform
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
        $params = array($includes_type, $filename);
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
 * @throws EmptyParameterException
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
 *
 * @todo the / also prevents relative access in some cases (template tag for example)
 * @todo this puts responsibility on callee to know how things work, and gets a mangled name back, not very nice
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
    // <nuncanada> I really dont like this kind of behaviour... It's not consistent.
    if (func_num_args() == 1) {
        return $args[0];
    } else {
        return $args;
    }
}
?>
