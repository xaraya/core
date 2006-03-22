<?php
/**
 * User System
 *
 * @package user
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Jim McDonald
 * @todo <marco> user status field
 * @todo <johnny> look over xarUserComparePasswords
 */

/**
 * Dynamic User Data types for User Properties
 */
/* (currently unused)
define('XARUSER_DUD_TYPE_CORE', 0); // indicates a core field
define('XARUSER_DUD_TYPE_STRING', 1);
define('XARUSER_DUD_TYPE_TEXT', 2);
define('XARUSER_DUD_TYPE_DOUBLE', 4);
define('XARUSER_DUD_TYPE_INTEGER', 8);
*/

/**
 * Authentication modules capabilities
 * (to be revised e.g. to differentiate read & update capability for core & dynamic)
 */
define('XARUSER_AUTH_AUTHENTICATION', 1);
define('XARUSER_AUTH_DYNAMIC_USER_DATA_HANDLER', 2);
define('XARUSER_AUTH_PERMISSIONS_OVERRIDER', 16);
define('XARUSER_AUTH_USER_CREATEABLE', 32);
define('XARUSER_AUTH_USER_DELETEABLE', 64);
define('XARUSER_AUTH_USER_ENUMERABLE', 128);

/*
 * Error codes
 */
define('XARUSER_AUTH_FAILED', -1);
define('XARUSER_AUTH_DENIED', -2);
define('XARUSER_LAST_RESORT', -3);

/**
 * Initialise the User System
 *
 * @access protected
 * @global xarUser_authentication modules array
 * @param args[authenticationModules] array
 * @return bool true on success
 */
function xarUser_init(&$args, $whatElseIsGoingLoaded)
{
    // User System and Security Service Tables
    $systemPrefix = xarDBGetSystemTablePrefix();

    // CHECK: is this needed?
    $tables = array('roles'            => $systemPrefix . '_roles',
                    'realms'           => $systemPrefix . '_security_realms',
                    'rolemembers' => $systemPrefix . '_rolemembers');

    xarDB_importTables($tables);

    $GLOBALS['xarUser_authenticationModules'] = $args['authenticationModules'];

    xarMLS_setCurrentLocale(xarUserGetNavigationLocale());
    xarTplSetThemeName(xarUserGetNavigationThemeName());

    // Register the UserLogin event
    xarEvt_registerEvent('UserLogin');
    // Register the UserLogout event
    xarEvt_registerEvent('UserLogout');

    // Subsystem initialized, register a handler to run when the request is over
    //register_shutdown_function ('xarUser__shutdown_handler');
    return true;
}

/**
 * Shutdown handler for user subsystem
 *
 * @access private
 */
function xarUser__shutdown_handler()
{
    //xarLogMessage("xarUser shutdown handler");
}

/**
 * Log the user in
 *
 * @author Marco Canini
 * @access public
 * @param userName string the name of the user logging in
 * @param password string the password of the user logging in
 * @param rememberMe bool whether or not to remember this login
 * @return bool true if the user successfully logged in
 * @raise DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_FUNCTION_NOT_EXIST
 * @todo <marco> #1 here we could also set a last_logon timestamp
 */
