<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Marco Canini
// Purpose of file: Legacy functions
// ----------------------------------------------------------------------

/***********************************************************************
* This file is for legacy functions needed to make it
* easier to use pn modules in Xaraya. Please don't fill it with useless
* stuff or deprecated API funcs except as wrappers, and also.. please
* do not duplicated constants that already exist in xaraya core
* If a function did not exist in pn before...don't prefix it with pn
***********************************************************************/

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
* SESSION FUNCTIONS
* pnSessionDelVar -> xarSessionDelVar
* pnSessionSetVar -> xarSessionSetVar
* pnSessionGetVar -> xarSessionGetVar
*
* CONFIG FUNCTIONS
* pnConfigSetVar -> xarConfigSetVar
* pnConfigGetVar -> xarConfigGetVar
*
* SERVER FUNCTIONS (URL URI)
* pnGetBaseURI -> xarServerGetBaseURI
* pnGetBaseURL -> xarServerGetBaseURL
* pnRedirect -> xarResponseRedirect
* pnIsRedirected -> xarResponseIsRedirected CHECK THIS ONE!
*
* USER FUNCTIONS
* pnUserLoggedIn -> xarUserIsLoggedIn
* pnUserLogIn -> xarUserLogIn
* pnUserLogOut -> xarUserLogOut
*
* BLOCKS
*
* VAR
* pnVarPrepForStore -> xarPrepForStore
* pnVarPrepForOS -> xarPrepForOs
* pnVarPrepForDisplay -> xarPrepForDisplay
* pnVarPrepHTMLDisplay -> xarPrepHTMLDisplay
* pnVarCleanFromInput -> xarCleanFromInput
* pnVarCensor -> xarVarCeonsor CHECK THIS ONE!
*
*
*/
/**
 * get base URI for PostNuke
 *
 * @deprec
 * @access public
 * @returns string
 * @return base URI for PostNuke
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
 * @returns string
 * @return base URL for PostNuke
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
 * @returns bool
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
 * @returns bool
 */
function pnIsRedirected()
{
    return xarResponseIsRedirected();
}

/**
 * check to see if this is a local referral
 *
 * @deprec
 * @access ?????
 * @returns bool
 * @return true if locally referred, false if not
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
    xarExceptionSet(PN_SYSTEM_EXCEPTION, 'DEPRECATED_API',
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
    xarExceptionSet(PN_SYSTEM_EXCEPTION, 'DEPRECATED_API',
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
    xarExceptionSet(PN_SYSTEM_EXCEPTION, 'DEPRECATED_API',
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
 * get name of current top-level module
 *
 * @deprec
 * @access public
 * @return string the name of the current top-level module, false if not in a module
 */
function xarModGetName()
{
    //TODO Work around for the prefix.
    list($modName) = xarRequestGetInfo();

    return $modName;
}

/**
 * checks if a module is installed and its state is _PNMODULE_ACTIVE_STATE
 *
 * @deprec
 * @access public
 * @param modName registered name of module
 * @returns bool
 * @return true if the module is available, false if not
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
 * @returns bool
 * @return true on success
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
    list($dbconn) = xarDBGetConn();
    $pntable = xarDBGetTables();
    $hookstable = $pntable['hooks'];

    // Insert hook
    $query = "INSERT INTO $hookstable (
              xar_id,
              xar_object,
              xar_action,
              xar_tarea,
              xar_tmodule,
              xar_ttype,
              xar_tfunc)
              VALUES (
              " . xarVarPrepForStore($dbconn->GenId($hookstable)) . ",
              '" . xarVarPrepForStore($hookObject) . "',
              '" . xarVarPrepForStore($hookAction) . "',
              '" . xarVarPrepForStore($hookArea) . "',
              '" . xarVarPrepForStore($hookModName) . "',
              '" . xarVarPrepForStore($hookModType) . "',
              '" . xarVarPrepForStore($hookFuncName) . "')";
    $dbconn->Execute($query);

    if($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
    }

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
 * @returns bool
 * @return true if the unregister call suceeded, false if it failed
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
    list($dbconn) = xarDBGetConn();
    $pntable = xarDBGetTables();
    $hookstable = $pntable['hooks'];

    // Remove hook
    $query = "DELETE FROM $hookstable
              WHERE xar_object = '" . xarVarPrepForStore($hookObject) . "'
              AND xar_action = '" . xarVarPrepForStore($hookAction) . "'
              AND xar_tarea = '" . xarVarPrepForStore($hookArea) . "'
              AND xar_tmodule = '" . xarVarPrepForStore($hookModName) . "'
              AND xar_ttype = '" . xarVarPrepForStore($hookModType) . "'
              AND xar_tfunc = '" . xarVarPrepForStore($hookFuncName) . "'";
    $dbconn->Execute($query);

    if($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
    }

    return true;
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
 * @returns bool
 * @return true on success
 * @raise DATABASE_ERROR
 */
