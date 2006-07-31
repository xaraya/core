<?php
/**
 * Legacy Functions
 *
 * @package legacy
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Marco Canini
*/

/***********************************************************************
* This file is for legacy functions needed to make it
* easier to use pn modules in Xaraya and have a place for our own legacy
* function which are on their way to deprecation. 
* Please don't fill it with useless
* stuff except as wrappers, and also.. please
* do not duplicate constants that already exist in xaraya core
* If a function did not exist in pn before...don't prefix it with pn
***********************************************************************/

include dirname(__FILE__).'/pnHTML.php';

/**********************************************************************
* WARNING: THIS FILE IS A WORK IN PROGRESS!!!!!!!!!!!!!!!!!!!
* Please mark all stuff that you need in this file or file a bug report
*
* Necessary functions to duplicate
* MODULE SYSTEM FUNCTIONS
* pnModGetVar -> xarModGetVar
* pnModSetVar -> xarModSetVar
* pnModDelVar -> xarModDelVar
* pnModURL -> xarModURL
* pnModGetName -> xarRequestGetInfo (use list() = $modName = xarRequestGetInfo() )
* pnModGetIDFromName -> xarModGetIDFromName
* pnModLoad -> xarModLoad
* pnModAPILoad -> xarModAPILoad
* pnModFunc -> xarModFunc
* pnModAPIFunc -> xarModAPIFunc
* pnModAvailable -> xarModIsAvailable
*
* pnModDBInfoLoad -> xarModDBInfoLoad
* pnModGetInfo -> xarModGetInfo
* pnModGetUserMods -> xarModGetList(array('UserCapable' => 1))
* pnModGetAdminMods -> xarModGetList(array('AdminCapable' => 1))
* pnModCallHooks {code}
* pnModRegisterHook {code}
* pnModUnregisterHook {code}
*
* SESSION FUNCTIONS
* pnSessionDelVar -> xarSessionDelVar
* pnSessionSetVar -> xarSessionSetVar
* pnSessionGetVar -> xarSessionGetVar
*
* CONFIG FUNCTIONS
* pnConfigSetVar -> xarConfigSetVar
* pnConfigGetVar -> xarConfigGetVar
* pnConfigDelVar -> xarConfigDelVar
*
* SECURITY FUNCTIONS
* pnSecAuthAction -> xarSecurityCheck
* pnSecConfirmAuthKey -> xarSecConfirmAuthKey
* pnSecGenAuthKey -> xarSecGenAuthKey
*
* SERVER FUNCTIONS (URL URI)
* pnGetBaseURI -> xarServerGetBaseURI
* pnGetBaseURL -> xarServerGetBaseURL
* pnRedirect -> xarResponseRedirect
* pnIsRedirected -> xarResponseIsRedirected CHECK THIS ONE!!! Where is this function?
* pnLocalReferer -> xarResponseIsLocalReferer
*
* USER FUNCTIONS
* pnUserLoggedIn -> xarUserIsLoggedIn
* pnUserLogIn -> xarUserLogIn
* pnUserLogOut -> xarUserLogOut
* pnUserGetLang -> xarUserGetLang
* pnUserGetVar -> xarUserGetVar
* pnUserSetVar -> xarUserSetVar
* pnUserGetVars -> xarErrorSet('DEPRECATED_API')
* pnUserDelVar -> xarErrorSet('DEPRECATED_API')
* pnUserGetAll($startnum = 1, $numitems = -1) -> xarErrorSet('DEPRECATED_API') - invalid args!!!
*
* BLOCKS FUNCTIONS
* pnBlockGetInfo -> xarBlockGetInfo
* pnBlockLoad -> xarBlockLoad
* pnBlockLoadAll -> xarBlockLoadAll
* pnBlockShow -> xarBlockShow
* pnBlockVarsFromContent -> xarBlockVarsFromContent
* pnBlockVarsToContent -> xarBlockVarsToContent
* pnBlock_show -> xarBlock_render !!!
* pnBlock_groupShow -> xarBlock_renderGroup !!!
*
* DATABASE FUNCTIONS
* pnDBInit -> xarDBInit
* pnDBGetConn -> xarDBGetConn
* pnDBGetTables -> xarDBGetTables
*
* VAR FUNCTIONS
* pnVarPrepForStore -> xarPrepForStore
* pnVarPrepForOS -> xarPrepForOs
* pnVarPrepForDisplay -> xarPrepForDisplay
* pnVarPrepHTMLDisplay -> xarPrepHTMLDisplay
* pnVarCleanFromInput -> xarCleanFromInput
* pnVarCensor -> xarVarCeonsor CHECK THIS ONE!!!
* pnVarValidate {code}
*
* MISC FUNCTIONS
* pnInit -> xarCoreInit - !!!
* pnMail {code}
* pnGetStatusMsg -> xarGetStatusMsg
* pnSecureInput -> xarSecureInput
* add it as deprecated?
*     pnUserGetCommentOptions
*     pnUserGetCommentOptionsArray
*     pnUserGetTheme
*     pnThemeLoad
*
*
* DEPRECATED XAR FUNCTIONS
* xarModEmailURL        -> no direct equivalent
* xarVarPrepForStore()  -> use bind vars or dbconn->qstr() method
* xarExceptionSet()     -> xarErrorSet()
* xarExceptionMajor()   -> xarCurrentErrorType()
* xarExceptionId()      -> xarCurrentErrorID()
* xarExceptionValue()   -> xarCurrentError()
* xarExceptionFree()    -> xarErrorFree()
* xarExceptionHandled() -> xarErrorHandled()
* xarExceptionRender()  -> xarErrorRender()
* xarPage_sessionLess() -> xarPageCache_sessionLess()
* xarPage_httpCacheHeaders() -> xarPageCache_sendHeaders()
* /



/**
 * get base URI for PostNuke
 *
 * @deprec
 * @access public
 * @return string base URI for PostNuke
 */
