<?php
// File: $Id$
// ----------------------------------------------------------------------
// PostNuke Content Management System
// Copyright (C) 2001 by the PostNuke Development Team.
// http://www.postnuke.com/
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// ----------------------------------------------------------------------
// Original Author of file: Jim McDonald
// Purpose of file: User System
// ----------------------------------------------------------------------

// TODO: <marco> user status field

/*
 *
 * Defines
 *
 */

/*
 * Dynamic User Data types for User Properties
 */

define('PNUSER_DUD_TYPE_CORE', 0); // indicates a core field
define('PNUSER_DUD_TYPE_STRING', 1);
define('PNUSER_DUD_TYPE_TEXT', 2);
define('PNUSER_DUD_TYPE_DOUBLE', 4);
define('PNUSER_DUD_TYPE_INTEGER', 8);

/*
 * Authentication modules capabilities
 */
define('PNUSER_AUTH_AUTHENTICATION', 1);
define('PNUSER_AUTH_DYNAMIC_USER_DATA_HANDLER', 2);
define('PNUSER_AUTH_PERMISSIONS_OVERRIDER', 16);
define('PNUSER_AUTH_USER_CREATEABLE', 32);
define('PNUSER_AUTH_USER_DELETEABLE', 64);
define('PNUSER_AUTH_USER_ENUMERABLE', 128);

/*
 * Error codes
 */
define('PNUSER_AUTH_FAILED', -1);

/**
 * Initialise the User System
 * @returns bool
 * @return true on success
 */
function pnUser_init($args)
{
    global $pnUser_authenticationModules;

    $pnUser_authenticationModules = $args['authenticationModules'];

    pnMLS_setCurrentLocale(pnUserGetNavigationLocale());

    return true;
}


/**
 * Log the user in
 *
 * @author Marco Canini
 * @param userName the name of the user logging in
 * @param password the password of the user logging in
 * @param rememberMe whether or not to remember this login
 * @returns bool
 * @return true if the user successfully logged in
 * @raise DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_FUNCTION_NOT_EXIST
 */
function pnUserLogIn($userName, $password, $rememberMe)
{
    global $pnUser_authenticationModules;

    if (pnUserLoggedIn()) {
        return true;
    }

    if (empty($userName)) {
        $msg = pnML('Empty uname.');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    $userId = PNUSER_AUTH_FAILED;
    foreach($pnUser_authenticationModules as $authModName) {
        // Every authentication module must at least implement the
        // Authentication interface so there's at least the authenticate_user
        // user api function

        $res = pnModAPILoad($authModName, 'user');
        if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
            return; // throw back
        }

        $userId = pnModAPIFunc($authModName, 'user', 'authenticate_user',
                            array('uname' => $userName, 'pass' => $password));
        if (!isset($userId) && pnExceptionMajor() != PN_NO_EXCEPTION) {
            return;
        } elseif ($userId != PNUSER_AUTH_FAILED) {
            // Someone authenticated us
    	    break;
    	}
        // $userId is PNUSER_AUTH_FAILED, try with next auth module
    }
    if ($userId == PNUSER_AUTH_FAILED) {
        return false;
    }

    // Catch common variations (0, false, '', ...)
    if (empty($rememberMe)) {
        $rememberMe = 0;
    } else {
        $rememberMe = 1;
    }

    // Set user session information
    $res = pnSession_setUserInfo($userId, $rememberMe);
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return; // throw back
    }

    // Set user auth module information
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $userstable = $pntable['users'];

    $query = "UPDATE $userstable
              SET pn_auth_module = '" . pnVarPrepForStore($authModName) . "'
              WHERE pn_uid = '" . pnVarPrepForStore($userId) . "'";
    $dbconn->Execute($query);
    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
    }

    // Set session variables

    // Keep a reference to auth module that authenticates successfully
    pnSessionSetVar('authenticationModule', $authModName);

    // Sync core fields that're duplicates in users table
    $res = pnUser__syncUsersTableFields();
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return; // throw back
    }

    // FIXME: <marco> here we could also set a last_logon timestamp

    return true;
}

