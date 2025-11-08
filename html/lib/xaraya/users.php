<?php

/**
 * User System
 *
 * @package core\users
 * @category Xaraya Web Applications Framework
 * @version 2.8.5
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
use Xaraya\Services\UserService;
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

interface ixarUser
{
    public const AUTH_FAILED = -1;
    public const AUTH_DENIED = -2;
    public const LAST_RESORT = -3;
}

/**
 * User System
 * @package core\users
 * @deprecated 2.8.5 use xar::user() instead
 */
class xarUser extends xarObject implements ixarUser
{
    protected static ?UserService $userService = null;

    protected static function user(): UserService
    {
        if (!isset(self::$userService)) {
            $xar = xar::getServicesClass();
            self::$userService = $xar->user();
        }
        return self::$userService;
    }

    /**
     * Initialise the User System
     *
     *
     * @param array<string, mixed> $args[authenticationModules] array
     * @return boolean true on success
     */
    public static function init(array $args = [])
    {
        // static cache for migration
        self::$userService = null;
        return self::user()->init($args);
    }

    public static function getConfig()
    {
        return self::user()->getConfig();
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
        return self::user()->logIn($userName, $password, $rememberMe);
    }

    /**
     * Log the user out
     *
     *
     * @return boolean|void true if the user successfully logged out
     */
    public static function logOut($context = null)
    {
        return self::user()->logOut();
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
        return self::user()->isLoggedIn();
    }

    /**
     * Is the user listed as debug admin
     * @param ?int $userId
     * @return bool
     */
    public static function isDebugAdmin($userId = null)
    {
        return self::user()->isDebugAdmin();
    }

    /**
     * Is the user defined as site admin (see roles module)
     * @param ?int $userId
     * @return bool
     */
    public static function isSiteAdmin($userId = null)
    {
        return self::user()->isSiteAdmin();
    }

    /**
     * Gets the user navigation theme name
     *
     *
     * @return string name of the users navigation theme
     */
    public static function getNavigationThemeName()
    {
        return self::user()->getThemeName();
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
        return self::user()->setThemeName($themeName);
    }

    /**
     * Get the user navigation locale
     *
     *
     * @return string|bool $locale users navigation locale name
     */
    public static function getNavigationLocale()
    {
        return self::user()->getLocale();
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
        return self::user()->setLocale($locale);
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
        return self::user()->getVar($name);
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
        return self::user()->setVar($name, $value);
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
        return self::user()->comparePasswords($givenPassword, $realPassword, $userName, $cryptSalt);
    }

    // PRIVATE FUNCTIONS

}