function pnGetBaseURI()
{
    return xarServerGetBaseURI();
}

/**
 * get base URL for PostNuke
 *
 * @deprec
 * @access public
 * @return string base URL for PostNuke
 */
function pnGetBaseURL()
{
    return xarServerGetBaseURL();
}

/**
 * Carry out a redirect
 *
 * @deprec
 * @access public
 * @param the URL to redirect to
 * @return bool
 */
function pnRedirect($redirecturl)
{
    return xarResponseRedirect($redirecturl);
}

/**
 * Check if a redirection header was yet sent
 *
 * @deprec
 * @access public
 * @author Marco Canini
 * @return bool
 */
function pnIsRedirected()
{
    return xarResponseIsRedirected();
}

/**
 * check to see if this is a local referral
 *
 * @deprec
 * @access public
 * @return bool true if locally referred, false if not
 */
function pnLocalReferer()
{
    return xarResponseIsLocalReferer();
}

/**
 * is the user logged in?
 *
 * @deprec
 * @return bool true if the user is logged in, false if they are not
 */
function pnUserLoggedIn()
{
    return xarUserIsLoggedIn();
}

/**
 * get all user variables
 *
 * @deprec 1.52 - 2002/04/27
 * @author Marco Canini
 * @since 1.33 - 2002/02/07
 * @param uid the user id of the user
 * @return array an associative array with all variables for a user
 */
function pnUserGetVars($userId)
{
    xarErrorSet(PN_SYSTEM_EXCEPTION, 'DEPRECATED_API',
                       new SystemException(__FILE__.'('.__LINE__.')'));
    return NULL;
}

/**
 * delete a user variable
 *
 * @deprec 1.50 - 2002/04/27  Use the unregister_user_var of Modules module API instead
 * @author Marco Canini
 * @since 1.48 - 2002/04/13
 * @param name the name of the variable
 * @return false
 */