/**
 * Log the user out
 *
 * @returns bool
 * @return true if the user successfully logged out
 * @raise DATABASE_ERROR
 */
function pnUserLogOut()
{
    if (!pnUserLoggedIn()) {
        return true;
    }
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    // Reset user session information
    $res = pnSession_setUserInfo(0, 0);
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return; // throw back
    }

    pnSessionDelVar('authenticationModule');

    return true;
}

/**
 * Checks if the user logged in.
 *
 * @returns bool
 * @return true if the user is logged in, false if they are not
 */
function pnUserIsLoggedIn()
{
    if (pnSessionGetVar('uid') != 0) {
        return true;
    } else {
        return false;
    }
}

/**
 * get the user's language
 *
 * @returns string
 * @return the name of the user's language
 * @raise DATABASE_ERROR
 */
function pnUserGetLang()
{
    // FIXME: <marco> DEPRECATED?
    $locale = pnUserGetNavigationLocale();
    $data = pnMLSLoadLocaleData($locale);
    if (!isset($data)) return; // throw back
    return $data['/language/iso3code'];
}

/**
 * Gets the user navigation locale
 */
function pnUserGetNavigationLocale()
{
    $locale = pnSessionGetVar('navigationLocale');
    if (!isset($locale)) {
        if (pnUserIsLoggedIn()) {
            $locale = pnUserGetVar('locale');
        }
        if (!isset($locale)) {
            if (pnExceptionMajor() != PN_NO_EXCEPTION) {
                // Here we need to return always a meaningfull result,
                // so what we can do here is only to log the exception
                // and call pnExceptionFree
                pnLogException(PNLOG_LEVEL_ERROR);
                pnExceptionFree();
            }
            $locale = pnMLSGetSiteLocale();
        }
        pnSessionSetVar('navigationLocale', $locale);
    }
    return $locale;
}

/**
 * Sets the user navigation locale
 */
function pnUserSetNavigationLocale($locale)
{
    $mode = pnMLSGetMode();
    if ($mode == PNMLS_BOXED_MULTI_LANGUAGE_MODE) {
        pnSessionSetVar('navigationLocale', $locale);
    }
}

/*
 * User variables API functions
 */

/**
 * get a user variable
 *
 * @author Marco Canini
 * @param name the name of the variable
 * @param uid the user to get the variable for
 * @returns mixed
 * @return the value of the user variable if the variable exists, void if the variable doesn't exist
 * @raise BAD_PARAM, NOT_LOGGED_IN, ID_NOT_EXIST, NO_PERMISSION, UNKNOWN, DATABASE_ERROR, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_FUNCTION_NOT_EXIST, VARIABLE_NOT_REGISTERED
 */
