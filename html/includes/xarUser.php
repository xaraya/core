<?php
/**
 * File: $Id: s.xarUser.php 1.95 03/01/21 04:15:07+01:00 marcel@hsdev.com $
 *
 * User System
 *
 * @package user
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.org
 * @author Jim McDonald
 * @todo <marco> user status field
 * @todo <johnny> look over xarUserComparePasswords
 */

/**
 * Dynamic User Data types for User Properties
 */
define('XARUSER_DUD_TYPE_CORE', 0); // indicates a core field
define('XARUSER_DUD_TYPE_STRING', 1);
define('XARUSER_DUD_TYPE_TEXT', 2);
define('XARUSER_DUD_TYPE_DOUBLE', 4);
define('XARUSER_DUD_TYPE_INTEGER', 8);

/**
 * Authentication modules capabilities
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

/**
 * Initialise the User System
 *
 * @access protected
 * @global xarUser_authentication modules array
 * @param args[authenticationModules] array
 * @return bool true on success
 */
function xarUser_init($args, $whatElseIsGoingLoaded)
{
    // User System and Security Service Tables
    $systemPrefix = xarDBGetSystemTablePrefix();

    $tables = array('roles'            => $systemPrefix . '_roles',
                    'user_data'        => $systemPrefix . '_user_data',
                    'user_property'    => $systemPrefix . '_user_property',
                    'realms'           => $systemPrefix . '_realms',
                    'rolemembers' => $systemPrefix . '_rolemembers');

    xarDB_importTables($tables);

    $GLOBALS['xarUser_authenticationModules'] = $args['authenticationModules'];

    xarMLS_setCurrentLocale(xarUserGetNavigationLocale());
    xarTplSetThemeName(xarUserGetNavigationThemeName());

    return true;
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
function xarUserLogIn($userName, $password, $rememberMe)
{
    if (xarUserIsLoggedIn()) {
        return true;
    }
    if (empty($userName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'userName');
        return;
    }
    if (empty($password)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'password');
        return;
    }

    $userId = XARUSER_AUTH_FAILED;
    $args = array('uname' => $userName, 'pass' => $password);
    foreach($GLOBALS['xarUser_authenticationModules'] as $authModName) {
        // Every authentication module must at least implement the
        // Authentication interface so there's at least the authenticate_user
        // user api function
        if (!xarModAPILoad($authModName, 'user')) return; // throw back

        $userId = xarModAPIFunc($authModName, 'user', 'authenticate_user', $args);
        if (!isset($userId)) return; // throw back
        elseif ($userId != XARUSER_AUTH_FAILED) break; // Someone authenticated us
        // if here $userId is XARUSER_AUTH_FAILED, try with next auth module
    }
    if ($userId == XARUSER_AUTH_FAILED) return false;

    // Catch common variations (0, false, '', ...)
    if (empty($rememberMe)) $rememberMe = 0;
    else $rememberMe = 1;

    // Set user session information
    if (!xarSession_setUserInfo($userId, $rememberMe)) return; // throw back

    // Set user auth module information
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $rolestable = $xartable['roles'];

    $query = "UPDATE $rolestable
              SET xar_auth_module = '" . xarVarPrepForStore($authModName) . "'
              WHERE xar_pid = " . xarVarPrepForStore($userId);
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Set session variables

    // Keep a reference to auth module that authenticates successfully
    xarSessionSetVar('authenticationModule', $authModName);

    // Sync core fields that're duplicates in users table
    if (!xarUser__syncUsersTableFields()) return;

    // FIXME: <marco> here we could also set a last_logon timestamp

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
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    // Reset user session information
    $res = xarSession_setUserInfo(0, 0);
    if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
        return; // throw back
    }

    xarSessionDelVar('authenticationModule');

    return true;
}

/**
 * Check if the user logged in
 *
 * @access public
 * @return bool true if the user is logged in, false if they are not
 */
function xarUserIsLoggedIn()
{
    return xarSessionGetVar('uid') != 0;
}

/**
 * Gets the user navigation theme name
 *
 * @author Marco Canini <m.canini@libero.it>
 */