function pnUserDelVar($name)
{
    xarErrorSet(PN_SYSTEM_EXCEPTION, 'DEPRECATED_API',
                       new SystemException(__FILE__.'('.__LINE__.')'));
    return NULL;
}



/**
 * get a list of user information - it's a wrapper for users_userapi_getall
 *
 * @deprec 1.50 - 04/18/2002  Use the getall users module API instead
 * @author Marco Canini
 * @param startnum start item number
 * @param numitems how many user infos
 * @return array array of user arrays with these keys: uid, uname, name, email, url; or false on failure
 */
function pnUserGetAll($startnum = 1, $numitems = -1)
{
    xarErrorSet(PN_SYSTEM_EXCEPTION, 'DEPRECATED_API',
                       new SystemException(__FILE__.'('.__LINE__.')'));
    return;
}

/**
 * get name of current top-level module
 *
 * @deprec
 * @access public
 * @return string the name of the current top-level module, false if not in a module
 */
function pnModGetName()
{
    list($modName) = xarRequestGetInfo();
    return $modName;
}

/**
 * checks if a module is installed and its state is _PNMODULE_ACTIVE_STATE
 *
 * @deprec
 * @access public
 * @param modName registered name of module
 * @return bool true if the module is available, false if not
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function pnModAvailable($modName)
{
    return xarModIsAvailable($modName);
}

/**
 * register a hook function
 *
 * @deprec
 * @access public
 * @param hookObject the hook object
 * @param hookAction the hook action
 * @param hookArea the area of the hook (either 'GUI' or 'API')
 * @param hookModName name of the hook module
 * @param hookModType name of the hook type
 * @param hookFuncName name of the hook function
 * @return bool true on success
 * @raise DATABASE_ERROR
 */
function pnModRegisterHook($hookObject,
                           $hookAction,
                           $hookArea,
                           $hookModName,
                           $hookModType,
                           $hookFuncName)
{
    // FIXME: <marco> BAD_PARAM?

    // Get database info
    $dbconn =& xarDBGetConn();
    $pntable =& xarDBGetTables();
    $hookstable = $pntable['hooks'];

    // Insert hook
    $query = "INSERT INTO $hookstable (
              xar_id, xar_object, xar_action, xar_tarea,
              xar_tmodule, xar_ttype, xar_tfunc)
              VALUES (?,?,?,?,?,?,?)";
    $bindvars = array($dbconn->GenId($hookstable),
                      $hookObject,
                      $hookAction,
                      $hookArea,
                      $hookModName,
                      $hookModType,
                      $hookFuncName);
    $result =& $dbconn->Execute($query,$bindvars);
    if (!$result) return;

    return true;
}

/**
 * unregister a hook function
 *
 * @deprec
 * @access public
 * @param hookObject the hook object
 * @param hookAction the hook action
 * @param hookArea the area of the hook (either 'GUI' or 'API')
 * @param hookModName name of the hook module
 * @param hookModType name of the hook type
 * @param hookFuncName name of the hook function
 * @return bool true if the unregister call suceeded, false if it failed
 */
function pnModUnregisterHook($hookObject,
                             $hookAction,
                             $hookArea,
                             $hookModName,
                             $hookModType,
                             $hookFuncName)
{
    // FIXME: <marco> BAD_PARAM?

    // Get database info
    $dbconn =& xarDBGetConn();
    $pntable =& xarDBGetTables();
    $hookstable = $pntable['hooks'];

    // Remove hook
    $query = "DELETE FROM $hookstable
              WHERE xar_object = ?
              AND xar_action = ?  AND xar_tarea = ?
              AND xar_tmodule = ? AND xar_ttype = ?
              AND xar_tfunc = ?";
    $bindvars = array($hookObject,$hookAction,$hookArea,$hookModName,$hookModType,$hookFuncName);
    $result =& $dbconn->Execute($query,$bindvars);
    if (!$result) return;

    return true;
}