function xarUserLogIn($userName, $password, $rememberMe=0)
{
    if (xarUserIsLoggedIn()) {
        return true;
    }
    if (empty($userName)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'userName');
        return;
    }

    if (empty($password)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'password');
        return;
    }

    $userId = XARUSER_AUTH_FAILED;
    $args = array('uname' => $userName, 'pass' => $password);
    // FIXME: <rabbitt> Do we want to actually put this here or do this
    //        another way? Maybe record the exception stack before we go
    //        into the foreach loop below (which can kill any exceptions
    //        that are set prior to entering this function)....
    if (xarCurrentErrorType() != XAR_NO_EXCEPTION) return;
    foreach($GLOBALS['xarUser_authenticationModules'] as $authModName) {
        // Bug #918 - If the module has been deactivated, then continue
        // checking with the next available authentication module
        if (!xarModIsAvailable($authModName)) continue;
        // Every authentication module must at least implement the
        // authentication interface so there's at least the authenticate_user
        // user api function
        if (!xarModAPILoad($authModName, 'user')) continue;
        $userId = xarModAPIFunc($authModName, 'user', 'authenticate_user', $args);
        if (!isset($userId)) {
            return; // throw back
        } elseif ($userId != XARUSER_AUTH_FAILED) {
            // Someone authenticated the user or passed XARUSER_AUTH_DENIED
            break;
        }

        // if here $userId is XARUSER_AUTH_FAILED, try with next auth module
        // but free exceptions set by previous auth module
        xarErrorFree();
    }
    if ($userId == XARUSER_AUTH_FAILED || $userId == XARUSER_AUTH_DENIED) {
        if (xarModGetVar('privileges','lastresort')) {
            $secret = @unserialize(xarModGetVar('privileges','lastresort'));
            if ($secret['name'] == MD5($userName) && $secret['password'] == MD5($password)) {
                $userId = XARUSER_LAST_RESORT;
                $rememberMe = 0;
            }
         }
        if ($userId !=XARUSER_LAST_RESORT) {
            return false;
        }
    }

    // Catch common variations (0, false, '', ...)
    if (empty($rememberMe)) $rememberMe = 0;
    else $rememberMe = 1;

    // Set user session information
    if (!xarSession_setUserInfo($userId, $rememberMe)) return; // throw back

    // Set user auth module information
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $rolestable = $xartable['roles'];

    // TODO: this should be inside roles module
    $query = "UPDATE $rolestable SET xar_auth_module = ? WHERE xar_uid = ?";
    $result =& $dbconn->Execute($query,array($authModName,$userId));
    if (!$result) return;

    // Set session variables

    // Keep a reference to auth module that authenticates successfully
    xarSessionSetVar('authenticationModule', $authModName);

// TODO: re-think dynamic user capabilities of auth* modules for read/update,
//       and see how they should fit in for different scenarios (master/slave, ...)
/*
    // Sync core fields that're duplicates in users table
    if (!xarUser__syncUsersTableFields()) return;
*/

    // FIXME: <marco> here we could also set a last_logon timestamp

    // User logged in successfully, trigger the proper event with the new userid
    xarEvt_trigger('UserLogin',$userId);

    return true;
}

/**
 * Log the user out
 *
 * @access public
 * @return bool true if the user successfully logged out
 * @raise DATABASE_ERROR
 */
function xarUserLogOut()
{
    if (!xarUserIsLoggedIn()) {
        return true;
    }
    // get the current userid before logging out
    $userId = xarSessionGetVar('uid');

    // Reset user session information
    $res = xarSession_setUserInfo(_XAR_ID_UNREGISTERED, 0);
    if (!isset($res) && xarCurrentErrorType() != XAR_NO_EXCEPTION) {
        return; // throw back
    }

    xarSessionDelVar('authenticationModule');

    // User logged out successfully, trigger the proper event with the old userid
    xarEvt_trigger('UserLogout',$userId);

    return true;
}

/**
 * Check if the user logged in
 *
 * @access public
 * @return bool true if the user is logged in, false if they are not
 */

global $installing;

function xarUserIsLoggedIn()
{
    // FIXME: restore "clean" code once uid+session issues are resolved
    //return xarSessionGetVar('uid') != _XAR_ID_UNREGISTERED;
    return (xarSessionGetVar('uid') != _XAR_ID_UNREGISTERED
            && xarSessionGetVar('uid') != 0);
}

/**
 * Gets the user navigation theme name
 *
 * @author Marco Canini <marco@xaraya.com>
 */
function xarUserGetNavigationThemeName()
{
    $themeName = xarTplGetThemeName();

    if (xarUserIsLoggedIn()){
        $uid = xarUserGetVar('uid');
        $userThemeName = xarModGetUserVar('themes', 'default', $uid);
        if ($userThemeName) $themeName=$userThemeName;
    }

    return $themeName;
}

/**
 * Set the user navigation theme name
 *
 * @access public
 * @param themeName string
 */
function xarUserSetNavigationThemeName($themeName)
{
    assert('$themeName != ""');
    // uservar system takes care of dealing with anynomous
    xarModSetUserVar('themes', 'default', $themeName);
}

/**
 * Get the user navigation locale
 *
 * @access public
 * @return locale string
 */
