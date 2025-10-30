<?php

/**
 * User System
 *
 * @package core\users
 * @category Xaraya Web Applications Framework
 * @version 2.8.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Jim McDonald
 * @author Marco Canini <marco@xaraya.com>
 * @author Marc Lutolf
 * @todo <marco> user status field
 */

sys::import('xaraya.services.xar');
use Xaraya\Services\xar;

/**
 * Exception raised by the users subsystem
 *
 * @package core\users
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
**/
class NotLoggedInException extends xarExceptions
{
    protected $message = 'An operation was encountered that requires the user to be logged in. If you are currently logged in please report this as a bug.';
}

/**
 * Authentication modules capabilities - moved to Xaraya\Authentication\Capability
 * (to be revised e.g. to differentiate read & update capability for core & dynamic)
 * @deprecated 2.8.1 not used in 2.4+ auth modules
 * define('XARUSER_AUTH_AUTHENTICATION', 1);
 * ...
 */

/**
 * User System
 * @package core\users
 */
class xarUser extends xarObject
{
    public const AUTH_FAILED = -1;
    public const AUTH_DENIED = -2;
    public const LAST_RESORT = -3;

    private static $objectRef;
    public static $authenticationModules;
    protected static bool $initialized = false;

    /**
     * Initialise the User System
     *
     *
     * @param array<string, mixed> $args[authenticationModules] array
     * @return boolean true on success
     */
    public static function init(array $args = [])
    {
        if (empty($args)) {
            if (self::$initialized) {
                return true;
            }
            $args = self::getConfig();
        }
        $xar = xar::getServicesClass();
        // User System and Security Service Tables
        $prefix = $xar->db()->getPrefix();

        // CHECKME: is this needed?
        $tables = [
            'roles'       => $prefix . '_roles',
            'realms'      => $prefix . '_security_realms',
            'rolemembers' => $prefix . '_rolemembers',
        ];

        $xar->db()->importTables($tables);

        self::$authenticationModules = $args['authenticationModules'];

        // @todo update for each request context
        $xar->mls()->setCurrentLocale(self::getNavigationLocale());
        $xar->tpl()->setThemeName(self::getNavigationThemeName());

        self::$initialized = true;
        return true;
    }