/**
 * get status message from previous operation
 *
 * @deprecated
 * Obtains any status message, and also destroys
 * it from the session to prevent duplication
 * @public
 * @return string the status message
 */
function pnGetStatusMsg()
{
    return xarGetStatusMsg();
}

// Prefix Add
function xarGetStatusMsg()
{
    $msg = xarSessionGetVar('statusmsg');
    xarSessionDelVar('statusmsg');
    $errmsg = xarSessionGetVar('errormsg');
    xarSessionDelVar('errormsg');

    // Error message overrides status message
    if (!empty($errmsg)) {
        return $errmsg;
    }
    return $msg;
}

/**
 * show a block
 * @deprec
 * @access private
 * @param block information parameters
 * @return output the block to show
 * @raise BAD_PARAM, DATABASE_ERROR, ID_NOT_EXIST, MODULE_FILE_NOT_EXIST
 */
function pnBlock_show($blockInfo)
{
    return xarBlock_render($blockInfo);
}

/**
 * show a block group
 * @deprec
 * @access private
 * @param group_name the name of the block group
 * @raise BAD_PARAM, DATABASE_ERROR
 */
function pnBlock_groupShow($groupName)
{
    return xarBlock_renderGroup($groupName);
}

/*
 * Translation functions - avoids globals in external code
 */

// FIXME: <marco> Who use this?
// Translate level -> name
function accesslevelname($level)
{
    $accessnames = accesslevelnames();
    return $accessnames[$level];
}

// FIXME: <marco> Who use this?
// Get all level -> name
function accesslevelnames()
{
    static $accessnames = array(  0 => _ACCESS_NONE,
                                100 => _ACCESS_OVERVIEW,
                                200 => _ACCESS_READ,
                                300 => _ACCESS_COMMENT,
                                400 => _ACCESS_MODERATE,
                                500 => _ACCESS_EDIT,
                                600 => _ACCESS_ADD,
                                700 => _ACCESS_DELETE,
                                800 => _ACCESS_ADMIN);

    return $accessnames;
}

/**
 * send an email
 *
 * @deprec 08/29/2002
 * @access public
 * @param to - recipient of the email
 * @param subject - title of the email
 * @param message - body of the email
 * @param headers - extra headers for the email
 * @return bool true if the email was sent, false if not
 */
function pnMail($to, $subject, $message, $headers)
{
    // Language translations
    switch(pnUserGetLang()) {
        case 'rus':
            $headers .= "Content-Type: text/plain; charset=koi8-r";
            $subject = convert_cyr_string($subject,"w","k");
            $message = convert_cyr_string($message,"w","k");
            $headers = convert_cyr_string($headers,"w","k");
            break;
    }

    // Mail message
    mail($to, $subject, $message, $headers);
}

/**
 * validate a user variable
 *
 * @deprec
 * @author Damien Bonvillain
 * @author Gregor J. Rothfuss
 * @since 1.23 - 2002/02/01
 * @access public
 * @param var the variable to validate
 * @param type the type of the validation to perform
 * @param args optional array with validation-specific settings
 * @return bool true if the validation was successful, false otherwise
 */
function pnVarValidate($var, $type, $args = NULL)
{
    // all characters must be 7 bit ascii
    $length = strlen($var);
    $idx = 0;
    while ($length--) {
        $c = $var[$idx++];
        if(ord($c) > 127){
            return false;
        }
    }

    switch ($type) {

       case 'email':
         $regexp = '/^(?:[^\s\000-\037\177\(\)<>@,;:\\"\[\]]\.?)+@(?:[^\s\000-\037\177\(\)<>@,;:\\\"\[\]]\.?)+\.[a-z]{2,6}$/Ui';
         if(preg_match($regexp, $var)) {
            return true;
         } else {
            return false;
         }
        break;

       case 'url':
        $regexp = '/^([!\$\046-\073=\077-\132_\141-\172~]|(?:%[a-f0-9]{2}))+$/i';
        if (!preg_match($regexp, $var)) {
            return false;
        }
        $url_array = @parse_url($var);
        if (empty($url_array)) {
            return false;
        } else {
            return !empty($url_array['scheme']);
        }
        break;
   }
}