function xarUserGetNavigationLocale()
{
    if (xarUserIsLoggedIn()) {
        $uid = xarUserGetVar('uid');
          //last resort user is falling over on this uservar by setting multiple times
         //return true for last resort user - use default locale
         if ($uid==XARUSER_LAST_RESORT) return true;

        $locale = xarModGetUserVar('roles', 'locale');
        if (!isset($locale)) {
            // CHECKME: why is this here? The logic of falling back is already in the modgetuservar
            $siteLocale = xarModGetVar('roles', 'locale');
            if (!isset($siteLocale)) {
                xarModSetVar('roles', 'locale', '');
            }
        }
        if (empty($locale)) {
            $locale = xarSessionGetVar('navigationLocale');
            if (!isset($locale)) {
                $locale = xarMLSGetSiteLocale();
            }
            xarModSetUserVar('roles', 'locale', $locale);
        } else {
            $siteLocales = xarMLSListSiteLocales();
            if (!in_array($locale, $siteLocales)) {
                // Locale not available, use the default
                $locale = xarMLSGetSiteLocale();
                xarModSetUserVar('roles', 'locale', $locale);
                xarLogMessage("WARNING: falling back to default locale: $locale in xarUserGetNavigationLocale function");
            }
        }
        xarSessionSetVar('navigationLocale', $locale);
    } else {
        $locale = xarSessionGetVar('navigationLocale');
        if (!isset($locale)) {
            // CHECKME: use dynamicdata for roles, module user variable and/or
            // session variable (see also 'timezone' in xarMLS_userOffset())
            if (xarCurrentErrorType() != XAR_NO_EXCEPTION) {
                // Here we need to return always a meaningfull result,
                // so what we can do here is only to log the exception
                // and call xarErrorFree
                // xarLogException(XARLOG_LEVEL_ERROR);
                // This will Free all exceptions, including the ones pending
                // as these are still unhandled if they are here i commented it out
                // for now, as we had lots of exceptions hiding on us (MrB)
                //xarErrorFree();
            }
            $locale = xarMLSGetSiteLocale();
            xarSessionSetVar('navigationLocale', $locale);
        }
    }
    return $locale;
}

/**
 * Set the user navigation locale
 *
 * @access public
 * @param locale string
 * @return bool true if the navigation locale is set, false if not
 */
function xarUserSetNavigationLocale($locale)
{
    if (xarMLSGetMode() != XARMLS_SINGLE_LANGUAGE_MODE) {
        xarSessionSetVar('navigationLocale', $locale);
        if (xarUserIsLoggedIn()) {
            $userLocale = xarModGetUserVar('roles', 'locale');
            if (!isset($userLocale)) {
                // CHECKME: Why is this here? the fallback logic is already in modgetuservar
                $siteLocale = xarModGetVar('roles', 'locale');
                if (!isset($siteLocale)) {
                    xarModSetVar('roles', 'locale', '');
                }
            }
            xarModSetUserVar('roles', 'locale', $locale);
        }
        return true;
    }
    return false;
}

/*
 * User variables API functions
 */

/*
 * Initialise the user object
 */
$GLOBALS['xarUser_objectRef'] = null;

/**
 * Get a user variable
 *
 * @author Marco Canini
 * @access public
 * @param name string the name of the variable
 * @param uid integer the user to get the variable for
 * @return mixed the value of the user variable if the variable exists, void if the variable doesn't exist
 * @raise BAD_PARAM, NOT_LOGGED_IN, ID_NOT_EXIST, NO_PERMISSION
 * @todo <marco> #1 figure out why this check failsall the time now: if ($userId != xarSessionGetVar('uid')) {
 * @todo <marco FIXME: ignoring unknown user variables for now...
 */
function xarUserGetVar($name, $userId = NULL)
{
    if (empty($name)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'name');
        return;
    }

    if (empty($userId)) {
        $userId = xarSessionGetVar('uid');
    }
    if ($name == 'uid') {
        return $userId;
    }
    if ($userId == _XAR_ID_UNREGISTERED) {
        // Anonymous user => only uid, name and uname allowed, for other variable names
        // an exception of type NOT_LOGGED_IN is raised
        if ($name == 'name' || $name == 'uname') {
            return xarML('Anonymous');
        }
        xarErrorSet(XAR_USER_EXCEPTION, 'NOT_LOGGED_IN');
        return;
    }

    // Don't allow any module to retrieve passwords in this way
    if ($name == 'pass') {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'name');
        return;
    }