function xarUserGetNavigationThemeName()
{
    $themeName = xarSessionGetVar('navigationThemeName');
    if (!isset($themeName)) {
        if (xarUserIsLoggedIn()) {
            $themeName = xarUserGetVar('ThemeName');
        }
        if (!isset($themeName)) {
            if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
                // Here we can't raise an exception
                // so what we can do here is only to log the exception
                // and call xarExceptionFree
                xarLogException(XARLOG_LEVEL_ERROR);
                xarExceptionFree();
                return;
            }
            $themeName = xarTplGetThemeName();
        }

        //Hack to make the install work...
        //Marco: can you fix this later? Thanks...
        //if ($themeName != 'installer') {
            xarSessionSetVar('navigationThemeName', $themeName);
        //}
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
    xarSessionSetVar('navigationThemeName', $themeName);
}

/**
 * Get the user navigation locale
 *
 * @access public
 * @return locale string
 */
function xarUserGetNavigationLocale()
{
    $locale = xarSessionGetVar('navigationLocale');
    if (!isset($locale)) {
        if (xarUserIsLoggedIn()) {
            $locale = xarUserGetVar('locale');
        }
        if (!isset($locale)) {
            if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
                // Here we need to return always a meaningfull result,
                // so what we can do here is only to log the exception
                // and call xarExceptionFree
                xarLogException(XARLOG_LEVEL_ERROR);
                xarExceptionFree();
            }
            $locale = xarMLSGetSiteLocale();
        }
        xarSessionSetVar('navigationLocale', $locale);
    }
    return $locale;
}

/**
 * Set the user navigation locale
 *
 * @access public
 * @param locale string
 */
function xarUserSetNavigationLocale($locale)
{
    $mode = xarMLSGetMode();
    if ($mode == XARMLS_BOXED_MULTI_LANGUAGE_MODE) {
        xarSessionSetVar('navigationLocale', $locale);
    }
}

/*
 * User variables API functions
 */

/**
 * Get a user variable
 *
 * @author Marco Canini
 * @access public
 * @param name string the name of the variable
 * @param uid integer the user to get the variable for
 * @return mixed the value of the user variable if the variable exists, void if the variable doesn't exist
 * @raise BAD_PARAM, NOT_LOGGED_IN, ID_NOT_EXIST, NO_PERMISSION, UNKNOWN, DATABASE_ERROR, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_FUNCTION_NOT_EXIST, VARIABLE_NOT_REGISTERED
 * @todo <marco> #1 figure out why this check failsall the time now: if ($userId != xarSessionGetVar('uid')) {
 * @todo <marco FIXME: ignoring unknown user variables for now...
 */
function xarUserGetVar($name, $userId = NULL)
{
    if (empty($name)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'name');
        return;
    }

    if (empty($userId)) {
        $userId = xarSessionGetVar('uid');
    }
    if ($name == 'uid') {
        // User id for Anonymous is NULL, so we check later for this
        return $userId;
    }
    if ($userId == 0) {
        // Anonymous user => only uid, name and uname allowed, for other variable names
        // an exception of type NOT_LOGGED_IN is raised
        if ($name == 'name' || $name == 'uname') {
            return xarMLByKey('ANONYMOUS');
        }
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NOT_LOGGED_IN');
        return;
    }

/* TODO: #1
    if ($userId != xarSessionGetVar('uid')) {
        // Security check
        // Here we use a trick
        // One user can make private some of its data by creating a permission with ACCESS_NONE as level
        // The trick is that this permission is applied to other users
        if (!xarSecAuthAction(0, 'users::Variables', "$name::", ACCESS_READ, $userId)) {
            if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
                return; // throw back
            }
            $msg = xarML('No permission to get value of #(1) user variable for uid #(2).', $name, $userId);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                           new SystemException($msg));
            return;
        }
    }
*/

    if (!xarVarIsCached('User.Variables.'.$userId, $name)) {
        // check that $name variable appears in the dynamic user data fields
        $infos = xarUser__getUserVarInfo($name);
        if (!isset($infos)) {
        // TODO: #2
            if (xarExceptionMajor() != XAR_NO_EXCEPTION &&
                xarExceptionId() == 'VARIABLE_NOT_REGISTERED') {
                xarVarSetCached('User.Variables.'.$userId, $name, false);
                // Here we can't raise an exception
                // so what we can do here is only to log the exception
                // and call xarExceptionFree
                xarLogException(XARLOG_LEVEL_ERROR);
                xarExceptionFree();
            }
            // Of sure got an exception
            return; // throw back
        }
        extract($infos); // $prop_id, $prop_dtype, $prop_default, $prop_validation

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
            // will be called: xarVarSetCached('User.Variables.'.$userId, $name, false)
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

        xarVarSetCached('User.Variables.'.$userId, $name, $value);
    }

    if (!xarVarIsCached('User.Variables.'.$userId, $name)) {
        return false; //failure
    }

    $cachedValue = xarVarGetCached('User.Variables.'.$userId, $name);
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
 * @raise BAD_PARAM, NOT_LOGGED_IN, ID_NOT_EXIST, NO_PERMISSION, UNKNOWN, DATABASE_ERROR, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_FUNCTION_NOT_EXIST, VARIABLE_NOT_REGISTERED
 */