/**
 * Gets a list of active modules that have a user interface.
 *
 * @return array array of module information arrays
 * @raise DATABASE_ERROR
 */
function pnModGetUserMods()
{
    return xarModAPIFunc('modules',
                          'admin',
                          'GetList',
                          array('filter'     => array('UserCapable' => 1)));
}

/**
 * Gets a list of active modules that have an administrative interface.
 *
 * @return array array of module information arrays
 * @raise DATABASE_ERROR
 */
function pnModGetAdminMods()
{
    return xarModAPIFunc('modules',
                          'admin',
                          'GetList',
                          array('filter'     => array('AdminCapable' => 1)));
}

/**
 * delete a configuration variable
 *
 * @access public
 * @param name the name of the variable
 * @raise BAD_PARAM
 * @return bool
 */
function pnConfigDelVar($name)
{
    global $pnconfig;

    if (empty($name)) {
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__."(".__LINE__."): Empty name."));return;
    }

    // Just a quick handle for Multisites
    // Does not change the db yet!
    if (!empty($pnconfig[$name])) {
        unset ($pnconfig[$name]);
        return true;
    }

    // Don't allow deleting at current
    return false;
}

// Stubs for pnAPI compatibility testing

function pnBlockGetInfo($bid)
{
    $blockinfo = xarModAPIFunc('blocks',
                               'admin',
                               'getinfo', array('blockId' => $bid));
    return $blockinfo;
}

function pnBlockLoad($modname, $block)
{
    return xarBlockLoad($modname, $block);
}

function pnBlockLoadAll()
{
    return xarBlockLoadAll();
}

function pnBlockShow($modname, $block, $blockinfo=array())
{
    return xarBlockShow($modname, $block, $blockinfo);
}

function pnBlockVarsFromContent($content)
{
    return xarBlockVarsFromContent($content);
}

function pnBlockVarsToContent($vars)
{
    return xarBlockVarsToContent($vars);
}

function pnConfigGetVar($name)
{
    return xarConfigGetVar($name);
}

function pnConfigSetVar($name, $value)
{
    return xarConfigSetVar($name, $value);
}

/**
 * get a list of database connections
 * @deprec
 * @access public
 * @return array array of database connections
 */
function pnDBGetConn()
{
    $dbconn =& xarDBGetConn();
    return array($dbconn);
}

/**
 * get a list of database tables
 * @deprec
 * @access public
 * @return array array of database tables
 */
function pnDBGetTables()
{
    return xarDBGetTables();
}

function pnDBInit()
{
    return xarDBInit();
}

function pnInit()
{
    return xarCoreInit();
}

function pnModAPIFunc($modName, $modType='user', $funcName='main', $args = array())
{
    return xarModAPIFunc($modName, $modType, $funcName, $args);
}

function pnModAPILoad($modName, $modType='user')
{
    return xarModAPILoad($modName, $modType);
}

function pnModCallHooks($hookobject, $hookaction, $hookid, $extrainfo)
{
    if ($hookobject == 'item' && $hookaction == 'display') {
        // Note : this is the only "commonly used" hook in PostNuke that
        //        expects to receive a string in return
        $hookoutput = xarModCallHooks($hookobject, $hookaction, $hookid, $extrainfo);
        if (isset($hookoutput) && is_array($hookoutput)) {
            return join('',$hookoutput);
        } else {
            return $hookoutput;
        }
    } else {
        return xarModCallHooks($hookobject, $hookaction, $hookid, $extrainfo);
    }
}

function pnModDBInfoLoad($modname, $directory='')
{
    return xarModDBInfoLoad($modname, $directory);
}

function pnModDelVar($modName, $name)
{
    return xarModDelVar($modName, $name);
}

