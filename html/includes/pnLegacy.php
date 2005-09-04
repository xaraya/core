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

// LEGACY CONSTANTS

// Old constants

define('_PNYES', 1);
define('_PNNO', 0);
//'All' and 'unregistered' for user and group permissions
define('_PNPERMS_ALL', '-1');
define('_PNPERMS_UNREGISTERED', '0');


define('_PN_VERSION_NUM', '0.8');
define('_PN_VERSION_ID',  'PostNuke');
define('_PN_VERSION_SUB', 'adam_baum');

define('_PNMODULE_STATE_UNINITIALISED', 1);
define('_PNMODULE_STATE_INACTIVE', 2);
define('_PNMODULE_STATE_ACTIVE', 3);
// FIXME: <marco> What're these two for?
define('_PNMODULE_STATE_MISSING', 4);
define('_PNMODULE_STATE_UPGRADED', 5);

// This isn't a module state, but only a convenient definition to indicates,
// where it's used, that we don't care about state, any state is good
define('_PNMODULE_STATE_ANY', 0);

 // FIXME: <marco> i think we could remove it, now validation does this job
define('_UDCONST_MANDATORY',1024); // indicates a cord field that can't be removed'

define('_UDCONST_CORE', 0); // indicates a core field
define('_UDCONST_STRING', 1);
define('_UDCONST_TEXT', 2);
define('_UDCONST_FLOAT', 4);
define('_UDCONST_INTEGER', 8);


define('_PNAUTH_AUTHENTICATION', 1);
define('_PNAUTH_DYNAMIC_USER_DATA_HANDLER', 2);
define('_PNAUTH_PERMISSIONS_OVERRIDER', 16);
define('_PNAUTH_USER_CREATEABLE', 32);
define('_PNAUTH_USER_DELETEABLE', 64);
define('_PNAUTH_USER_ENUMERABLE', 128);

/*
 * Error codes
 */
define('_PNAUTH_FAILED', -1);


// LEGACY FUNCTIONS

/**
 * get request info for current page
 *
 * @deprec
 * @author Marco Canini, Michel Dalle
 * @access private
 * @returns array
 * @return requested module, type and func
 */
function pnGetRequestInfo()
{
    return pnRequestGetInfo();
}

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
    return pnServerGetBaseURI();
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
    return pnServerGetBaseURL();
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
    return pnResponseRedirect($redirecturl);
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
    return pnResponseIsRedirected();
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
    return pnResponseIsLocalReferer();
}

/**
 * is the user logged in?
 *
 * @deprec
 * @return bool true if the user is logged in, false if they are not
 */
function pnUserLoggedIn()
{
    return pnUserIsLoggedIn();
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
    pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DEPRECATED_API',
                       new SystemException(__FILE__.'('.__LINE__.')'));
    return;
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
    pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DEPRECATED_API',
                       new SystemException(__FILE__.'('.__LINE__.')'));
    return;
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
    pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DEPRECATED_API',
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
function pnModGetName() {
    list($modName) = pnRequestGetInfo();

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
    return pnModIsAvailable($modName);
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
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();
    $hookstable = $pntable['hooks'];

    // Insert hook
    $query = "INSERT INTO $hookstable (
              pn_id,
              pn_object,
              pn_action,
              pn_tarea,
              pn_tmodule,
              pn_ttype,
              pn_tfunc)
              VALUES (
              " . pnVarPrepForStore($dbconn->GenId($hookstable)) . ",
              '" . pnVarPrepForStore($hookObject) . "',
              '" . pnVarPrepForStore($hookAction) . "',
              '" . pnVarPrepForStore($hookArea) . "',
              '" . pnVarPrepForStore($hookModName) . "',
              '" . pnVarPrepForStore($hookModType) . "',
              '" . pnVarPrepForStore($hookFuncName) . "')";
    $dbconn->Execute($query);

    if($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
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
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();
    $hookstable = $pntable['hooks'];

    // Remove hook
    $query = "DELETE FROM $hookstable
              WHERE pn_object = '" . pnVarPrepForStore($hookObject) . "'
              AND pn_action = '" . pnVarPrepForStore($hookAction) . "'
              AND pn_tarea = '" . pnVarPrepForStore($hookArea) . "'
              AND pn_tmodule = '" . pnVarPrepForStore($hookModName) . "'
              AND pn_ttype = '" . pnVarPrepForStore($hookModType) . "'
              AND pn_tfunc = '" . pnVarPrepForStore($hookFuncName) . "'";
    $dbconn->Execute($query);

    if($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
    }

    return true;
}

