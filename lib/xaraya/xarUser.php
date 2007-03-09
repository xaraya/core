<?php
/**
 * User System
 *
 * @package core
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage user
 * @author Jim McDonald
 * @author Marco Canini <marco@xaraya.com>
 * @todo <marco> user status field
 */

// IS THIS STILL USED?
global $installing;

/**
 * Exceptions defined by this subsystem
 *
 */
class NotLoggedInException extends xarExceptions
{
    protected $message = 'An operation was encountered that requires the user to be logged in. If you are currently logged in please report this as a bug.';
}

/**
 * Authentication modules capabilities
 * (to be revised e.g. to differentiate read & update capability for core & dynamic)
 */
define('XARUSER_AUTH_AUTHENTICATION'           ,   1);
define('XARUSER_AUTH_DYNAMIC_USER_DATA_HANDLER',   2);
define('XARUSER_AUTH_PERMISSIONS_OVERRIDER'    ,  16);
define('XARUSER_AUTH_USER_CREATEABLE'          ,  32);
define('XARUSER_AUTH_USER_DELETEABLE'          ,  64);
define('XARUSER_AUTH_USER_ENUMERABLE'          , 128);

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
function xarUser_init(Array &$args, $whatElseIsGoingLoaded)
{
    // User System and Security Service Tables
    $systemPrefix = xarDBGetSystemTablePrefix();

    // CHECK: is this needed?
    $tables = array(
        'roles'       => $systemPrefix . '_roles',
        'realms'      => $systemPrefix . '_security_realms',
        'rolemembers' => $systemPrefix . '_rolemembers'
    );

    xarDB::importTables($tables);

    $GLOBALS['xarUser_authenticationModules'] = $args['authenticationModules'];

    xarMLS_setCurrentLocale(xarUserGetNavigationLocale());
    xarTplSetThemeName(xarUserGetNavigationThemeName());

    // Register the UserLogin event
    xarEvents::register('UserLogin');
    // Register the UserLogout event
    xarEvents::register('UserLogout');

    return true;
}

/**
 * Log the user in
 *
 * @access public
 * @param  string  $userName the name of the user logging in
 * @param  string  $password the password of the user logging in
 * @param  integer $rememberMe whether or not to remember this login
 * @return bool true if the user successfully logged in
 * @throws EmptyParameterException, SQLException
 * @todo <marco> #1 here we could also set a last_logon timestamp
 */
function xarUserLogIn($userName, $password, $rememberMe = 0)
{
    if (xarUserIsLoggedIn()) {
        return true;
    }
    if (empty($userName)) throw new EmptyParameterException('userName');
    if (empty($password)) throw new EmptyParameterException('password');


    $userId = XARUSER_AUTH_FAILED;
    $args = array('uname' => $userName, 'pass' => $password);

    foreach($GLOBALS['xarUser_authenticationModules'] as $authModName)
    {
        // Bug #918 - If the module has been deactivated, then continue
        // checking with the next available authentication module
        if (!xarMod::isAvailable($authModName))
            continue;

        // Every authentication module must at least implement the
        // authentication interface so there's at least the authenticate_user
        // user api function
        if (!xarMod::apiLoad($authModName, 'user'))
            continue;

        $modInfo = xarMod::getBaseInfo($authModName);
        $modId = $modInfo['systemid'];

        // CHECKME: Does this raise an exception??? If so:
        // TODO: test with multiple auth modules and wrap in try/catch clause
        $userId = xarModAPIFunc($authModName, 'user', 'authenticate_user', $args);
        if (!isset($userId)) {
            return; // throw back
        } elseif ($userId != XARUSER_AUTH_FAILED) {
            // Someone authenticated the user or passed XARUSER_AUTH_DENIED
            break;
        }
    }
    if ($userId == XARUSER_AUTH_FAILED || $userId == XARUSER_AUTH_DENIED)
    {
        if (xarModVars::get('privileges','lastresort'))
        {
            $secret = unserialize(xarModVars::get('privileges','lastresort'));
            if ($secret['name'] == md5($userName) && $secret['password'] == md5($password))
            {
                $userId = XARUSER_LAST_RESORT;
                $rememberMe = 0;
            }
         }
        if ($userId !=XARUSER_LAST_RESORT) {
            return false;
        }
    }

    // Catch common variations (0, false, '', ...)
    if (empty($rememberMe))
        $rememberMe = 0;
    else
        $rememberMe = 1;

    // Set user session information
    // TODO: make this a class static in xarSession.php
    if (!xarSession_setUserInfo($userId, $rememberMe))
        return; // throw back

    // Set user auth module information
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $rolestable = $xartable['roles'];

    // TODO: this should be inside roles module
    try {
        $dbconn->begin();
        $query = "UPDATE $rolestable SET xar_auth_modid = ? WHERE xar_uid = ?";
        $stmt = $dbconn->prepareStatement($query);
        $stmt->executeUpdate(array($modId,$userId));
        $dbconn->commit();
    } catch (SQLException $e) {
        $dbconn->rollback();
        throw $e;
    }

    // Set session variables

    // Keep a reference to auth module that authenticates successfully
    xarSessionSetVar('authenticationModule', $authModName);

    // FIXME: <marco> here we could also set a last_logon timestamp
    //<jojodee> currently set in individual authsystem when success on login returned to it

    // User logged in successfully, trigger the proper event with the new userid
    xarEvents::trigger('UserLogin',$userId);

    return true;
}