/* TODO: #1 - some security check from the roles module needed here
    if ($userId != xarSessionGetVar('uid')) {
        // Security check
    }
*/

    if (!xarCore_IsCached('User.Variables.'.$userId, $name)) {

        if ($name == 'name' || $name == 'uname' || $name == 'email') {
            if ($userId == XARUSER_LAST_RESORT) {
                return xarML('No Information');
            }
            // retrieve the item from the roles module
            $userRole = xarModAPIFunc('roles',
                                      'user',
                                      'get',
                                       array('uid' => $userId));

            if (empty($userRole) || $userRole['uid'] != $userId) {
                $msg = xarML('User identified by uid #(1) does not exist.', $userId);
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'ID_NOT_EXIST',
                               new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                return;
            }

            xarCore_SetCached('User.Variables.'.$userId, 'uname', $userRole['uname']);
            xarCore_SetCached('User.Variables.'.$userId, 'name', $userRole['name']);
            xarCore_SetCached('User.Variables.'.$userId, 'email', $userRole['email']);

        } elseif (!xarUser__isVarDefined($name)) {
            if (xarModGetVar('roles',$name)) {
                $value = xarModGetUserVar('roles',$name,$userId);
                if ($value == null) {
                    xarCore_SetCached('User.Variables.'.$userId, $name, false);
                    // Here we can't raise an exception because they're all optional
                    if ($name != 'locale' && $name != 'timezone') {
                        // log unknown user variables to inform the site admin
                        $msg = xarML('User variable #(1) was not correctly registered', $name);
                        xarLogMessage($msg, XARLOG_LEVEL_ERROR);
                    }
                    return;
                }
                else {
                    xarCore_SetCached('User.Variables.'.$userId, $name, $value);
                }
            }

        } else {
            // retrieve the user item
            $itemid = $GLOBALS['xarUser_objectRef']->getItem(array('itemid' => $userId));
            if (empty($itemid) || $itemid != $userId) {
                $msg = xarML('User identified by uid #(1) does not exist.', $userId);
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'ID_NOT_EXIST',
                               new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
                return;
            }

            // save the properties
            $properties =& $GLOBALS['xarUser_objectRef']->getProperties();
            foreach (array_keys($properties) as $key) {
                if (isset($properties[$key]->value)) {
                    xarCore_SetCached('User.Variables.'.$userId, $key, $properties[$key]->value);
                }
            }
        }

/*
    // TODO: re-think dynamic user capabilities of auth* modules for read/update,
    //       and see how they should fit in for different scenarios (master/slave, ...)

        $authModName = xarUser__getAuthModule($userId);
        if (!isset($authModName)) return; // throw back
        //$useAuthSystem = false; // Used for debug
        $useAuthSystem = ($authModName == 'authsystem') ? true : false;

        if (!$useAuthSystem) {
            $res = xarModAPIFunc($authModName, 'user', 'has_capability',
                                 array('capability' => XARUSER_AUTH_DYNAMIC_USER_DATA_HANDLER));
            if (!isset($res)) return; // throw back
            if ($res) {
                // $authModName supports the UserDataHandler interface
                $res = xarModAPIFunc($authModName, 'user', 'is_valid_variable',
                                     array('name' => $name));
                if (!isset($res)) return; // throw back
                if ($res == false) $useAuthSystem = true; // $name variable is retrieved from authsystem module
            } else {
                // $name variable is retrieved from authsystem module
                $useAuthSystem = true;
            }
        }

        if ($useAuthSystem == true) {
            $authModName = 'authsystem';
            if (!xarModAPILoad($authModName, 'user')) return; // throw back
        }

        $value = xarModAPIFunc($authModName, 'user', 'get_user_variable',
                               array('uid' => $userId,
                                     'name' => $name,
                                     'prop_id' => $prop_id,
                                     'prop_dtype' => $prop_dtype));
        if (!isset($value)) return; // throw back
        if ($value === false) {
            if (isset($prop_default)) $value = $prop_default; // Use the metainfo default value if any
            // else
            // Variable doesn't exist
            // false is here a special value to denote that variable was searched
            // but wasn't found so xarUserGetVar'll return void
            // will be called: xarCore_SetCached('User.Variables.'.$userId, $name, false)
        } else {
            switch ($prop_dtype) {
                case XARUSER_DUD_TYPE_DOUBLE:
                    $value = (float) $value;
                    break;
                case XARUSER_DUD_TYPE_INTEGER:
                    $value = (int) $value;
                    break;
            }
        }

        xarCore_SetCached('User.Variables.'.$userId, $name, $value);
*/
    }

    if (!xarCore_IsCached('User.Variables.'.$userId, $name)) {
        return false; //failure
    }

    $cachedValue = xarCore_GetCached('User.Variables.'.$userId, $name);
    if ($cachedValue === false) {
        // Variable already searched but doesn't exist and has no default
        return;
    }

    return $cachedValue;
}