function pnUserGetVar($name, $userId = NULL)
{
    if (empty($name)) {
        $msg = pnML('Empty name.');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (empty($userId)) {
        $userId = pnSessionGetVar('uid');
    }
    if ($name == 'uid') {
        // User id for Anonymous is NULL, so we check later for this
        return $userId;
    }
    if (empty($userId)) {
        // Anonymous user => only uid, name and uname allowed, for other variable names
        // an exception of type NOT_LOGGED_IN is raised
        if ($name == 'name' || $name == 'uname') {
            return pnML('Anonymous');
        }
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NOT_LOGGED_IN',
                       new SystemException(__FILE__.'('.__LINE__.')'));
        return;
    }

/* TODO: figure out why this fails all the time now (marco ?)
    if ($userId != pnSessionGetVar('uid')) {
        // Security check
        // Here we use a trick
        // One user can make private some of its data by creating a permission with ACCESS_NONE as level
        // The trick is that this permission is applied to other users
        if (!pnSecAuthAction(0, 'users::Variables', "$name::", ACCESS_READ, $userId)) {
            if (pnExceptionMajor() != PN_NO_EXCEPTION) {
                return; // throw back
            }
            $msg = pnML('No permission to get value of #(1) user variable for uid #(2).', $name, $userId);
            pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                           new SystemException($msg));
            return;
        }
    }
*/

    if (!pnVarIsCached('User.Variables.'.$userId, $name)) {
        // check that $name variable appears in the dynamic user data fields
        $infos = pnUser__getUserVarInfo($name);
        if (!isset($infos)) {
        // FIXME: ignoring unknown user variables for now...
            if (pnExceptionMajor() != PN_NO_EXCEPTION &&
                pnExceptionId() == 'VARIABLE_NOT_REGISTERED') {
                pnVarSetCached('User.Variables.'.$userId, $name, false);
                // Here we can't raise an exception
                // so what we can do here is only to log the exception
                // and call pnExceptionFree
                pnLogException(PNLOG_LEVEL_ERROR);
                pnExceptionFree();
            }
            // Of sure got an exception
            return; // throw back
        }
        extract($infos); // $prop_id, $prop_dtype, $prop_default, $prop_validation

        $authModName = pnUser__getAuthModule($userId);
        if (!isset($authModName) && pnExceptionMajor() != PN_NO_EXCEPTION) {
            return; // throw back
        }
        $useAuthSystem = false; // Used for debug
        //$useAuthSystem = ($authModName == 'authsystem') ? true : false;

        if (!$useAuthSystem) {
            $res = pnModAPIFunc($authModName, 'user', 'has_capability',
                                array('capability' => PNUSER_AUTH_DYNAMIC_USER_DATA_HANDLER));
            if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
                return; // throw back
            }
            if ($res) {
                // $authModName supports the UserDataHandler interface
                $res = pnModAPIFunc($authModName, 'user', 'is_valid_variable',
                                    array('name' => $name));
                if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
                    return; // throw back
                }
                if ($res == false) {
                    // $name variable is retrieved from authsystem module
                    $useAuthSystem = true;
                }
            }
        } else {
            // $name variable is retrieved from authsystem module
            $useAuthSystem = true;
        }

        if ($useAuthSystem == true) {
            $authModName = 'authsystem';
            $res = pnModAPILoad($authModName, 'user');
            if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
                return; // throw back
            }
        }

        $value = pnModAPIFunc($authModName, 'user', 'get_user_variable',
                              array('uid' => $userId,
                                    'name' => $name,
                                    'prop_id' => $prop_id,
                                    'prop_dtype' => $prop_dtype));
        if (!isset($value) && pnExceptionMajor() != PN_NO_EXCEPTION) {
            return; // throw back
        }

        if ($value === false) {
            if (isset($prop_default)) {
                // Use the metainfo default value if any
                $value = $prop_default;
            }
            // else
            // Variable doesn't exist
            // false is here a special value to denote that variable was searched
            // but wasn't found so pnUserGetVar'll return void
            // will be called: pnVarSetCached('User.Variables.'.$userId, $name, false)
        } else {
            switch ($prop_dtype) {
                case PNUSER_DUD_TYPE_DOUBLE:
                    $value = (float) $value;
                    break;
                case PNUSER_DUD_TYPE_INTEGER:
                    $value = (int) $value;
                    break;
            }
        }

        pnVarSetCached('User.Variables.'.$userId, $name, $value);
    }

    if (!pnVarIsCached('User.Variables.'.$userId, $name)) {
        return false; //failure
    }

    $cachedValue = pnVarGetCached('User.Variables.'.$userId, $name);
    if ($cachedValue === false) {
        // Variable already searched but doesn't exist and has no default
        return;
    }

    return $cachedValue;
}

/**
 * set a user variable
 *
 * @author Marco Canini
 * @since 1.23 - 2002/02/01
 * @param name the name of the variable
 * @param value the value of the variable
 * @returns bool
 * @return true if the set was successful, false if validation fails
 * @raise BAD_PARAM, NOT_LOGGED_IN, ID_NOT_EXIST, NO_PERMISSION, UNKNOWN, DATABASE_ERROR, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_FUNCTION_NOT_EXIST, VARIABLE_NOT_REGISTERED
 */