function xarModRegisterHook($hookObject,
                           $hookAction,
                           $hookArea,
                           $hookModName,
                           $hookModType,
                           $hookFuncName)
{
    // FIXME: <marco> BAD_PARAM?

    // Get database info
    list($dbconn) = xarDBGetConn();
    $pntable = xarDBGetTables();
    $hookstable = $pntable['hooks'];

    // Insert hook
    $query = "INSERT INTO $hookstable (
              xar_id,
              xar_object,
              xar_action,
              xar_tarea,
              xar_tmodule,
              xar_ttype,
              xar_tfunc)
              VALUES (
              " . xarVarPrepForStore($dbconn->GenId($hookstable)) . ",
              '" . xarVarPrepForStore($hookObject) . "',
              '" . xarVarPrepForStore($hookAction) . "',
              '" . xarVarPrepForStore($hookArea) . "',
              '" . xarVarPrepForStore($hookModName) . "',
              '" . xarVarPrepForStore($hookModType) . "',
              '" . xarVarPrepForStore($hookFuncName) . "')";
    $dbconn->Execute($query);

    if($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
    }

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
 * @returns bool
 * @return true if the unregister call suceeded, false if it failed
 */
function xarModUnregisterHook($hookObject,
                             $hookAction,
                             $hookArea,
                             $hookModName,
                             $hookModType,
                             $hookFuncName)
{
    // FIXME: <marco> BAD_PARAM?

    // Get database info
    list($dbconn) = xarDBGetConn();
    $pntable = xarDBGetTables();
    $hookstable = $pntable['hooks'];

    // Remove hook
    $query = "DELETE FROM $hookstable
              WHERE xar_object = '" . xarVarPrepForStore($hookObject) . "'
              AND xar_action = '" . xarVarPrepForStore($hookAction) . "'
              AND xar_tarea = '" . xarVarPrepForStore($hookArea) . "'
              AND xar_tmodule = '" . xarVarPrepForStore($hookModName) . "'
              AND xar_ttype = '" . xarVarPrepForStore($hookModType) . "'
              AND xar_tfunc = '" . xarVarPrepForStore($hookFuncName) . "'";
    $dbconn->Execute($query);

    if($dbconn->ErrorNo() != 0) {
        $msg = xarMLByKey('DATABASE_ERROR', $query);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
    }

    return true;
}

/**
 * get status message from previous operation
 *
 * @deprec
 * Obtains any status message, and also destroys
 * it from the session to prevent duplication
 * @public
 * @returns string
 * @return the status message
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
 * @returns bool
 * @return true if the email was sent, false if not
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
 * @returns array
 * @return array of module information arrays
 * @raise DATABASE_ERROR
 */
function pnModGetUserMods()
{
    return xarModGetList(array('UserCapable' => 1));
}

/**
 * Gets a list of active modules that have an administrative interface.
 *
 * @returns array
 * @return array of module information arrays
 * @raise DATABASE_ERROR
 */
function pnModGetAdminMods()
{
    return xarModGetList(array('AdminCapable' => 1));
}

/**
 * Gets a list of active modules that have an administrative interface.
 *
 * @returns array
 * @return array of module information arrays
 * @raise DATABASE_ERROR
 */
function xarModGetAdminMods()
{
    //TODO Workaround for admin panels.
    return xarModGetList(array('AdminCapable' => 1));
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
    return xarBlockGetInfo($bid);
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
 * @returns array
 * @return array of database connections
 */
function pnDBGetConn()
{
    return xarDBGetConn();
}

/**
 * get a list of database tables
 * @deprec
 * @access public
 * @returns array
 * @return array of database tables
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

function pnModAPIFunc($modName, $modType, $funcName, $args = NULL)
{
    return xarModAPIFunc($modName, $modType, $funcName, $args);
}

function pnModAPILoad($modName, $modType)
{
    return xarModAPILoad($modName, $modType);
}

function pnModCallHooks($hookobject, $hookaction, $hookid, $extrainfo)
{
    return xarModCallHooks($hookobject, $hookaction, $hookid, $extrainfo);
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

function pnSecAuthAction($testRealm, $testComponent, $testInstance, $testLevel, $userId = NULL)
{
    return xarSecAuthAction($testRealm, $testComponent, $testInstance, $testLevel, $userId);
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

function pnUserGetVar($name, $uid=-1)
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

function pnVarCensor()
{
    return xarVarCensor();
}

function pnVarCleanFromInput()
{
    return xarVarCleanFromInput();
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
?>