/**
 * Set a user variable
 *
 * @author Marco Canini
 * @since 1.23 - 2002/02/01
 * @access public
 * @param name string the name of the variable
 * @param value ??? the value of the variable
 * @param userId integer user's ID
 * @return bool true if the set was successful, false if validation fails
 * @raise BAD_PARAM, NOT_LOGGED_IN, ID_NOT_EXIST, NO_PERMISSION
 */
function xarUserSetVar($name, $value, $userId = NULL)
{
    // check that $name is valid
    if (empty($name)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'name');
        return;
    }
    if ($name == 'uid' || $name == 'authenticationModule' || $name == 'pass') {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'name');
        return;
    }

    if (empty($userId)) {
        $userId = xarSessionGetVar('uid');
    }
    if ($userId == _XAR_ID_UNREGISTERED) {
        // Anonymous user
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'NOT_LOGGED_IN');
    }

/* TODO: #1 - some security check from the roles module needed here
    if ($userId != xarSessionGetVar('uid')) {
        // Security check
    }
*/

    if ($name == 'name' || $name == 'uname' || $name == 'email') {
    // TODO: replace with some roles API
        xarUser__setUsersTableUserVar($name, $value, $userId);

    } elseif (!xarUser__isVarDefined($name)) {
		if (xarModGetVar('roles',$name)) {
            xarCore_SetCached('User.Variables.'.$userId, $name, false);
            $msg = xarML('User variable #(1) was not correctly registered', $name);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'VARIABLE_NOT_REGISTERED',
                           new SystemException($msg));
            return;
        } else {
            xarModSetUserVar('roles',$name,$value,$userId);
        }
    } else {
        // retrieve the user item
        $itemid = $GLOBALS['xarUser_objectRef']->getItem(array('itemid' => $userId));
        if (empty($itemid) || $itemid != $userId) {
            $msg = xarML('User identified by uid #(1) does not exist.', $userId);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'ID_NOT_EXIST',
                           new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return;
        }

        // check if we need to update the item
        if ($value != $GLOBALS['xarUser_objectRef']->properties[$name]->value) {
            // validate the new value
            if (!$GLOBALS['xarUser_objectRef']->properties[$name]->validateValue($value)) {
                return false;
            }
            // update the item
            $itemid = $GLOBALS['xarUser_objectRef']->updateItem(array($name => $value));
            if (!isset($itemid)) return; // throw back
        }

    }

/*
    // TODO: re-think dynamic user capabilities of auth* modules for read/update,
    //       and see how they should fit in for different scenarios (master/slave, ...)

    if ($prop_dtype == XARUSER_DUD_TYPE_CORE) {
        // Keep in sync core fields
        if (!xarUser__setUsersTableUserVar($name, $value, $userId)) return;
    }

    $authModName = xarUser__getAuthModule($userId);
    if (!isset($authModName)) return; // throw back

    //$useAuthSystem = false; // Used for debug
    $useAuthSystem = ($authModName == 'authsystem') ? true : false;

    if (!$useAuthSystem) {
        $res = xarModAPIFunc($authModName, 'user', 'has_capability',
                             array('capability' => XARUSER_AUTH_DYNAMIC_USER_DATA_HANDLER));
        if (!isset($res)) return; // throw back
        if ($res) {
            // $authModName supports the UserDataHandler interface
            $res = xarModAPIFunc($authModName, 'user', 'is_valid_variable', array('name' => $name));
            if (!isset($res)) return; // throw back
            if ($res == false) $useAuthSystem = true; // $name variable is handled by authsystem module
        } else {
            // $name variable is retrieved from authsystem module
            $useAuthSystem = true;
        }
    }

    if ($useAuthSystem == true) {
        if ($prop_dtype == XARUSER_DUD_TYPE_CORE) return true; // Already updated
        $authModName = 'authsystem';
        if (!xarModAPILoad($authModName, 'user')) return; // throw back
    }

    if (!xarModAPIFunc($authModName, 'user', 'set_user_variable',
                       array('uid' => $userId,
                             'name' => $name,
                             'value' => $value,
                             'prop_id' => $prop_id,
                             'prop_dtype' => $prop_dtype))) {
        assert('xarCurrentErrorType() != XAR_NO_EXCEPTION');
        return;
    }

*/

    // Keep in sync the UserVariables cache
    xarCore_SetCached('User.Variables.'.$userId, $name, $value);

    return true;
}

/**
 * Compare Passwords
 *
 * @access public
 * @param givenPassword string
 * @return bool true if the passwords match, false otherwise
 */