function pnUserSetVar($name, $value, $userId = NULL)
{
    // check that $name is valid
    if (empty($name) || $name == 'uid' || $name == 'authenticationModule') {
        $msg = pnML('Empty name (#(1)) or invalid name.', $name);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (empty($userId)) {
        $userId = pnSessionGetVar('uid');
    }
    if (empty($userId)) {
        // Anonymous user
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NOT_LOGGED_IN',
                       new SystemException(__FILE__.'('.__LINE__.')'));return;
    }
    if ($userId != pnSessionGetVar('uid')) {
        // If you want to set a variable owned by another user
        // you must have ACCESS_EDIT permission
        // Security check
        if (!pnSecAuthAction(0, 'users::Variables', "$name::", ACCESS_EDIT)) {
            if (pnExceptionMajor() != PN_NO_EXCEPTION) {
                return; // throw back
            }
            $msg = pnML('No permission to set value of #(1) user variable for uid #(2).', $name, $userId);
            pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                           new SystemException($msg));
            return;
        }
    }


    // check that $name variable appears in the dynamic user data fields
    $infos = pnUser__getUserVarInfo($name);
    if (!isset($infos)) {
        // Of sure got an exception
        return; // throw back
    }
    extract($infos); // $prop_id, $prop_dtype, $prop_default, $prop_validation

    // FIXME: <marco> Do we want this?
    /*
    if ($value == $prop_default) {
        // Avoid duplicating default value into database
        return true;
    }
    */

    if (!pnUserValidateVar($name, $value)) {
        if (pnExceptionMajor() != PN_NO_EXCEPTION) {
            return; // throw back
        }
        // Validation failed
        return false;
    }

    if ($prop_dtype == PNUSER_DUD_TYPE_CORE) {
        // Keep in sync core fields
        $res = pnUser__setUsersTableUserVar($name, $value, $userId);
        if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
            return; // throw back
        }
    }

    $authModName = pnUser__getAuthModule($userId);
    if (!isset($authModName) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return; // throw back
    }
    $useAuthSystem = false; // Used for debug
    //$useAuthSystem = ($authModName == 'authsystem') ? true : false;

    if (!$useAuthSystem) {
        $res = pnModAPIFunc($authModName, 'user', 'has_capability',
        array('capability' => PNUSER_AUTH_DYNAMIC_USER_DATA_HANDLER));
        if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
            return; // throw back
        }
        if ($res) {
            // $authModName supports the UserDataHandler interface
            $res = pnModAPIFunc($authModName, 'user', 'is_valid_variable',
            array('name' => $name));
            if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
                return; // throw back
            }
            if ($res == false) {
                // $name variable is handled by authsystem module
                $useAuthSystem = true;
            }
        }
    } else {
        // $name variable is handled by authsystem module
        $useAuthSystem = true;
    }

    if ($useAuthSystem == true) {
        if ($prop_dtype == PNUSER_DUD_TYPE_CORE) {
            // Already updated
            return true;
        }
        $authModName = 'authsystem';
        $res = pnModAPILoad($authModName, 'user');
        if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
            return; // throw back
        }
    }

    $res = pnModAPIFunc($authModName, 'user', 'set_user_variable',
                        array('uid' => $userId,
                              'name' => $name,
                              'value' => $value,
                              'prop_id' => $prop_id,
                              'prop_dtype' => $prop_dtype));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return; // throw back
    }

    if ($res != true) {
        $msg = pnML('For an unknown reason the function set_user_variable of module #(1) didn\'t return true and didn\'t throw an exception.', $authModName);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNKNOWN',
                       new SystemException($msg));
        return;
    }
    // Keep in sync the UserVariables cache
    pnVarSetCached('User.Variables.'.$userId, $name, $value);

    return true;
}

/**
 * validate a user variable
 *
 * @since 1.60 - 2002/05/02
 * @author Marco Canini
 * @param name user variable name
 * @param value value to be validated
 * @returns bool
 * @return true when validation was successfully accomplished, false otherwise
 * @raise ID_NOT_EXIST, DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_FUNCTION_NOT_EXIST, UNKNOWN
 */