function pnGetStatusMsg()
{
    $msg = pnSessionGetVar('statusmsg');
    pnSessionDelVar('statusmsg');
    $errmsg = pnSessionGetVar('errormsg');
    pnSessionDelVar('errormsg');

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
    return pnBlock_render($blockInfo);
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
    return pnBlock_renderGroup($groupName);
}

/*
 * Translation functions - avoids globals in external code
 */

// FIXME: <marco> Who use this?
// Translate level -> name
function accesslevelname($level) {
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
    return pnModGetList(array('UserCapable' => 1));
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
    return pnModGetList(array('AdminCapable' => 1));
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

/**
 * get the user's theme
 *
 * @returns string
 * @return the name of the user's theme
 * @raise DATABASE_ERROR
 * modified May the 15th, 
 * return the name of the folder where themes are stored, defined in
 * modules_vars, themesfolder ("themes/" by default).
 */
function pnUserGetTheme()
{
    // Order of theme priority:
    // - page-specific
    // - user
    // - system
    // Page-specific theme

    /***********
    * modification  of May the 15th.
    * pnUserGetTheme() return the folder where the theme is stored.
    * a fresh install create the var themesfolder in modules_vars.
    * an update should also :-(
    * if it not happens, uncomment these lines and comment them later. */
    $themesfolder = pnConfigGetVar('themesfolder');
    if (empty($themesfolder)) {
        pnConfigSetVar('themesfolder','themes/');
    }
    /*********************************************/

// Q: where was this pagetheme originally supposed to come from ?
    $pagetheme = pnVarCleanFromInput('theme');

    if (!empty($pagetheme)) {
	$pagetheme = pnConfigGetVar('themesfolder').pnVarPrepForOS(pnVarCleanFromInput('theme'));
	if (file_exists($pagetheme)) {
	    return $pagetheme;
	}
    }
    if (pnUserLoggedIn()) {
        $usertheme = pnUserGetVar('theme');
        if (!isset($usertheme) && pnExceptionMajor() != PN_NO_EXCEPTION) {
            if (pnExceptionId() == 'DATABASE_ERROR') {
                return; // throw back
            }
            // Ingnore other exceptions
            pnExceptionFree();
        }		
        if (!empty($usertheme)) {
	    $usertheme = pnConfigGetVar('themesfolder').pnVarPrepForOS($usertheme);
	    if (file_exists($usertheme)) {
		return $usertheme;
	    }
        }
    }
    $defaulttheme = pnConfigGetVar('Default_Theme');
    if (!isset($defaulttheme) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        if (pnExceptionId() == 'DATABASE_ERROR') {
            return; // throw back
        }
        // Ingnore other exceptions
        pnExceptionFree();
    }
    if (!empty($defaulttheme)) { 
	$defaulttheme = pnConfigGetVar('themesfolder').pnVarPrepForOS($defaulttheme);
	if (file_exists($defaulttheme)) {
	    return $defaulttheme;
	}
    }
    // Try to fallback with 'PostNuke'
    if (file_exists(pnConfigGetVar('themesfolder').'PostNuke')) {
        return pnConfigGetVar('themesfolder').'PostNuke';
    }
    $msg = pnML('Cannot find a suitable theme name.');
    pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNKNOWN',
                   new SystemException($msg));
    return;
}

?>
