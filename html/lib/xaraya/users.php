<?php
/**
 * User System
 *
 * @package core
 * @subpackage user
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author Jim McDonald
 * @author Marco Canini <marco@xaraya.com>
 * @author Marc Lutolf
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

// Legacy calls

function xarUserLogIn($userName, $password, $rememberMe = 0)
{   
    return xarUser::logIn($userName, $password, $rememberMe = 0); 
}
function xarUserLogOut()
{   
    return xarUser::logOut(); 
}
function xarUserIsLoggedIn()
{   
    return xarUser::isLoggedIn(); 
}
function xarUserGetNavigationThemeName()
{   
    return xarUser::getNavigationThemeName(); 
}
function xarUserSetNavigationThemeName($themeName)
{   
    return xarUser::setNavigationThemeName($themeName); 
}
function xarUserGetNavigationLocale()
{   
    return xarUser::getNavigationLocale(); 
}
function xarUserSetNavigationLocale($locale)
{   
    return xarUser::setNavigationLocale($locale); 
}
function xarUserGetVar($name, $userId = NULL)
{   
    return xarUser::getVar($name, $userId); 
}
function xarUserSetVar($name, $value, $userId = null)
{   
    return xarUser::setVar($name, $value, $userId); 
}
function xarUserComparePasswords($givenPassword, $realPassword, $userName, $cryptSalt = '')
{   
    return xarUser::comparePasswords($givenPassword, $realPassword, $userName, $cryptSalt); 
}

class xarUser extends Object
{
    private static $objectRef;
    public static $authenticationModules;
    
    /**
     * Initialise the User System
     *
     * 
     * @global xarUser_authentication modules array
     * @param args[authenticationModules] array
     * @return boolean true on success
     */
    static public function init(Array &$args)
    {
        // User System and Security Service Tables
        $prefix = xarDB::getPrefix();
    
        // CHECKME: is this needed?
        $tables = array(
            'roles'       => $prefix . '_roles',
            'realms'      => $prefix . '_security_realms',
            'rolemembers' => $prefix . '_rolemembers'
        );
    
        xarDB::importTables($tables);
    
        self::$authenticationModules = $args['authenticationModules'];
    
        xarMLS::setCurrentLocale(self::getNavigationLocale());
        xarTpl::setThemeName(self::getNavigationThemeName());
    
        // These events are now registered during authsystem module init
        // Register the UserLogin event
        //xarEvents::register('UserLogin');
        // Register the UserLogout event
        //xarEvents::register('UserLogout');
    
        // Populate the GLOBAL for legacy calls
        $GLOBALS['xarUser_authenticationModules'] =  self::$authenticationModules;
        
        return true;
    }

    /**
     * @TODO <chris> do login and logout functions belong in here, or in authsystem ?
    **/
    /**
     * Log the user in
     *
     * 
     * @param  string  $userName the name of the user logging in
     * @param  string  $password the password of the user logging in
     * @param  integer $rememberMe whether or not to remember this login
     * @return boolean true if the user successfully logged in
     * @throws EmptyParameterException, SQLException
     * @todo <marco> #1 here we could also set a last_logon timestamp
     */
    static public function logIn($userName, $password, $rememberMe = 0)
    {
        if (self::isLoggedIn()) return true;
    
        if (empty($userName)) throw new EmptyParameterException('userName');
        if (empty($password)) throw new EmptyParameterException('password');
    
        $userId = XARUSER_AUTH_FAILED;
        $args = array('uname' => $userName, 'pass' => $password);
    
        foreach(self::$authenticationModules as $authModName)
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
            $userId = xarMod::apiFunc($authModName, 'user', 'authenticate_user', $args);
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
            $rememberMe = false;
        else
            $rememberMe = true;
    
        // Set user session information
        // TODO: make this a class static in xarSession.php
        if (!xarSession_setUserInfo($userId, $rememberMe))
            return; // throw back
    
        // Set user auth module information
        $dbconn   = xarDB::getConn();
        $xartable = xarDB::getTables();
    
        $rolestable = $xartable['roles'];
    
        // TODO: this should be inside roles module
        try {
            $dbconn->begin();
            $query = "UPDATE $rolestable SET auth_module_id = ? WHERE id = ?";
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
        //xarEvents::trigger('UserLogin',$userId);
        xarEvents::notify('UserLogin', $userId);
        xarSession::delVar('privilegeset');
        return true;
    }

    /**
     * Log the user out
     *
     * 
     * @return boolean true if the user successfully logged out
     */
    static public function logOut()
    {
        if (!self::isLoggedIn()) {
            return true;
        }
        // get the current userid before logging out
        $userId = xarSessionGetVar('id');
    
        // Reset user session information
        $res = xarSession_setUserInfo(_XAR_ID_UNREGISTERED, false);
        if (!isset($res)) {
            return; // throw back
        }
    
        xarSessionDelVar('authenticationModule');
    
        // User logged out successfully, trigger the proper event with the old userid
        //xarEvents::trigger('UserLogout',$userId);
        xarEvents::notify('UserLogout',$userId);
        
        xarSession::delVar('privilegeset');
        return true;
    }

    /**
     * Check if the user logged in
     *
     * 
     * @return boolean true if the user is logged in, false if they are not
     */
    static public function isLoggedIn()
    {
        // FIXME: restore "clean" code once id+session issues are resolved
        //return xarSessionGetVar('role_id') != _XAR_ID_UNREGISTERED;
        return (xarSessionGetVar('role_id') != _XAR_ID_UNREGISTERED
                && xarSessionGetVar('role_id') != 0);
    }

    /**
     * Gets the user navigation theme name
     *
     * 
     * @return string name of the users navigation theme
     */
    static public function getNavigationThemeName()
    {
        $themeName = xarTpl::getThemeName();
    
        if (self::isLoggedIn() && (bool)xarModVars::get('themes', 'enable_user_menu')){
            $id = self::getVar('id');
            $userThemeName = xarModUserVars::get('themes', 'default_theme', $id);
            if ($userThemeName) $themeName=$userThemeName;
        }
    
        return $themeName;
    }

    /**
     * Set the user navigation theme name
     *
     * 
     * @param  string $themeName name of the theme to set as navigation theme
     * @return void
     */
    static public function setNavigationThemeName($themeName)
    {
        assert('$themeName != ""');
        // uservar system takes care of dealing with anynomous
        xarModUserVars::set('themes', 'default_theme', $themeName);
    }

    /**
     * Get the user navigation locale
     *
     * 
     * @return string $locale users navigation locale name
     */
    static public function getNavigationLocale()
    {
        if (self::isLoggedIn())
        {
            $id = self::getVar('id');
              //last resort user is falling over on this uservar by setting multiple times
             //return true for last resort user - use default locale
             if ($id == XARUSER_LAST_RESORT) return true;
    
            $locale = xarModUserVars::get('roles', 'locale');
            if (empty($locale)) {
                $locale = xarSessionGetVar('navigationLocale');
            }
        } else {
            $locale = xarSessionGetVar('navigationLocale');
        }
        if (empty($locale)) {
            $locale = xarConfigVars::get(null, 'Site.MLS.DefaultLocale');
        }
        xarSessionSetVar('navigationLocale', $locale);
        return $locale;
    }

    /**
     * Set the user navigation locale
     *
     * 
     * @param  string $locale
     * @return boolean true if the navigation locale is set, false if not
     */
    static public function setNavigationLocale($locale)
    {
        if (xarMLSGetMode() != XARMLS_SINGLE_LANGUAGE_MODE) {
            xarSessionSetVar('navigationLocale', $locale);
            if (self::isLoggedIn()) {
                $userLocale = xarModUserVars::get('roles', 'locale');
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
     * Get a user variable
     *
     * 
     * @param  string  $name the name of the variable
     * @param  integer $userId integer the user to get the variable for
     * @return mixed the value of the user variable if the variable exists, void if the variable doesn't exist
     * @throws EmptyParameterException, NotLoggedInException, BadParameterException, IDNotFoundException
     * @todo <marco> #1 figure out why this check failsall the time now: if ($userId != xarSessionGetVar('role_id')) {
     * @todo <marco FIXME: ignoring unknown user variables for now...
     * @todo redesign the delegation to auth* modules for handling user variables
     * @todo add some security for getting to user variables (at least from another id)
     * @todo define clearly what the difference or similarity is with dd here
     */
    static public function getVar($name, $userId = NULL)
    {
        if (empty($name)) throw new EmptyParameterException('name');
    
        if (empty($userId)) $userId = xarSessionGetVar('role_id');
        //LEGACY
        if ($name == 'id' || $name == 'uid') return $userId;
    
        if ($userId == _XAR_ID_UNREGISTERED) {
            // Anonymous user => only id, name and uname allowed, for other variable names
            // an exception of type NOT_LOGGED_IN is raised
            // CHECKME: if we're going the route of moditemvars, this doesn need to be the case
            if ($name == 'name' || $name == 'uname') {
                return xarML('Anonymous');
            }
            throw new NotLoggedInException();
        }
    
        // Don't allow any module to retrieve passwords in this way
        if ($name == 'pass') throw new BadParameterException('name');
    
        if (!xarCoreCache::isCached('User.Variables.'.$userId, $name)) {
    
            if ($name == 'name' || $name == 'uname' || $name == 'email') {
                if ($userId == XARUSER_LAST_RESORT) {
                    return xarML('No Information'); // better return null here
                }
                // retrieve the item from the roles module
                $userRole = xarMod::apiFunc('roles',  'user',  'get',
                                           array('id' => $userId));
    
                if (empty($userRole) || $userRole['id'] != $userId) {
                    throw new IDNotFoundException($userId,'User identified by id #(1) does not exist.');
                }
    
                xarCoreCache::setCached('User.Variables.'.$userId, 'uname', $userRole['uname']);
                xarCoreCache::setCached('User.Variables.'.$userId, 'name', $userRole['name']);
                xarCoreCache::setCached('User.Variables.'.$userId, 'email', $userRole['email']);
    
            } elseif (!self::isVarDefined($name)) {
                if (xarModVars::get('roles',$name) || xarModVars::get('roles','set'.$name)) { //acount for optionals that need to be activated)
                    $value = xarModUserVars::get('roles',$name,$userId);
                    if ($value == null) {
                        xarCoreCache::setCached('User.Variables.'.$userId, $name, false);
                        // Here we can't raise an exception because they're all optional
                        $optionalvars=array('locale','timezone','usertimezone','userlastlogin',
                                            'userhome','primaryparent','passwordupdate');
                        //if ($name != 'locale' && $name != 'timezone') {
                        if (!in_array($name, $optionalvars)) {
                        // log unknown user variables to inform the site admin
                            $msg = xarML('User variable #(1) was not correctly registered', $name);
                            xarLog::message($msg, XARLOG_LEVEL_ERROR);
                        }
                        return;
                    } else {
                        xarCoreCache::setCached('User.Variables.'.$userId, $name, $value);
                    }
                }
    
            } else {
                // retrieve the user item
                $itemid = self::$objectRef->getItem(array('itemid' => $userId));
                if (empty($itemid) || $itemid != $userId) {
                    throw new IDNotFoundException($userId,'User identified by id #(1) does not exist.');
                }
    
                // save the properties
                $properties =& self::$objectRef->getProperties();
                foreach (array_keys($properties) as $key) {
                    if (isset($properties[$key]->value)) {
                        xarCoreCache::setCached('User.Variables.'.$userId, $key, $properties[$key]->value);
                    }
                }
            }
        }
    
        if (!xarCoreCache::isCached('User.Variables.'.$userId, $name)) {
            return false; //failure
        }
    
        $cachedValue = xarCoreCache::getCached('User.Variables.'.$userId, $name);
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
     * 
     * @param  string  $name  the name of the variable
     * @param  mixed   $value the value of the variable
     * @param  integer $userId integer user's ID
     * @return boolean true if the set was successful, false if validation fails
     * @throws EmptyParameterException, BadParameterException, NotLoggedInException, xarException, IDNotFoundException
     * @todo redesign the delegation to auth* modules for handling user variables
     * @todo some securitycheck for retrieving at least other users variables ?
     */
    static public function setVar($name, $value, $userId = null)
    {
        // check that $name is valid
        if (empty($name)) throw new EmptyParameterException('name');
        if ($name == 'id' || $name == 'authenticationModule' || $name == 'pass') {
            throw new BadParameterException('name');
        }
    
        if (empty($userId)) {
            $userId = xarSessionGetVar('role_id');
        }
        if ($userId == _XAR_ID_UNREGISTERED) {
            // Anonymous user
            throw new NotLoggedInException();
        }
    
        if ($name == 'name' || $name == 'uname' || $name == 'email') {
            // TODO: replace with some roles API
            // TODO: not -^ but get rid of this entirely here.
            self::setUsersTableUserVar($name, $value, $userId);
    
        } elseif (!self::isVarDefined($name)) {
            if (xarModVars::get('roles',$name)) {
                xarCoreCache::setCached('User.Variables.'.$userId, $name, false);
                throw new xarException($name,'User variable #(1) was not correctly registered');
            } else {
                xarModUserVars::set('roles',$name,$value,$userId);
            }
        } else {
            // retrieve the user item
            $itemid = self::$objectRef->getItem(array('itemid' => $userId));
            if (empty($itemid) || $itemid != $userId) {
                throw new IDNotFoundException($userId,'User identified by id "#(1)" does not exist.');
            }
    
            // check if we need to update the item
            if ($value != self::$objectRef->properties[$name]->value) {
                // validate the new value
                if (!self::$objectRef->properties[$name]->validateValue($value)) {
                    return false;
                }
                // update the item
                $itemid = self::$objectRef->updateItem(array($name => $value));
                if (!isset($itemid)) return; // throw back
            }
    
        }
    
        // Keep in sync the UserVariables cache
        xarCoreCache::setCached('User.Variables.'.$userId, $name, $value);
    
        return true;
    }

    /**
     * Compare Passwords
     *
     * 
     * @param  string $givenPassword  the password given for comparison
     * @param  string $realPassword   the reference password to compare to
     * @param  string $userName       name of the corresponding user?
     * @param  string $cryptSalt      ?
     * @return boolean true if the passwords match, false otherwise
     * @todo   weird duckling here
     * @todo   consider something strong than md5 here (not trivial wrt upgrading though)
     */
    static public function comparePasswords($givenPassword, $realPassword, $userName, $cryptSalt = '')
    {
        // TODO: consider moving to something stronger like sha1
        $md5pass = md5($givenPassword);
        if (strcmp($md5pass, $realPassword) == 0)
            // Huh? shouldn't this be true instead of the md5 ?
            return $md5pass;
    
        return false;
    }

    // PRIVATE FUNCTIONS
    
    /**
     * Get user's authentication module
     *
     * 
     * @param  userId string
     * @todo   what happens for anonymous users ???
     * @todo   check coherence 1 vs. 0 for Anonymous users !!!
     * @todo   this should be somewhere else probably (base class of auth* or roles mebbe)
     * @todo   is $userId a string? looks like an ID
     */
    static private function getAuthModule($userId)
    {
        if ($userId == xarSessionGetVar('role_id')) {
            $authModName = xarSessionGetVar('authenticationModule');
            if (isset($authModName)) {
                return $authModName;
            }
        }
    
        $dbconn   = xarDB::getConn();
        $xartable = xarDB::getTables();
    
        // Get user auth_module name
        $rolestable = $xartable['roles'];
        $modstable = $xartable['modules'];
    
        $query = "SELECT mods.name
                  FROM $modstable mods, $rolestable roles
                  WHERE mods.id = roles.auth_module_id AND
                        roles.id = ?";
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
     * 
     * @param  string $name name of the variable to check
     * @return boolean true if the variable is defined
     * @todo   rething this.
     */
    static private function isVarDefined($name)
    {
        // Retrieve the dynamic user object if necessary
        if (!isset(self::$objectRef) && xarModIsHooked('dynamicdata','roles')) {
            self::$objectRef = xarMod::apiFunc('dynamicdata', 'user', 'getobject',
                                                           array('module' => 'roles'));
            if (empty(self::$objectRef) || empty(self::$objectRef->objectid)) {
                self::$objectRef = false;
            }
        }
    
        // Check if this property is defined for the dynamic user object
        if (empty(self::$objectRef) || empty(self::$objectRef->properties[$name])) {
            return false;
        }
        return true;
    }
}
?>