function pnModFunc($modname, $type='user', $func='main', $args=array())
{
    return xarModFunc($modname, $type, $func, $args);
}

function pnModGetIDFromName($module)
{
    return xarModGetIDFromName($module);
}

function pnModGetInfo($modid)
{
    return xarModGetInfo($modid);
}

function pnModGetVar($modName, $name)
{
    return xarModGetVar($modName, $name);
}

function pnModLoad($modname, $type='user')
{
    return xarModLoad($modname, $type);
}

function pnModSetVar($modName, $name, $value)
{
    return xarModSetVar($modName, $name, $value);
}

function pnModURL($modName = NULL, $modType = 'user', $funcName = 'main', $args = array(), $generateXMLURL = NULL)
{
    return xarModURL($modName, $modType, $funcName, $args, $generateXMLURL);
}

function pnSecAddSchema($component, $schema)
{
    return xarSecAddSchema($component, $schema);
}

define("ACCESS_NONE","ACCESS_NONE");
define("ACCESS_OVERVIEW","ACCESS_OVERVIEW");
define("ACCESS_READ","ACCESS_READ");
define("ACCESS_COMMENT","ACCESS_COMMENT");
define("ACCESS_MODERATE","ACCESS_MODERATE");
define("ACCESS_EDIT","ACCESS_EDIT");
define("ACCESS_ADD","ACCESS_ADD");
define("ACCESS_DELETE","ACCESS_DELETE");
define("ACCESS_ADMIN","ACCESS_ADMIN");

function pnSecAuthAction($testRealm, $testComponent, $testInstance, $testLevel)
{
    $temp = explode('::',$testComponent);
    $testModule = $temp[0];
    $testComponent = isset($temp[1]) ? $temp[1] : 'All';
    $masks = new xarMasks();
    return xarSecurityCheck("pnLegacyMask",0,$testComponent, $testInstance,'','',$testRealm,$masks->xarSecLevel($testLevel));
}

function pnSecConfirmAuthKey()
{
    return xarSecConfirmAuthKey();
}

function pnSecGenAuthKey($modName='')
{
    return xarSecGenAuthKey($modName);
}

function pnSecGetAuthInfo()
{
    return xarSecGetAuthInfo();
}

function pnSecGetLevel($perms, $testrealm, $testcomponent, $testinstance)
{
    return xarSecGetLevel($perms, $testrealm, $testcomponent, $testinstance);
}

function pnSecureInput()
{
    return xarSecureInput();
}

/**
 * Delete a session variable
 * @deprec
 * @param name name of the session variable to delete
 */
function pnSessionDelVar($name)
{
    return xarSessionDelVar($name);
}

/**
 * Get a session variable
 * @deprec
 * @param name name of the session variable to get
 */
function pnSessionGetVar($name)
{
    return xarSessionGetVar($name);
}

/**
 * Set a session variable
 * @deprec
 * @param name name of the session variable to set
 * @param value value to set the named session variable
 */
function pnSessionSetVar($name, $value)
{
    return xarSessionSetVar($name, $value);
}

function pnUserGetLang()
{
    return xarUserGetLang();
}

function pnUserGetVar($name, $uid = NULL)
{
    return xarUserGetVar($name, $uid);
}

function pnUserLogIn($uname, $pass, $rememberme)
{
    return xarUserLogIn($uname, $pass, $rememberme);
}

function pnUserLogOut()
{
    return xarUserLogOut();
}

function pnUserSetVar($name, $value)
{
    return xarUserSetVar($name, $value);
}