function pnUserValidateVar($name, $value)
{
    // check that $name is valid
    if (empty($name)) {
        $msg = pnML('Empty name.');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }

    if (pnVarIsCached('User.Variables.Validated', $name) &&
        pnVarGetCached('User.Variables.Validated', $name) == $value) {
        return true;
    }

    // check that $name variable appears in the dynamic user data fields
    $infos = pnUser__getUserVarInfo($name);
    if (!isset($infos)) {
        // Of sure got an exception
        return; // throw back
    }
    extract($infos); // $prop_id, $prop_dtype, $prop_default, $prop_validation

    if (isset($prop_validation)) {
        switch ($prop_dtype) {
            case PNUSER_DUD_TYPE_DOUBLE:
                $value = (float) $value;
                break;
            case PNUSER_DUD_TYPE_INTEGER:
                $value = (int) $value;
                break;
        }

        // Do validation
        $res = pnUser__validationApply($prop_validation, $value);
        if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
            return; // throw back
        }
        if ($res == false) {
            // Validation failed
            return false;
        }
    }

    pnVarSetCached('User.Variables.Validated', $name, $value);

    return true;
}

/**
 * Compare Passwords
 *
 * @access public
 * @return bool true if the passwords match, false otherwise
 */
function pnUserComparePasswords($givenPassword, $realPassword, $userName, $cryptSalt = '')
{
    $compare2crypt = true;
    $compare2text = true;

    $system = pnConfigGetVar('system');

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

/**
 * Get the user's theme directory path
 *
 * @returns string
 * @return the user's theme directory path if successful, void otherwise
 */
function pnUser_getThemeName()
{
    if (!pnUserLoggedIn()) {
        return;
    }
    $themeName = pnUserGetVar('Theme');
    if (pnExceptionMajor() != PN_NO_EXCEPTION) {
        // Here we can't raise an exception
        // so what we can do here is only to log the exception
        // and call pnExceptionFree
        pnLogException(PNLOG_LEVEL_ERROR);
        pnExceptionFree();
        return;
    }
    return $themeName;
}

// PRIVATE FUNCTIONS

/*
 * @access private
 * @raise UNKNOWN, DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST
 */
function pnUser__getAuthModule($userId)
{
    // FIXME: what happens for anonymous users ???
    // TODO: check coherence 1 vs. 0 for Anonymous users !!!
    if ($userId == pnSessionGetVar('uid')) {
        $authModName = pnSessionGetVar('authenticationModule');
        if (!isset($authModName)) {
            // Should never happen, however ...
            $msg = pnML('Auth module isn\'t set as session variable.');
            pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNKNOWN',
                           new SystemException($msg));
            return;
        }
    } else {
        list($dbconn) = pnDBGetConn();
        $pntable = pnDBGetTables();

        // Get user auth_module name
        $userstable = $pntable['users'];

        $query = "SELECT pn_auth_module
                  FROM $userstable
                  WHERE pn_uid = '" . pnVarPrepForStore($userId) . "'";
        $result = $dbconn->Execute($query);
        if ($dbconn->ErrorNo() != 0) {
            $msg = pnMLByKey('DATABASE_ERROR', $query);
            pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                           new SystemException($msg));
            return;
        }

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
    $res = pnModAPILoad($authModName, 'user');
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return; // throw back
    }

    return $authModName;
}

/*
 * @access private
 * @raise DATABASE_ERROR, ID_NOT_EXIST
 */