/**
 * Log the user out
 *
 * @access public
 * @return bool true if the user successfully logged out
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
    if (!isset($res)) {
        return; // throw back
    }

    xarSessionDelVar('authenticationModule');

    // User logged out successfully, trigger the proper event with the old userid
    xarEvents::trigger('UserLogout',$userId);

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
    // FIXME: restore "clean" code once uid+session issues are resolved
    //return xarSessionGetVar('uid') != _XAR_ID_UNREGISTERED;
    return (xarSessionGetVar('uid') != _XAR_ID_UNREGISTERED
            && xarSessionGetVar('uid') != 0);
}

/**
 * Gets the user navigation theme name
 *
 * @access public
 * @return string name of the users navigation theme
 */
function xarUserGetNavigationThemeName()
{
    $themeName = xarTplGetThemeName();

    if (xarUserIsLoggedIn()){
        $uid = xarUserGetVar('uid');
        $userThemeName = xarModUserVars::get('themes', 'default', $uid);
        if ($userThemeName) $themeName=$userThemeName;
    }

    return $themeName;
}

/**
 * Set the user navigation theme name
 *
 * @access public
 * @param  string $themeName name of the theme to set as navigation theme
 * @return void
 */
function xarUserSetNavigationThemeName($themeName)
{
    assert('$themeName != ""');
    // uservar system takes care of dealing with anynomous
    xarModUserVars::set('themes', 'default', $themeName);
}

/**
 * Get the user navigation locale
 *
 * @access public
 * @return string $locale users navigation locale name
 */