function xarUserComparePasswords($givenPassword, $realPassword, $userName, $cryptSalt = '')
{
    // TODO: consider moving to something stronger like sha1
    $md5pass = md5($givenPassword);
    if (strcmp($md5pass, $realPassword) == 0)
        return $md5pass;

    return false;
}

// PROTECTED FUNCTIONS

// PRIVATE FUNCTIONS

/**
 * Get user's authentication module
 *
 * @access private
 * @param userId string
 * @raise UNKNOWN, DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST
 * @todo FIXME: what happens for anonymous users ???
 * @todo check coherence 1 vs. 0 for Anonymous users !!!
 */
function xarUser__getAuthModule($userId)
{
    if ($userId == xarSessionGetVar('uid')) {
        $authModName = xarSessionGetVar('authenticationModule');
        if (isset($authModName)) {
            return $authModName;
        }
    }

    // TODO: replace with some roles API 

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    // Get user auth_module name
    $rolestable = $xartable['roles'];

    $query = "SELECT xar_auth_module FROM $rolestable WHERE xar_uid = ?";
    $result =& $dbconn->Execute($query,array($userId));
    if (!$result) return;

    if ($result->EOF) {
        // That user has never logon, strange, don't you think?
        // However fallback to authsystem
        $authModName = 'authsystem';
    } else {
        list($authModName) = $result->fields;
        // TODO: remove when issue of Anonymous users is resolved
        // Q: what issue?
        if (empty($authModName)) {
            $authModName = 'authsystem';
        }
    }
    $result->Close();

    if (!xarModAPILoad($authModName, 'user')) return;

    return $authModName;
}

/*
 * @access private
 * @return bool true if the variable is defined
 */
function xarUser__isVarDefined($name)
{
    // Retrieve the dynamic user object if necessary
    if (!isset($GLOBALS['xarUser_objectRef']) && xarModIsHooked('dynamicdata','roles')) {
        $GLOBALS['xarUser_objectRef'] = xarModAPIFunc('dynamicdata', 'user', 'getobject',
                                                       array('module' => 'roles'));
        if (empty($GLOBALS['xarUser_objectRef']) || empty($GLOBALS['xarUser_objectRef']->objectid)) {
            $GLOBALS['xarUser_objectRef'] = false;
        }
    }

    // Check if this property is defined for the dynamic user object
    if (empty($GLOBALS['xarUser_objectRef']) || empty($GLOBALS['xarUser_objectRef']->properties[$name])) {
        return false;
    }

    return true;
}

/*
 * @access private
 * @return bool
 * @raise NOT_LOGGED_IN, UNKNOWN, DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST
 */
function xarUser__syncUsersTableFields()
{
    $userId = xarSessionGetVar('uid');
    assert('$userId != _XAR_ID_UNREGISTERED');

// TODO: configurable one- or two-way re-synchronisation of core + dynamic fields ?

    $authModName = xarUser__getAuthModule($userId);
    if (!isset($authModName)) return; // throw back
    if ($authModName == 'authsystem') return true; // Already synced

    $res = xarModAPIFunc($authModName, 'user', 'has_capability',
                         array('capability' => XARUSER_AUTH_DYNAMIC_USER_DATA_HANDLER));
    if (!isset($res)) return; // throw back
    if ($res == false) return true; // Impossible to go out of sync

// TODO: improve multi-update operations

    $name = xarUserGetVar('name');
    if (!isset($name)) return; // throw back
    $res = xarUser__setUsersTableUserVar('name', $name, $userId);
    if (!isset($res)) return; // throw back
    $uname = xarUserGetVar('uname');
    if (!isset($uname)) return; // throw back
    $res = xarUser__setUsersTableUserVar('uname', $uname, $userId);
    if (!isset($res)) return; // throw back
    $email = xarUserGetVar('email');
    if (!isset($email)) return; // throw back
    $res = xarUser__setUsersTableUserVar('email', $email, $userId);
    if (!isset($res)) return; // throw back

    return true;
}

/*
 * @access private
 * @return bool
 * @raise DATABASE_ERROR
 */
function xarUser__setUsersTableUserVar($name, $value, $userId)
{

// TODO: replace with some roles API ?

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $rolestable = $xartable['roles'];
    $usercolumns = $xartable['users_column'];

    // The $name variable will be used to get the appropriate column
    // from the users table.
    $query = "UPDATE $rolestable
              SET $usercolumns[$name] = ? WHERE xar_uid = ?";
    $result =& $dbconn->Execute($query,array($value,$userId));
    if (!$result) return;
    return true;
}
?>