function pnUser__getUserVarInfo($name)
{
    $pntable = pnDBGetTables();

    $userstable = $pntable['users'];

    // Core fields aren't handled with Dynamic User Data
    if ($name == 'name' || $name == 'uname' ||
        $name == 'email' || $name == 'url') {
        // You're asking metainfo for a core field
        // We can safely return a default value
        // prop_id = 0 means variable won't go in user_data table
        return array('prop_id' => 0, 'prop_dtype' => PNUSER_DUD_TYPE_CORE);
    }

    if (!pnVarIsCached('User.Variables.Info', $name)) {
        pnVarSetCached('User.Variables.Info', $name, false); // If at the end of operations it still
                                                             // be false we're sure that the property
                                                             // was searched but not found

        list($dbconn) = pnDBGetConn();

        $propertiestable = $pntable['user_property'];

        if (($ind = strpos($name, '_')) > 0) {
            // Here we do a pre-caching
            $name_prefix = substr($name, 0, $ind + 1);
            // Select all user vars that begins with $name_prefix
            $query = "SELECT pn_prop_id,
                             pn_prop_label,
                             pn_prop_dtype,
                             pn_prop_default,
                             pn_prop_validation
                             FROM $propertiestable
                             WHERE pn_prop_label LIKE '" . pnVarPrepForStore($name_prefix) ."%%'";
            $result = $dbconn->Execute($query);
            if ($dbconn->ErrorNo() != 0) {
                $msg = pnMLByKey('DATABASE_ERROR', $query);
                pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                               new SystemException($msg));
                return;
            }

            while (!$result->EOF) {
                list($prop_id, $prop_label, $prop_dtype, $prop_default, $prop_validation) = $result->fields;

                $info['prop_id'] = (int) $prop_id;
                $info['prop_dtype'] = (int) $prop_dtype;
                if (isset($prop_default)) {
                    switch($info['prop_dtype']) {
                        case PNUSER_DUD_TYPE_STRING:
                        case PNUSER_DUD_TYPE_TEXT:
                            $info['prop_default'] = $prop_default;
                            break;
                        case PNUSER_DUD_TYPE_DOUBLE:
                            $info['prop_default'] = (float) $prop_default;
                            break;
                        case PNUSER_DUD_TYPE_INTEGER:
                            $info['prop_default'] = (int) $prop_default;
                            break;
                    }
                }
                $info['prop_validation'] = $prop_validation;
                // Cache info
                pnVarSetCached('User.Variables.Info', $prop_label, $info);

                $result->MoveNext();
            }

            $result->Close();
        } else {
            // Confirm that this is a known value
            $query = "SELECT pn_prop_id,
                      pn_prop_dtype,
                      pn_prop_default,
                      pn_prop_validation
                      FROM $propertiestable
                      WHERE pn_prop_label = '" . pnVarPrepForStore($name) ."'";
            $result = $dbconn->Execute($query);
            if ($dbconn->ErrorNo() != 0) {
                $msg = pnMLByKey('DATABASE_ERROR', $query);
                pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                               new SystemException($msg));
                return;
            }

            if (!$result->EOF) {
                list($prop_id, $prop_dtype, $prop_default, $prop_validation) = $result->fields;

                $info['prop_id'] = (int) $prop_id;
                $info['prop_dtype'] = (int) $prop_dtype;
                if (isset($prop_default)) {
                    switch($info['prop_dtype']) {
                        case PNUSER_DUD_TYPE_STRING:
                        case PNUSER_DUD_TYPE_TEXT:
                        $info['prop_default'] = $prop_default;
                        break;
                        case PNUSER_DUD_TYPE_DOUBLE:
                        $info['prop_default'] = (float) $prop_default;
                        break;
                        case PNUSER_DUD_TYPE_INTEGER:
                        $info['prop_default'] = (int) $prop_default;
                        break;
                    }
                }
                $info['prop_validation'] = $prop_validation;
                // Cache info
                pnVarSetCached('User.Variables.Info', $name, $info);

                $result->Close();
            }
        }
    }

    $info = pnVarGetCached('User.Variables.Info', $name);
    if ($info == false) {
        $msg = pnML('Metadata for user variable #(1) are not correctly registered in database.', $name);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'VARIABLE_NOT_REGISTERED',
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
function pnUser__syncUsersTableFields()
{
    $userId = pnSessionGetVar('uid');
    if (empty($userId)) {
        $msg = pnML('Empty uid.');
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NOT_LOGGED_IN',
                       new SystemException($msg));return;
    }

    $authModName = pnUser__getAuthModule($userId);
    if (!isset($authModName) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return; // throw back
    }
    if ($authModName == 'authsystem') {
        // Already synced
        return true;
    }

    $res = pnModAPIFunc($authModName, 'user', 'has_capability',
                        array('capability' => PNUSER_AUTH_DYNAMIC_USER_DATA_HANDLER));
    if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
        return; // throw back
    }
    if ($res == false) {
        // Impossible to go out of sync
        return true;
    }

    $name = pnUserGetVar('name');
    if (!isset($name)) {
        return; // throw back
    }
    $res = pnUser__setUsersTableUserVar('name', $name, $userId);
    if (!isset($res)) {
        return; // throw back
    }
    $uname = pnUserGetVar('uname');
    if (!isset($uname)) {
        return; // throw back
    }
    $res = pnUser__setUsersTableUserVar('uname', $uname, $userId);
    if (!isset($res)) {
        return; // throw back
    }
    $email = pnUserGetVar('email');
    if (!isset($email)) {
        return; // throw back
    }
    $res = pnUser__setUsersTableUserVar('email', $email, $userId);
    if (!isset($res)) {
        return; // throw back
    }

    return true;
}