function xarUserSetVar($name, $value, $userId = NULL)
{
    // check that $name is valid
    if (empty($name)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'name');
        return;
    }
    if ($name == 'uid' || $name == 'authenticationModule') {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'name');
        return;
    }

    if (empty($userId)) {
        $userId = xarSessionGetVar('uid');
    }
    if ($userId == 0) {
        // Anonymous user
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NOT_LOGGED_IN');
    }
    /*
    Disabled for now!
    if ($userId != xarSessionGetVar('uid')) {
        // If you want to set a variable owned by another user
        // you must have ACCESS_EDIT permission
        // Security check
        if (!xarSecAuthAction(0, 'users::Variables', "$name::", ACCESS_EDIT)) {
            if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
                return; // throw back
            }
            $msg = xarML('No permission to set value of #(1) user variable for uid #(2).', $name, $userId);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                           new SystemException($msg));
            return;
        }
    }
    */


    // check that $name variable appears in the dynamic user data fields
    $infos = xarUser__getUserVarInfo($name);
    if (!isset($infos)) return; // throw back

    extract($infos); // $prop_id, $prop_dtype, $prop_default, $prop_validation

    // FIXME: <marco> Do we want this?
    /*
    if ($value == $prop_default) {
        // Avoid duplicating default value into database
        return true;
    }
    */

    if (!xarUserValidateVar($name, $value)) {
        if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
            return; // throw back
        }
        // Validation failed
        return false;
    }

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
        assert('xarExceptionMajor() != XAR_NO_EXCEPTION');
        return;
    }

    // Keep in sync the UserVariables cache
    xarVarSetCached('User.Variables.'.$userId, $name, $value);

    return true;
}

/**
 * Validate a user variable
 *
 * @since 1.60 - 2002/05/02
 * @author Marco Canini
 * @param name user variable name
 * @param value value to be validated
 * @return bool true when validation was successfully accomplished, false otherwise
 * @raise ID_NOT_EXIST, DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_FUNCTION_NOT_EXIST, UNKNOWN
 */
function xarUserValidateVar($name, $value)
{
    if (xarVarIsCached('User.Variables.Validated', $name) &&
        xarVarGetCached('User.Variables.Validated', $name) == $value) {
        return true;
    }

    // check that $name variable appears in the dynamic user data fields
    $infos = xarUser__getUserVarInfo($name);
    if (!isset($infos)) {
        // Of sure got an exception
        return; // throw back
    }
    extract($infos); // $prop_id, $prop_dtype, $prop_default, $prop_validation

    if (isset($prop_validation)) {
        switch ($prop_dtype) {
            case XARUSER_DUD_TYPE_DOUBLE:
                $value = (float) $value;
                break;
            case XARUSER_DUD_TYPE_INTEGER:
                $value = (int) $value;
                break;
        }

        // Do validation
        $res = xarUser__validationApply($prop_validation, $value);
        if (!isset($res)) return; // throw back
        if (!$res) return false; // Validation failed
    }

    xarVarSetCached('User.Variables.Validated', $name, $value);

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
    $compare2crypt = true;
    $compare2text = true;

    // FIXME: <marco> What's this for?
    $system = xarConfigGetVar('system');

    $md5pass = md5($givenPassword);
    if (strcmp($md5pass, $realPassword) == 0)
        return $md5pass;
    elseif ($compare2crypt && $system != '1' ){
        $crypted = false;
        if (strcmp(crypt($givenPassword, $cryptSalt), $realPassword) == 0) {
            $crypted = true;
        }
        if ($crypted){
            updateUserPass($userName, $md5pass);
            return $md5pass;
        }
    } elseif ($compare2text && strcmp($givenPassword, $realPassword) == 0) {
             updateUserPass($userName, $md5pass);
             return $md5pass;
    }

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
        assert('isset($authModName)');
    } else {
        list($dbconn) = xarDBGetConn();
        $xartable = xarDBGetTables();

        // Get user auth_module name
        $rolestable = $xartable['roles'];

        $query = "SELECT xar_auth_module
                  FROM $rolestable
                  WHERE xar_pid = '" . xarVarPrepForStore($userId) . "'";
        $result =& $dbconn->Execute($query);
        if (!$result) return;

        if ($result->EOF) {
            // That user has never logon, strange, don't you think?
            // However fallback to authsystem
            $authModName = 'authsystem';
        } else {
            list($authModName) = $result->fields;
            // TODO: remove when issue of Anonymous users is resolved
            if (empty($authModName)) {
                $authModName = 'authsystem';
            }
        }
        $result->Close();
    }
    if (!xarModAPILoad($authModName, 'user')) return;

    return $authModName;
}