    public static function getConfig()
    {
        $systemArgs = ['authenticationModules' => xar::config()->getVar('Site.User.AuthenticationModules')];
        return $systemArgs;
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
     * @return boolean|void true if the user successfully logged in
     * @throws EmptyParameterException, SQLException
     * @todo <marco> #1 here we could also set a last_logon timestamp
     */
    public static function logIn($userName, $password, $rememberMe = 0, $context = null)
    {
        if (self::isLoggedIn()) {
            return true;
        }

        if (empty($userName)) {
            throw new EmptyParameterException('userName');
        }
        if (empty($password)) {
            throw new EmptyParameterException('password');
        }
        $xar = xar::getServicesClass();

        $userId = self::AUTH_FAILED;
        $args = ['uname' => $userName, 'pass' => $password];

        $authModName = 'authsystem';
        $modId = 42;
        foreach (self::$authenticationModules as $authModName) {
            // Bug #918 - If the module has been deactivated, then continue
            // checking with the next available authentication module
            if (!$xar->mod()->isAvailable($authModName)) {
                continue;
            }

            // Every authentication module must at least implement the
            // authentication interface so there's at least the authenticate_user
            // user api function
            if (!$xar->mod()->apiLoad($authModName, 'user')) {
                continue;
            }

            $modId = $xar->mod()->getID($authModName);

            // CHECKME: Does this raise an exception??? If so:
            // TODO: test with multiple auth modules and wrap in try/catch clause
            $userId = $xar->mod()->apiFunc($authModName, 'user', 'authenticate_user', $args);
            if (!isset($userId)) {
                return; // throw back
            } elseif ($userId != self::AUTH_FAILED) {
                // Someone authenticated the user or passed self::AUTH_DENIED
                break;
            }
        }
        if ($userId == self::AUTH_FAILED || $userId == self::AUTH_DENIED) {
            if ($xar->mod('privileges')->getVar('lastresort')) {
                $secret = unserialize((string) $xar->mod('privileges')->getVar('lastresort'));
                if ($secret['name'] == md5($userName) && $secret['password'] == md5($password)) {
                    $userId = self::LAST_RESORT;
                    $rememberMe = 0;
                }
            }
            if ($userId != self::LAST_RESORT) {
                return false;
            }
        }

        // Catch common variations (0, false, '', ...)
        if (empty($rememberMe)) {
            $rememberMe = 0;
        } else {
            $rememberMe = 1;
        }

        // Set user session information
        if (!$xar->session()->setUserInfo($userId, $rememberMe)) {
            return;
        } // throw back

        // Set user auth module information
        $dbconn   = $xar->db()->getConn();
        $xartable = $xar->db()->getTables();

        $rolestable = $xartable['roles'];

        // TODO: this should be inside roles module
        try {
            $dbconn->begin();
            $query = "UPDATE $rolestable SET auth_module_id = ? WHERE id = ?";
            $stmt = $dbconn->prepareStatement($query);
            $stmt->executeUpdate([$modId,$userId]);
            $dbconn->commit();
        } catch (SQLException $e) {
            $dbconn->rollback();
            throw $e;
        }

        // Set session variables

        // Keep a reference to auth module that authenticates successfully
        $xar->session()->setVar('authenticationModule', $authModName);

        // FIXME: <marco> here we could also set a last_logon timestamp
        //<jojodee> currently set in individual authsystem when success on login returned to it

        if (!empty($context)) {
            $context->setUserId($userId);
        }
        // User logged in successfully, trigger the proper event with the new userid
        xarEvents::notify('UserLogin', $userId, $context);
        $xar->session()->delVar('privilegeset');
        return true;
    }

    /**
     * Log the user out
     *
     *
     * @return boolean|void true if the user successfully logged out
     */
    public static function logOut($context = null)
    {
        if (!self::isLoggedIn()) {
            return true;
        }
        $xar = xar::getServicesClass();
        // get the current userid before logging out
        $userId = $xar->session()->getUserId();

        // Reset user session information
        $res = $xar->session()->setUserInfo($xar->session()->getAnonId(), 0);
        if (!isset($res)) {
            return; // throw back
        }

        $xar->session()->delVar('authenticationModule');

        if (!empty($context)) {
            $context->setUserId($xar->session()->getAnonId());
        }
        // User logged out successfully, trigger the proper event with the old userid
        xarEvents::notify('UserLogout', $userId, $context);

        $xar->session()->delVar('privilegeset');
        return true;
    }

    /**
     * Check if the user logged in
     *
     * @todo see UserContext::getUserId() for userId without session
     * @param ?int $userId
     * @return boolean true if the user is logged in, false if they are not
     */
    public static function isLoggedIn($userId = null)
    {
        $xar = xar::getServicesClass();
        $userId ??= $xar->session()->getUserId();
        return (!empty($userId) && $userId != $xar->session()->getAnonId());
    }

    /**
     * Is the user listed as debug admin
     * @param ?int $userId
     * @return bool
     */
    public static function isDebugAdmin($userId = null)
    {
        $xar = xar::getServicesClass();
        $userId ??= $xar->session()->getUserId();
        return in_array($userId, $xar->config()->getVar('Site.User.DebugAdmins'));
    }

    /**
     * Is the user defined as site admin (see roles module)
     * @param ?int $userId
     * @return bool
     */
    public static function isSiteAdmin($userId = null)
    {
        $xar = xar::getServicesClass();
        $userId ??= $xar->session()->getUserId();
        return $userId == $xar->mod('roles')->getVar('admin');
    }

    /**
     * Gets the user navigation theme name
     *
     *
     * @return string name of the users navigation theme
     */
    public static function getNavigationThemeName()
    {
        $xar = xar::getServicesClass();
        $themeName = $xar->tpl()->getThemeName();

        if (self::isLoggedIn() && (bool) $xar->mod('themes')->getVar('enable_user_menu')) {
            $userThemeName = $xar->mod('themes')->getUserVar('default_theme');
            if ($userThemeName) {
                $themeName = $userThemeName;
            }
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
    public static function setNavigationThemeName($themeName)
    {
        assert($themeName != "");
        // uservar system takes care of dealing with anynomous
        xar::mod('themes')->setUserVar('default_theme', $themeName);
    }

    /**
     * Get the user navigation locale
     *
     *
     * @return string|bool $locale users navigation locale name
     */
    public static function getNavigationLocale()
    {
        $xar = xar::getServicesClass();
        if (self::isLoggedIn()) {
            $id = self::getVar('id');
            //last resort user is falling over on this uservar by setting multiple times
            //return true for last resort user - use default locale
            if ($id == self::LAST_RESORT) {
                return true;
            }

            $locale = $xar->mod('roles')->getUserVar('locale', $id);
            if (empty($locale)) {
                $locale = $xar->session()->getVar('navigationLocale');
            }
        } else {
            $locale = $xar->session()->getVar('navigationLocale');
        }
        if (empty($locale)) {
            $locale = $xar->config()->getVar('Site.MLS.DefaultLocale');
        }
        $xar->session()->setVar('navigationLocale', $locale);
        return $locale;
    }

    /**
     * Set the user navigation locale
     *
     *
     * @param  string $locale
     * @return boolean true if the navigation locale is set, false if not
     */
    public static function setNavigationLocale($locale)
    {
        $xar = xar::getServicesClass();
        $xar->log()->info("Changing the navigation locale from " . self::getNavigationLocale() . " to " . $locale);
        if ($xar->mls()->getMode() != $xar->mls()::SINGLE_LANGUAGE_MODE) {
            $xar->session()->setVar('navigationLocale', $locale);
            if (self::isLoggedIn()) {
                $xar->mod('roles')->setUserVar('locale', $locale);
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
     * @todo <marco> #1 figure out why this check failsall the time now: if ($userId != xar::session()->getUserId()) {
     * @todo <marco FIXME: ignoring unknown user variables for now...
     * @todo redesign the delegation to auth* modules for handling user variables
     * @todo add some security for getting to user variables (at least from another id)
     * @todo define clearly what the difference or similarity is with dd here
     */
    public static function getVar($name, $userId = null)
    {
        if (empty($name)) {
            throw new EmptyParameterException('name');
        }
        $xar = xar::getServicesClass();

        // @todo see UserContext::getUserId() for userId without session
        if (empty($userId)) {
            $userId = $xar->session()->getUserId();
        }
        //LEGACY
        if ($name == 'id' || $name == 'uid') {
            return $userId;
        }

        if (empty($userId) || $userId == $xar->session()->getAnonId()) {
            // Anonymous user => only id, name and uname allowed, for other variable names
            // an exception of type NOT_LOGGED_IN is raised
            // CHECKME: if we're going the route of moditemvars, this doesn need to be the case
            if ($name == 'name' || $name == 'uname') {
                return $xar->mls()->translate('Anonymous');
            }
            throw new NotLoggedInException();
        }

        // Don't allow any module to retrieve passwords in this way
        if ($name == 'pass') {
            throw new BadParameterException('name');
        }

        if (!$xar->mem()->has('User.Variables.' . $userId, $name)) {

            if ($name == 'name' || $name == 'uname' || $name == 'email') {
                if ($userId == self::LAST_RESORT) {
                    return $xar->mls()->translate('No Information'); // better return null here
                }

                // Retrieve the item
                // Rather than use roles_userapi_get, we hard code this unique case
                // FIXME: Look at this again when we move to PDO
                $dbconn = $xar->db()->getConn();
                $tables = $xar->db()->getTables();
                $rolestable = $tables['roles'];
                $query = "SELECT * FROM " . $rolestable . " WHERE id = " . $userId;
                $result = $dbconn->Execute($query);

                // We want the result as an associative array
                // First get the field names
                $fields = [];
                $result->setFetchMode($xar->db()->getFetchAssoc());
                //                $result->next(); $result->previous();
                $result->first();
                if (!isset($result->fields)) {
                    $result->fields = [];
                }
                $numfields = count($result->fields);
                for ($i = 0;$i < $numfields;$i++) {
                    $tmp = array_slice($result->fields, $i, 1);
                    $namefield  = key($tmp);
                    $fields[$namefield]['name'] = strtolower($namefield);
                }
                $result->setFetchMode($xar->db()->getFetchNum());
                $result->first();
                //                $result->next(); $result->previous();

                // Now get the values
                $i = 0;
                $line = [];
                foreach ($fields as $key => $value) {
                    if (!empty($value['alias'])) {
                        $line[$value['alias']] = $result->fields[$i];
                    } elseif (!empty($value['name'])) {
                        $line[$value['name']] = $result->fields[$i];
                    } else {
                        $line[] = $result->fields[$i];
                    }
                    $i++;
                }
                $userRole = $line;

                if (empty($userRole) || $userRole['id'] != $userId) {
                    throw new IDNotFoundException($userId, 'User identified by id #(1) does not exist.');
                }

                $xar->mem()->set('User.Variables.' . $userId, 'uname', $userRole['uname']);
                $xar->mem()->set('User.Variables.' . $userId, 'name', $userRole['name']);
                $xar->mem()->set('User.Variables.' . $userId, 'email', $userRole['email']);

            } elseif (!self::isVarDefined($name)) {
                if ($xar->mod('roles')->getVar($name) || $xar->mod('roles')->getVar('set' . $name)) { //acount for optionals that need to be activated)
                    $value = $xar->mod('roles')->getUserVar($name, $userId);
                    if ($value == null) {
                        $xar->mem()->set('User.Variables.' . $userId, $name, false);
                        // Here we can't raise an exception because they're all optional
                        $optionalvars = ['locale','timezone','usertimezone','userlastlogin',
                            'userhome','primaryparent','passwordupdate'];
                        //if ($name != 'locale' && $name != 'timezone') {
                        if (!in_array($name, $optionalvars)) {
                            // log unknown user variables to inform the site admin
                            $msg = $xar->mls()->translate('User variable #(1) was not correctly registered', $name);
                            $xar->log()->error($msg);
                        }
                        return;
                    } else {
                        $xar->mem()->set('User.Variables.' . $userId, $name, $value);
                    }
                }

            } else {
                // retrieve the user item
                $itemid = self::$objectRef->getItem(['itemid' => $userId]);
                if (empty($itemid) || $itemid != $userId) {
                    throw new IDNotFoundException($userId, 'User identified by id #(1) does not exist.');
                }

                // save the properties
                $properties = & self::$objectRef->getProperties();
                foreach (array_keys($properties) as $key) {
                    if (isset($properties[$key]->value)) {
                        $xar->mem()->set('User.Variables.' . $userId, $key, $properties[$key]->value);
                    }
                }
            }
        }

        if (!$xar->mem()->has('User.Variables.' . $userId, $name)) {
            return false; //failure
        }

        $cachedValue = $xar->mem()->get('User.Variables.' . $userId, $name);
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
     * @return boolean|void true if the set was successful, false if validation fails
     * @throws EmptyParameterException, BadParameterException, NotLoggedInException, xarExceptions, IDNotFoundException
     * @todo redesign the delegation to auth* modules for handling user variables
     * @todo some securitycheck for retrieving at least other users variables ?
     */
    public static function setVar($name, $value, $userId = null)
    {
        // check that $name is valid
        if (empty($name)) {
            throw new EmptyParameterException('name');
        }
        if ($name == 'id' || $name == 'authenticationModule' || $name == 'pass') {
            throw new BadParameterException('name');
        }
        $xar = xar::getServicesClass();

        if (empty($userId)) {
            $userId = $xar->session()->getUserId();
        }
        if (empty($userId) || $userId == $xar->session()->getAnonId()) {
            // Anonymous user
            throw new NotLoggedInException();
        }

        if ($name == 'name' || $name == 'uname' || $name == 'email') {
            // TODO: replace with some roles API
            // TODO: not -^ but get rid of this entirely here.
            //self::setUsersTableUserVar($name, $value, $userId);
            throw new BadParameterException('name');

        } elseif (!self::isVarDefined($name)) {
            if ($xar->mod('roles')->getVar($name)) {
                $xar->mem()->set('User.Variables.' . $userId, $name, false);
                throw new IDNotFoundException($name, 'User variable #(1) was not correctly registered');
            } else {
                $xar->mod('roles')->setUserVar($name, $value, $userId);
            }
        } else {
            // retrieve the user item
            $itemid = self::$objectRef->getItem(['itemid' => $userId]);
            if (empty($itemid) || $itemid != $userId) {
                throw new IDNotFoundException($userId, 'User identified by id "#(1)" does not exist.');
            }

            // check if we need to update the item
            if ($value != self::$objectRef->properties[$name]->value) {
                // validate the new value
                if (!self::$objectRef->properties[$name]->validateValue($value)) {
                    return false;
                }
                // update the item
                $itemid = self::$objectRef->updateItem([$name => $value]);
                if (!isset($itemid)) {
                    return;
                } // throw back
            }

        }

        // Keep in sync the UserVariables cache
        $xar->mem()->set('User.Variables.' . $userId, $name, $value);

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
    public static function comparePasswords($givenPassword, $realPassword, $userName, $cryptSalt = '')
    {
        // TODO: consider moving to something stronger like sha1
        $md5pass = md5($givenPassword);
        if (strcmp($md5pass, $realPassword ?? '') == 0) {
            // Huh? shouldn't this be true instead of the md5 ?
            return true;
        }

        return false;
    }

    // PRIVATE FUNCTIONS

    /**
     * Get user's authentication module
     *
     *
     * @param  int $userId string
     * @todo   what happens for anonymous users ???
     * @todo   check coherence 1 vs. 0 for Anonymous users !!!
     * @todo   this should be somewhere else probably (base class of auth* or roles mebbe)
     * @todo   is $userId a string? looks like an ID
     */
    private static function getAuthModule($userId)
    {
        $xar = xar::getServicesClass();
        if ($userId == $xar->session()->getUserId()) {
            $authModName = $xar->session()->getVar('authenticationModule');
            if (isset($authModName)) {
                return $authModName;
            }
        }

        $dbconn   = $xar->db()->getConn();
        $xartable = $xar->db()->getTables();

        // Get user auth_module name
        $rolestable = $xartable['roles'];
        $modstable = $xartable['modules'];

        $query = "SELECT mods.name
                  FROM $modstable mods, $rolestable roles
                  WHERE mods.id = roles.auth_module_id AND
                        roles.id = ?";
        $stmt = & $dbconn->prepareStatement($query);
        $result = & $stmt->executeQuery([$userId], $xar->db()->getFetchNum());

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

        if (!$xar->mod()->apiLoad($authModName, 'user')) {
            return;
        }

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
    private static function isVarDefined($name)
    {
        $xar = xar::getServicesClass();
        // Retrieve the dynamic user object if necessary
        if (!isset(self::$objectRef) && $xar->mod()->isHooked('dynamicdata', 'roles')) {
            sys::import('modules.dynamicdata.class.objects.factory');
            self::$objectRef = DataObjectFactory::getObject(['module' => 'roles']);
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