/*
 * @access private
 * @returns bool
 * @return true
 * @raise DATABASE_ERROR
 */
function pnUser__setUsersTableUserVar($name, $value, $userId)
{
    list($dbconn) = pnDBGetConn();
    $pntable = pnDBGetTables();

    $userstable = $pntable['users'];

    $query = "UPDATE $userstable
              SET pn_name = '" . pnVarPrepForStore($value) . "'
              WHERE pn_uid = '" . pnVarPrepForStore($userId) . "'";
    $dbconn->Execute($query);

    if ($dbconn->ErrorNo() != 0) {
        $msg = pnMLByKey('DATABASE_ERROR', $query);
        pnExceptionSet(PN_SYSTEM_EXCEPTION, 'DATABASE_ERROR',
                       new SystemException($msg));
        return;
    }

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
function pnUser__validationApply($validation, $valueToCheck)
{
    // TODO: set a ML errmsg on failure

    // Syntax trees of parsed validation strings are cached
    if (!pnVarIsCached('User.Variables.Validators', $validation)) {
        $val_stack = pnUser__validationParse($validation);
        if (!isset($val_stack)) {
            return;
        }
        pnVarSetCached('User.Variables.Validators', $validation, $val_stack);
    }
    $val_stack = pnVarGetCached('User.Variables.Validators', $validation);

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
                $msg = pnML('Invalid operator \'#(1)\' for type \'num\'.', $val_entry->operator);
                pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNKNOWN',
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
                pnExceptionSet(PN_SYSTEM_EXCEPTION, 'NOT_IMPLEMENTED',
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
                $msg = pnML('Invalid operator \'#(1)\' for type \'string\'.', $val_entry->operator);
                pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNKNOWN',
                       new SystemException($msg));
                return;
            }
        } elseif ($val_entry->type == 'func') {
            list($modname, $funcname) = explode(',', $val_entry->param);

            // Load module
            $res = pnModAPILoad($modname, 'user');
            if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
                return; // throw back
            }

            $args = array('value' => $value);
            if ($val_entry->param != 'none') {
                $args['param'] = $val_entry->param;
            }

            // Call module API function
            $res = pnModAPIFunc($modname, 'user', $funcname, $args);
            if (!isset($res) && pnExceptionMajor() != PN_NO_EXCEPTION) {
                return; // throw back
            }
        } else {
            $msg = pnML('Invalid type \'#(1)\'.', $val_entry->type);
            pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNKNOWN',
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
class pnUser__ValEntry
{
    var $negation = false;
    var $type;
    var $operator;
    var $param;
}

/**
 * @access private
 */
function pnUser__validationExplodeEsc($delimiter, $str)
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
function pnUser__validationParse($validationString)
{
    $val_stack = array();

    $validator_list = pnUser__validationExplodeEsc('&', $validationString);

    foreach($validator_list as $validator_string) {
        $val_entry = new pnUser__ValEntry();

        $validator = pnUser__validationExplodeEsc(':', $validator_string);

        if (count($validator) != 3) {
            $msg = pnML('Parse failed for validation string: \'#(1)\'.', $validationString);
            pnExceptionSet(PN_SYSTEM_EXCEPTION, 'UNKNOWN',
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