/*
 * @access private
 * @raise DATABASE_ERROR, ID_NOT_EXIST
 */
function xarUser__getUserVarInfo($name)
{
    $xartable = xarDBGetTables();

    $rolestable = $xartable['roles'];

    // Core fields aren't handled with Dynamic User Data
    if ($name == 'name' || $name == 'uname' ||
        $name == 'email' || $name == 'url') {
        // You're asking metainfo for a core field
        // We can safely return a default value
        // prop_id = 0 means variable won't go in user_data table
        return array('prop_id' => 0, 'prop_dtype' => XARUSER_DUD_TYPE_CORE);
    }

    if (!xarVarIsCached('User.Variables.Info', $name)) {
        xarVarSetCached('User.Variables.Info', $name, false); // If at the end of operations it still
                                                             // be false we're sure that the property
                                                             // was searched but not found

        list($dbconn) = xarDBGetConn();

        $propertiestable = $xartable['user_property'];

        if (($ind = strpos($name, '_')) > 0) {
            // Here we do a pre-caching
            $name_prefix = substr($name, 0, $ind + 1);
            // Select all user vars that begins with $name_prefix
            $query = "SELECT xar_prop_id,
                             xar_prop_label,
                             xar_prop_dtype,
                             xar_prop_default,
                             xar_prop_validation
                             FROM $propertiestable
                             WHERE xar_prop_label LIKE '" . xarVarPrepForStore($name_prefix) ."%%'";
            $result =& $dbconn->Execute($query);
            if (!$result) return;

            while (!$result->EOF) {
                list($prop_id, $prop_label, $prop_dtype, $prop_default, $prop_validation) = $result->fields;

                $info['prop_id'] = (int) $prop_id;
                $info['prop_dtype'] = (int) $prop_dtype;
                if (isset($prop_default)) {
                    switch($info['prop_dtype']) {
                        case XARUSER_DUD_TYPE_STRING:
                        case XARUSER_DUD_TYPE_TEXT:
                            $info['prop_default'] = $prop_default;
                            break;
                        case XARUSER_DUD_TYPE_DOUBLE:
                            $info['prop_default'] = (float) $prop_default;
                            break;
                        case XARUSER_DUD_TYPE_INTEGER:
                            $info['prop_default'] = (int) $prop_default;
                            break;
                    }
                }
                $info['prop_validation'] = $prop_validation;
                // Cache info
                xarVarSetCached('User.Variables.Info', $prop_label, $info);

                $result->MoveNext();
            }

            $result->Close();
        } else {
            // Confirm that this is a known value
            $query = "SELECT xar_prop_id,
                      xar_prop_dtype,
                      xar_prop_default,
                      xar_prop_validation
                      FROM $propertiestable
                      WHERE xar_prop_label = '" . xarVarPrepForStore($name) ."'";
            $result =& $dbconn->Execute($query);
            if (!$result) return;

            if (!$result->EOF) {
                list($prop_id, $prop_dtype, $prop_default, $prop_validation) = $result->fields;

                $info['prop_id'] = (int) $prop_id;
                $info['prop_dtype'] = (int) $prop_dtype;
                if (isset($prop_default)) {
                    switch($info['prop_dtype']) {
                        case XARUSER_DUD_TYPE_STRING:
                        case XARUSER_DUD_TYPE_TEXT:
                        $info['prop_default'] = $prop_default;
                        break;
                        case XARUSER_DUD_TYPE_DOUBLE:
                        $info['prop_default'] = (float) $prop_default;
                        break;
                        case XARUSER_DUD_TYPE_INTEGER:
                        $info['prop_default'] = (int) $prop_default;
                        break;
                    }
                }
                $info['prop_validation'] = $prop_validation;
                // Cache info
                xarVarSetCached('User.Variables.Info', $name, $info);

                $result->Close();
            }
        }
    }

    $info = xarVarGetCached('User.Variables.Info', $name);
    if ($info == false) {
        $msg = xarML('Metadata for user variable #(1) are not correctly registered in database.', $name);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'VARIABLE_NOT_REGISTERED',
                       new SystemException($msg));
        return;
    }
    return $info;
}