/**
 * Remove censored words.
 * Moved to legacy for the time being.  Censoring handled by transform hook now.
 *
 * Removes all censored words from the variables handed to the function.
 * Can have as many parameters as desired.
 *
 * @access public
 * @return mixed prepared variable if only one variable passed
 * in, otherwise an array of prepared variables
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function pnVarCensor()
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

//        foreach ($xarVar_censoredWords as $censoredWord) {
            // Simple word
//            $search[] = "/\b$censoredWord\b/i";

            // Common replacements
//            $mungedword = preg_replace($repSearch, $repReplace, $censoredWord);
//            if ($mungedword != $censoredWord) {
//                $search[] = "/\b$mungedword\b/";
//            }
//        }
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
    // short open tag < followed by ? (we do it like this, otherwise our qa tests go bonkers)
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

function pnVarPrepForDisplay($var)
{
    return xarVarPrepForDisplay($var);
}

function pnVarPrepForOS($var)
{
    return xarVarPrepForOS($var);
}

function pnVarPrepForStore($var)
{
    return xarVarPrepForStore($var);
}

function pnVarPrepHTMLDisplay($var)
{
    return xarVarPrepHTMLDisplay($var);
}


function xarBlockTypeExists($modName, $blockType)
{
    if (!xarModAPILoad('blocks', 'admin')) return;
    $args = array('modName'=>$modName, 'blockType'=>$blockType);
    return xarModAPIFunc('blocks', 'admin', 'block_type_exists', $args);
}

/**
 * get the user's language
 *
 * @return string the name of the user's language
 * @raise DATABASE_ERROR
 */
function xarUserGetLang()
{
    // FIXME: <marco> DEPRECATED?
    $locale = xarUserGetNavigationLocale();
    $data =& xarMLSLoadLocaleData($locale);
    if (!isset($data)) return; // throw back
    return $data['/language/iso3code'];
}

/**
 * Get the user's theme directory path
 *
 * @return string the user's theme directory path if successful, void otherwise
 */
function xarUser_getThemeName()
{
    if (!xarUserIsLoggedIn()) {
        return;
    }
    $themeName = xarUserGetVar('Theme');
    if (xarCurrentErrorType() != XAR_NO_EXCEPTION) {
        // Here we can't raise an exception
        // so what we can do here is only to log the exception
        // and call xarExceptionFree
        //xarLogException(XARLOG_LEVEL_ERROR);
        //xarExceptionFree();
        return;
    }
    return $themeName;
}

/*
 * Register an instance schema with the security
 * system
 *
 * @access public
 * @param string component the component to add
 * @param string schema the security schema to add
 *
 * Will fail if an attempt is made to overwrite an existing schema
 */
function xarSecAddSchema($component, $schema)
{
    $msg = xarML('This call needs to be removed');
    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'DEPRECATED_API',
                    new SystemException($msg));
    return true;
}

function pnUserGetTheme()
{
    return xarUserGetNavigationThemeName();
}

function pnThemeLoad()
{
    return true;
}

function opentable()
{
    return '<table><tr><td>';
}

function closetable()
{
    return '</td></tr></table>';
}

/**
 * Generates an URL that reference to a module function via Email.
 *
 * @access public
 * @param modName string registered name of module
 * @param modType string type of function
 * @param funcName string module function
 * @param args array of arguments to put on the URL
 * @return mixed absolute URL for call, or false on failure
 */
function xarModEmailURL($modName = NULL, $modType = 'user', $funcName = 'main', $args = array())
{
//TODO: <garrett> either deprecate this function or keep it in synch with xarModURL *or* add another param
//      to xarModURL to handle this functionality. See bug #372
// Let's depreciate it for 1.0.0  next release I will remove it.
    if (empty($modName)) {
        return xarServerGetBaseURL() . 'index.php';
    }

    if (xarCurrentErrorType() != XAR_NO_EXCEPTION) {
        // If exceptionId is MODULE_FUNCTION_NOT_EXIST there's no problem,
        // this exception means that the module does not support short urls
        // for this $modType.
        // If exceptionId is MODULE_FILE_NOT_EXIST there's no problem too,
        // this exception means that the module does not have the $modType API.
        if (xarExceptionId() != 'MODULE_FUNCTION_NOT_EXIST' &&
            xarExceptionId() != 'MODULE_FILE_NOT_EXIST') {
            // In all other cases we just log the exception since we must always
            // return a valid url
            xarLogException(XARLOG_LEVEL_ERROR);
        }
        xarExceptionFree();
    }

    // The arguments
    $urlArgs[] = "module=$modName";
    if ((!empty($modType)) && ($modType != 'user')) {
        $urlArgs[] = "type=$modType";
    }
    if ((!empty($funcName)) && ($funcName != 'main')) {
        $urlArgs[] = "func=$funcName";
    }
    $urlArgs = join('&', $urlArgs);

    $url = "index.php?$urlArgs";

    foreach ($args as $k=>$v) {
        if (is_array($v)) {
            foreach($v as $l=>$w) {
                if (isset($w)) {
                    $url .= "&$k" . "[$l]=$w";
                }
            }
        } elseif (isset($v)) {
            $url .= "&$k=$v";
        }
    }

    // The URL
    return xarServerGetBaseURL() . $url;
}