function xarUserGetNavigationLocale()
{
    if (xarUserIsLoggedIn())
    {
        $uid = xarUserGetVar('uid');
          //last resort user is falling over on this uservar by setting multiple times
         //return true for last resort user - use default locale
         if ($uid==XARUSER_LAST_RESORT) return true;

        $locale = xarModUserVars::get('roles', 'locale');
        if (!isset($locale)) {
            // CHECKME: why is this here? The logic of falling back is already in the modgetuservar
            $siteLocale = xarModVars::get('roles', 'locale');
            if (!isset($siteLocale)) {
                xarModVars::set('roles', 'locale', '');
            }
        }
        if (empty($locale)) {
            $locale = xarSessionGetVar('navigationLocale');
            if (!isset($locale)) {
                $locale = xarMLSGetSiteLocale();
            }
            xarModUserVars::set('roles', 'locale', $locale);
        } else {
            $siteLocales = xarMLSListSiteLocales();
            if (!in_array($locale, $siteLocales)) {
                // Locale not available, use the default
                $locale = xarMLSGetSiteLocale();
                xarModUserVars::set('roles', 'locale', $locale);
                xarLogMessage("WARNING: falling back to default locale: $locale in xarUserGetNavigationLocale function");
            }
        }
        xarSessionSetVar('navigationLocale', $locale);
    } else {
        $locale = xarSessionGetVar('navigationLocale');
        if (!isset($locale)) {
            // CHECKME: use dynamicdata for roles, module user variable and/or
            // session variable (see also 'timezone' in xarMLS_userOffset())
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
 * @param  string $locale
 * @return bool true if the navigation locale is set, false if not
 */
function xarUserSetNavigationLocale($locale)
{
    if (xarMLSGetMode() != XARMLS_SINGLE_LANGUAGE_MODE) {
        xarSessionSetVar('navigationLocale', $locale);
        if (xarUserIsLoggedIn()) {
            $userLocale = xarModUserVars::get('roles', 'locale');
            if (!isset($userLocale)) {
                // CHECKME: Why is this here? the fallback logic is already in modgetuservar
                $siteLocale = xarModVars::get('roles', 'locale');
                if (!isset($siteLocale)) {
                    xarModVars::set('roles', 'locale', '');
                }
            }
            xarModUserVars::set('roles', 'locale', $locale);
        }
        return true;
    }
    return false;
}

/*
 * User variables API functions
 */

/**
 * Initialise the user object
 *
 * @todo get rid of this global here
 */
$GLOBALS['xarUser_objectRef'] = null;

/**
 * Get a user variable
 *
 * @access public
 * @param  string  $name the name of the variable
 * @param  integer $userId integer the user to get the variable for
 * @return mixed the value of the user variable if the variable exists, void if the variable doesn't exist
 * @throws EmptyParameterException, NotLoggedInException, BadParameterException, IDNotFoundException
 * @todo <marco> #1 figure out why this check failsall the time now: if ($userId != xarSessionGetVar('uid')) {
 * @todo <marco FIXME: ignoring unknown user variables for now...
 * @todo redesign the delegation to auth* modules for handling user variables
 * @todo add some security for getting to user variables (at least from another uid)
 * @todo define clearly what the difference or similarity is with dd here
 */
function xarUserGetVar($name, $userId = NULL)
{
    if (empty($name)) throw new EmptyParameterException('name');

    if (empty($userId)) {
        $userId = xarSessionGetVar('uid');
    }
    if ($name == 'uid') {
        return $userId;
    }
    if ($userId == _XAR_ID_UNREGISTERED) {
        // Anonymous user => only uid, name and uname allowed, for other variable names
        // an exception of type NOT_LOGGED_IN is raised
        // CHECKME: if we're going the route of moditemvars, this doesn need to be the case
        if ($name == 'name' || $name == 'uname') {
            return xarML('Anonymous');
        }
        throw new NotLoggedInException();
    }

    // Don't allow any module to retrieve passwords in this way
    if ($name == 'pass') throw new BadParameterException('name');

    if (!xarCore::isCached('User.Variables.'.$userId, $name)) {

        if ($name == 'name' || $name == 'uname' || $name == 'email') {
            if ($userId == XARUSER_LAST_RESORT) {
                return xarML('No Information'); // better return null here
            }
            // retrieve the item from the roles module
            $userRole = xarModAPIFunc('roles',  'user',  'get',
                                       array('uid' => $userId));

            if (empty($userRole) || $userRole['uid'] != $userId) {
                throw new IDNotFoundException($userId,'User identified by uid #(1) does not exist.');
            }

            xarCore::setCached('User.Variables.'.$userId, 'uname', $userRole['uname']);
            xarCore::setCached('User.Variables.'.$userId, 'name', $userRole['name']);
            xarCore::setCached('User.Variables.'.$userId, 'email', $userRole['email']);

        } elseif (!xarUser__isVarDefined($name)) {
            if (xarModVars::get('roles',$name) || xarModVars::get('roles','set'.$name)) { //acount for optionals that need to be activated)
                $value = xarModUserVars::get('roles',$name,$userId);
                if ($value == null) {
                    xarCore::setCached('User.Variables.'.$userId, $name, false);
                    // Here we can't raise an exception because they're all optional
                    $optionalvars=array('locale','timezone','usertimezone','userlastlogin',
                                        'userhome','primaryparent','passwordupdate');
                    //if ($name != 'locale' && $name != 'timezone') {
                    if (!in_array($name, $optionalvars)) {
                    // log unknown user variables to inform the site admin
                        $msg = xarML('User variable #(1) was not correctly registered', $name);
                        xarLogMessage($msg, XARLOG_LEVEL_ERROR);
                    }
                    return;
                }
                else {
                    xarCore::setCached('User.Variables.'.$userId, $name, $value);
                }
            }

        } else {
            // retrieve the user item
            $itemid = $GLOBALS['xarUser_objectRef']->getItem(array('itemid' => $userId));
            if (empty($itemid) || $itemid != $userId) {
                throw new IDNotFoundException($userId,'User identified by uid #(1) does not exist.');
            }

            // save the properties
            $properties =& $GLOBALS['xarUser_objectRef']->getProperties();
            foreach (array_keys($properties) as $key) {
                if (isset($properties[$key]->value)) {
                    xarCore::setCached('User.Variables.'.$userId, $key, $properties[$key]->value);
                }
            }
        }
    }

    if (!xarCore::isCached('User.Variables.'.$userId, $name)) {
        return false; //failure
    }

    $cachedValue = xarCore::getCached('User.Variables.'.$userId, $name);
    if ($cachedValue === false) {
        // Variable already searched but doesn't exist and has no default
        return;
    }

    return $cachedValue;
}

/**
 * Set a user variable
 *
 * @since 1.23 - 2002/02/01
 * @access public
 * @param  string  $name  the name of the variable
 * @param  mixed   $value the value of the variable
 * @param  integer $userId integer user's ID
 * @return bool true if the set was successful, false if validation fails
 * @throws EmptyParameterException, BadParameterException, NotLoggedInException, xarException, IDNotFoundException
 * @todo redesign the delegation to auth* modules for handling user variables
 * @todo some securitycheck for retrieving at least other users variables ?
 */
function xarUserSetVar($name, $value, $userId = null)
{
    // check that $name is valid
    if (empty($name)) throw new EmptyParameterException('name');
    if ($name == 'uid' || $name == 'authenticationModule' || $name == 'pass') {
        throw new BadParameterException('name');
    }

    if (empty($userId)) {
        $userId = xarSessionGetVar('uid');
    }
    if ($userId == _XAR_ID_UNREGISTERED) {
        // Anonymous user
        throw new NotLoggedInException();
    }

    if ($name == 'name' || $name == 'uname' || $name == 'email') {
        // TODO: replace with some roles API
        // TODO: not -^ but get rid of this entirely here.
        xarUser__setUsersTableUserVar($name, $value, $userId);

    } elseif (!xarUser__isVarDefined($name)) {
        if (xarModVars::get('roles',$name)) {
            xarCore::setCached('User.Variables.'.$userId, $name, false);
            throw new xarException($name,'User variable #(1) was not correctly registered');
        } else {
            xarModUserVars::set('roles',$name,$value,$userId);
        }
    } else {
        // retrieve the user item
        $itemid = $GLOBALS['xarUser_objectRef']->getItem(array('itemid' => $userId));
        if (empty($itemid) || $itemid != $userId) {
            throw new IDNotFoundException($userId,'User identified by uid "#(1)" does not exist.');
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

    // Keep in sync the UserVariables cache
    xarCore::setCached('User.Variables.'.$userId, $name, $value);

    return true;
}

/**
 * Compare Passwords
 *
 * @access public
 * @param  string $givenPassword  the password given for comparison
 * @param  string $realPassword   the reference password to compare to
 * @param  string $userName       name of the corresponding user?
 * @param  string $cryptSalt      ?
 * @return bool true if the passwords match, false otherwise
 * @todo   weird duckling here
 * @todo   consider something strong than md5 here (not trivial wrt upgrading though)
 */
function xarUserComparePasswords($givenPassword, $realPassword, $userName, $cryptSalt = '')
{
    // TODO: consider moving to something stronger like sha1
    $md5pass = md5($givenPassword);
    if (strcmp($md5pass, $realPassword) == 0)
        // Huh? shouldn't this be true instead of the md5 ?
        return $md5pass;

    return false;
}

// PROTECTED FUNCTIONS

// PRIVATE FUNCTIONS

/**
 * Get user's authentication module
 *
 * @access private
 * @param  userId string
 * @todo   what happens for anonymous users ???
 * @todo   check coherence 1 vs. 0 for Anonymous users !!!
 * @todo   this should be somewhere else probably (base class of auth* or roles mebbe)
 * @todo   is $userId a string? looks like an ID
 */
function xarUser__getAuthModule($userId)
{
    if ($userId == xarSessionGetVar('uid')) {
        $authModName = xarSessionGetVar('authenticationModule');
        if (isset($authModName)) {
            return $authModName;
        }
    }

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    // Get user auth_module name
    $rolestable = $xartable['roles'];
    $modstable = $xartable['modules'];

    $query = "SELECT mods.name
              FROM $modstable mods, $rolestable roles
              WHERE mods.id = roles.xar_auth_modid AND
                    roles.xar_uid = ?";
    $stmt =& $dbconn->prepareStatement($query);
    $result =& $stmt->executeQuery(array($userId),ResultSet::FETCHMODE_NUM);

    if (!$result->next()) {
        // That user has never logon, strange, don't you think?
        // However fallback to authsystem
        $authModName = 'authsystem';
    } else {
        $authModName = $result->getString(1);
        // TODO: remove when issue of Anonymous users is resolved
        // Q: what issue?
        if (empty($authModName)) {
            $authModName = 'authsystem';
        }
    }
    $result->Close();

    if (!xarMod::apiLoad($authModName, 'user')) return;

    return $authModName;
}

/**
 * See if a Variable has been defined
 *
 * @access private
 * @param  string $name name of the variable to check
 * @return bool true if the variable is defined
 * @todo   rething this.
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

/**
 * @access private
 * @return bool
 * @throws SQLException
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
    try {
        $dbconn->begin();
        $query = "UPDATE $rolestable SET $usercolumns[$name] = ? WHERE xar_uid = ?";
        $stmt = $dbconn->prepareStatement($query);
        $stmt->executeUpdate(array($value,$userId));
    } catch (SQLException $e) {
        $dbconn->rollback();
        throw $e;
    }
    return true;
}
?>