/*
 * @access private
 * @returns bool
 * @return true
 * @raise NOT_LOGGED_IN, UNKNOWN, DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST
 */
function xarUser__syncUsersTableFields()
{
    $userId = xarSessionGetVar('uid');
    assert('$userId != 0');

    $authModName = xarUser__getAuthModule($userId);
    if (!isset($authModName)) return; // throw back
    if ($authModName == 'authsystem') return true; // Already synced

    $res = xarModAPIFunc($authModName, 'user', 'has_capability',
                         array('capability' => XARUSER_AUTH_DYNAMIC_USER_DATA_HANDLER));
    if (!isset($res)) return; // throw back
    if ($res == false) return true; // Impossible to go out of sync

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
 * @returns bool
 * @return true
 * @raise DATABASE_ERROR
 */
function xarUser__setUsersTableUserVar($name, $value, $userId)
{
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $rolestable = $xartable['roles'];

    $query = "UPDATE $rolestable
              SET xar_name = '" . xarVarPrepForStore($value) . "'
              WHERE xar_pid = " . xarVarPrepForStore($userId);
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    return true;
}

/**
 * Validation stuff
 */


// validation_string := validator_list
// validator_list := validator | validator + '&' + validator_list
// validator := ['!' +] type + ':' + operator + ':' + param

/*
 * type can be one of these values:    num
 *                                     string
 *                                     stringlen
 *                                     func
 * operator is type-sensitive
 * valid operators for num type are:      ==, !=, <, >, <=, >=
 * valid operators for string type are:   is, contains, starts, ends, regex
 * valid operators for stringlen are the same as num type.
 * there's only one valid operator for func type: it's a string composed from Modname + ',' + Funcname
 */

/**
 * @access private
 * @raise DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_FUNCTION_NOT_EXIST, UNKNOWN
 */
function xarUser__validationApply($validation, $valueToCheck)
{
    // TODO: set a ML errmsg on failure

    // Syntax trees of parsed validation strings are cached
    if (!xarVarIsCached('User.Variables.Validators', $validation)) {
        $val_stack = xarUser__validationParse($validation);
        if (!isset($val_stack)) {
            return;
        }
        xarVarSetCached('User.Variables.Validators', $validation, $val_stack);
    }
    $val_stack = xarVarGetCached('User.Variables.Validators', $validation);

    foreach($val_stack as $val_entry) {
        $res = false;
        // Easy trick to use less code
        if ($val_entry->type == 'stringlen') {
            $val_entry->type = 'num';
            $value = strlen($valueToCheck);
        } else {
            $value = $valueToCheck;
        }

        if ($val_entry->type == 'num') {
            if (!is_numeric($value)) {
                $value = (strpos($value, '.') !== false) ? (float) $val_entry->param : (int) $val_entry->param;
            }
            $param = (strpos($val_entry->param, '.') !== false) ? (float) $val_entry->param : (int) $val_entry->param;
            if ($val_entry->operator == '==') {
                $res = ($value == $param) ? true : false;
            } elseif ($val_entry->operator == '!=') {
                $res = ($value != $param) ? true : false;
            } elseif ($val_entry->operator == '<') {
                $res = ($value < $param) ? true : false;
            } elseif ($val_entry->operator == '>') {
                $res = ($value > $param) ? true : false;
            } elseif ($val_entry->operator == '<=') {
                $res = ($value <= $param) ? true : false;
            } elseif ($val_entry->operator == '>=') {
                $res = ($value >= $param) ? true : false;
            } else {
                $msg = xarML('Invalid operator \'#(1)\' for type \'num\'.', $val_entry->operator);
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
                       new SystemException($msg));
                return;
            }
        } elseif ($val_entry->type == 'string') {
            $param = $val_entry->param;
            if ($val_entry->operator == 'is') {
                $res = (strcmp($value, $param) == 0) ? true : false;
            } elseif ($val_entry->operator == 'contains') {
                $res = (strpos($value, $param) !== false) ? true : false;
            } elseif ($val_entry->operator == 'starts') {
                if (preg_match("/^$param/", $value)) {
                    $res = true;
                }
            } elseif ($val_entry->operator == 'ends') {
                if (preg_match("/$param\$/", $value)) {
                    $res = true;
                }
            } elseif ($val_entry->operator == 'like') {
                // FIXME: How to implement this?
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NOT_IMPLEMENTED',
                               new SystemException(__FILE__.'('.__LINE__."): Operator like isn't implemented."));
                return;
            } elseif ($val_entry->operator == 'regex') {
                if (substr($param, 0, 1) != '/') {
                    $param = "/$param/";
                }
                if (preg_match($param, $value)) {
                    $res = true;
                }
            } else {
                $msg = xarML('Invalid operator \'#(1)\' for type \'string\'.', $val_entry->operator);
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
                       new SystemException($msg));
                return;
            }
        } elseif ($val_entry->type == 'func') {
            list($modname, $funcname) = explode(',', $val_entry->param);

            // Load module
            $res = xarModAPILoad($modname, 'user');
            if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
                return; // throw back
            }

            $args = array('value' => $value);
            if ($val_entry->param != 'none') {
                $args['param'] = $val_entry->param;
            }

            // Call module API function
            $res = xarModAPIFunc($modname, 'user', $funcname, $args);
            if (!isset($res) && xarExceptionMajor() != XAR_NO_EXCEPTION) {
                return; // throw back
            }
        } else {
            $msg = xarML('Invalid type \'#(1)\'.', $val_entry->type);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
                           new SystemException($msg));
            return;
        }

        // Negation
        if ($val_entry->negation == true) {
            $res = !$res;
        }
        // If at least one check fails return false
        if ($res == false) {
            return false;
        }
    }
    return true;
}