/**
* Ready database output
 *
 * Gets a variable, cleaning it up such that the text is
 * stored in a database exactly as expected. Can have as many parameters as desired.
 *
 * @deprec 2004-02-18
 * @access public
 * @return mixed prepared variable if only one variable passed
 * in, otherwise an array of prepared variables
 * @todo are we allowing arrays and objects for real?
 */
function xarVarPrepForStore()
{
    // Issue a WARNING as this function is deprecated
    xarLogMessage('Using deprecated function xarVarPrepForStore, use bind variables instead',XARLOG_LEVEL_WARNING);
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

function xarExceptionSet($major, $errorID, $value = NULL)
{
    xarErrorSet($major, $errorID, $value);
}

function xarExceptionMajor()
{
    return xarCurrentErrorType();
}    // deprecated

/**
* Gets the identifier of current error
 *
 * Returns the error identifier corresponding to the current error.
 * If invoked when no error was raised, a void value is returned.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return string the error identifier
 * @deprec 2004-04-01
 */
function xarExceptionId()
{
    return xarCurrentErrorID();
} 

/**
* Gets the current error object
 *
 * Returns the value corresponding to the current error.
 * If invoked when no error or an error for which there is no associated information was raised, a void value is returned.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return mixed error value object
 * @deprec 2004-04-01
 */
function xarExceptionValue()
{
    return xarCurrentError();
}    // deprecated

/**
* Resets current error status
 *
 * xarErrorFree is a shortcut for xarErrorSet(XAR_NO_EXCEPTION, NULL, NULL).
 * You must always call this function when you handle a caught error or
 * equivalently you don't throw the error back to the caller.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return void
 * @deprec 2004-04-01
 */
function xarExceptionFree()
{
    xarErrorFree();
} 

/**
* Handles the current error
 *
 * You must always call this function when you handle a caught error.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return void
 * @deprec 2004-04-01
 */
function xarExceptionHandled()
{
    xarErrorHandled();
}

/**
* Renders the current error
 *
 * Returns a string formatted according to the $format parameter that provides all the information
 * available on current error.
 * If there is no error currently raised an empty string is returned.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @param format string one of template or plain
 * @param stacktype string one of CORE or ERROR
 * @return string the string representing the raised error
 * @deprec 2004-04-01
 */
function xarExceptionRender($format)
{
    return xarErrorRender($format);
}    // deprecated

/**
 * Session-less page caching
 *
 * @author mikespub, jsb
 * @access private
 * @return void
 * @deprec 2005-02-01
 */
function xarPage_sessionLess()
{
    xarPageCache_sessionLess();
}

/**
 * Send HTTP headers for page caching (or return 304 Not Modified)
 *
 * @author mikespub, jsb
 * @access private
 * @return void
 * @deprec 2005-02-01
 */
function xarPage_httpCacheHeaders($cache_file)
{
    if (!file_exists($cache_file)) { return; }
    $modtime = filemtime($cache_file);

    xarPageCache_sendHeaders($modtime);
}

?>