// Simple data structure used by validation stuff
/**
 *
 *
 * @package user
 */
class xarUser__ValEntry
{
    var $negation = false;
    var $type;
    var $operator;
    var $param;
}

/**
 * @access private
 */
function xarUser__validationExplodeEsc($delimiter, $str)
{
    $ind = strpos($str, "\\$delimiter");
    if ($ind === false) {
        return explode($delimiter, $str);
    }
    $chunks = array();
    $ind = strpos($str, $delimiter);
    if ($ind === false) {
        return $str;
    }
    $last_ind = -1;
    while ($ind !== false) {
        if ($ind > 0 && substr($str, $ind - 1, 1) != "\\") {
            $chunk = substr($str, $last_ind + 1, $ind - $last_ind - 1);
            $chunk = str_replace("\\$delimiter", $delimiter, $chunk);
            $chunks[] = $chunk;
            $last_ind = $ind;
        }
        $ind = strpos($str, $delimiter, $ind + 1);
    }
    $chunk = substr($str, $last_ind + 1);
    $chunk = str_replace("\\$delimiter", $delimiter, $chunk);
    $chunks[] = $chunk;

    return $chunks;
}

/**
 * @access private
 * @returns array
 * @return validation string parsed tree or void on failure
 * @raise UNKNOWN
 */
function xarUser__validationParse($validationString)
{
    $val_stack = array();

    $validator_list = xarUser__validationExplodeEsc('&', $validationString);

    foreach($validator_list as $validator_string) {
        $val_entry = new xarUser__ValEntry();

        $validator = xarUser__validationExplodeEsc(':', $validator_string);

        if (count($validator) != 3) {
            $msg = xarML('Parse failed for validation string: \'#(1)\'.', $validationString);
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN',
                           new SystemException($msg));
            return;
        }
        if (substr($validator[0], 0, 1) == '!') {
            // Negation
            $val_entry->negation = true;
            $val_entry->type = substr($validator[0], 1);
        } else {
            $val_entry->type = $validator[0];
        }
        $val_entry->operator = $validator[1];
        $val_entry->param = $validator[2];
        // Push the new entry into the stack
        $val_stack[] = $val_entry;
    }
    return $val_stack;
}
?>
