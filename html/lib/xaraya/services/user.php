<?php

/**
 * User available via methods (WIP)
 *
 * @package core\services
 * @subpackage services
 * @category Xaraya Web Applications Framework
 * @version 2.8.5
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author mikespub <mikespub@xaraya.com>
**/

namespace Xaraya\Services;

use ixarUser;
use BadParameterException;
use EmptyParameterException;
use IDNotFoundException;
use NotLoggedInException;
use SQLException;

/**
 * For documentation purposes only - available via UserTrait
 */
interface UserInterface extends ServiceInterface
{
    public const SLICE = 'user2';
    public const AUTH_FAILED = ixarUser::AUTH_FAILED;
    public const AUTH_DENIED = ixarUser::AUTH_DENIED;
    public const LAST_RESORT = ixarUser::LAST_RESORT;

    public function init(array $config = []): bool;
    public function getConfig(): array;
    public function getVar(string $varName): mixed;
    public function setVar(string $varName, mixed $value): bool;
    public function getId(): ?int;
    public function getName(): string;
    public function getUser(): string;
    public function getEmail(): string;
    public function isLoggedIn(): bool;
    public function isDebugAdmin(): bool;
    public function isSiteAdmin(): bool;
    public function getLocale(): mixed;
    public function setLocale(string $locale): bool;
    public function getThemeName(): mixed;
    public function setThemeName(string $themeName): void;
    public function logIn(string $userName, string $password, int $rememberMe = 0): bool;
    public function logOut(): bool;
    public function comparePasswords(string $givenPassword, string $realPassword, string $userName, string $cryptSalt = ''): bool;
    public function getAuthModules(): array;
    public function getCurrentId(): ?int;
    public function setCurrentId(int $userId): void;
    public function specialize(...$args): ServiceInterface;
}

/**
 * User available via methods
 */
trait UserTrait
{
    use ServiceTrait;

    protected ?int $currentId = null;
    private $objectRef;
    public $authenticationModules;
    protected bool $initialized = false;

    /**
     * Initialize service class
     * @param array<string, mixed> $config
     */
    public function init(array $config = []): bool
    {
        if (empty($config)) {
            if ($this->initialized) {
                return true;
            }
            $config = $this->getConfig();
        }
        $xar = $this->getParent();
        // User System and Security Service Tables
        $prefix = $xar->db()->getPrefix();

        // CHECKME: is this needed?
        $tables = [
            'roles'       => $prefix . '_roles',
            'realms'      => $prefix . '_security_realms',
            'rolemembers' => $prefix . '_rolemembers',
        ];

        $xar->db()->importTables($tables);

        $this->authenticationModules = $config['authenticationModules'];

        // @done update for each request context
        $xar->mls()->setCurrentLocale($this->getLocale());
        $xar->tpl()->setThemeName($this->getThemeName());

        $this->initialized = true;
        return true;
    }

    /**
     * Get configuration
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        $xar = $this->getParent();
        $systemArgs = ['authenticationModules' => $xar->config()->getVar('Site.User.AuthenticationModules')];
        return $systemArgs;
    }

    public function isLoaded(): bool
    {
        return $this->initialized;
    }

    /**
     * Get user variable
     */
    public function getVar(string $varName): mixed
    {
        $userId = $this->getCurrentId();
        if (empty($varName)) {
            throw new EmptyParameterException('name');
        }
        $xar = $this->getParent();

        // @todo see UserContext::getUserId() for userId without session
        if (empty($userId)) {
            $userId = $xar->session()->getUserId();
        }
        //LEGACY
        if ($varName == 'id' || $varName == 'uid') {
            return $userId;
        }

        if (empty($userId) || $userId == $xar->session()->getAnonId()) {
            // Anonymous user => only id, name and uname allowed, for other variable names
            // an exception of type NOT_LOGGED_IN is raised
            // CHECKME: if we're going the route of moditemvars, this doesn need to be the case
            if ($varName == 'name' || $varName == 'uname') {
                return $xar->mls()->translate('Anonymous');
            }
            throw new NotLoggedInException();
        }

        // Don't allow any module to retrieve passwords in this way
        if ($varName == 'pass') {
            throw new BadParameterException('name');
        }

        if (!$xar->mem()->has('User.Variables.' . $userId, $varName)) {

            if ($varName == 'name' || $varName == 'uname' || $varName == 'email') {
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

            } elseif (!$this->isVarDefined($varName)) {
                if ($xar->mod('roles')->getVar($varName) || $xar->mod('roles')->getVar('set' . $varName)) { //acount for optionals that need to be activated)
                    $value = $xar->mod('roles')->getUserVar($varName, $userId);
                    if ($value == null) {
                        $xar->mem()->set('User.Variables.' . $userId, $varName, false);
                        // Here we can't raise an exception because they're all optional
                        $optionalvars = ['locale','timezone','usertimezone','userlastlogin',
                            'userhome','primaryparent','passwordupdate'];
                        //if ($varName != 'locale' && $varName != 'timezone') {
                        if (!in_array($varName, $optionalvars)) {
                            // log unknown user variables to inform the site admin
                            $msg = $xar->mls()->translate('User variable #(1) was not correctly registered', $varName);
                            $xar->log()->error($msg);
                        }
                        return null;
                    } else {
                        $xar->mem()->set('User.Variables.' . $userId, $varName, $value);
                    }
                }

            } else {
                // retrieve the user item
                $itemid = $this->objectRef->getItem(['itemid' => $userId]);
                if (empty($itemid) || $itemid != $userId) {
                    throw new IDNotFoundException($userId, 'User identified by id #(1) does not exist.');
                }

                // save the properties
                $properties = & $this->objectRef->getProperties();
                foreach (array_keys($properties) as $key) {
                    if (isset($properties[$key]->value)) {
                        $xar->mem()->set('User.Variables.' . $userId, $key, $properties[$key]->value);
                    }
                }
            }
        }

        if (!$xar->mem()->has('User.Variables.' . $userId, $varName)) {
            return false; //failure
        }

        $cachedValue = $xar->mem()->get('User.Variables.' . $userId, $varName);
        if ($cachedValue === false) {
            // Variable already searched but doesn't exist and has no default
            return null;
        }

        return $cachedValue;
    }

    /**
     * Set user variable
     */
    public function setVar(string $varName, mixed $value): bool
    {
        $userId = $this->getCurrentId();
        // check that $varName is valid
        if (empty($varName)) {
            throw new EmptyParameterException('name');
        }
        if ($varName == 'id' || $varName == 'authenticationModule' || $varName == 'pass') {
            throw new BadParameterException('name');
        }
        $xar = $this->getParent();

        if (empty($userId)) {
            $userId = $xar->session()->getUserId();
        }
        if (empty($userId) || $userId == $xar->session()->getAnonId()) {
            // Anonymous user
            throw new NotLoggedInException();
        }

        if ($varName == 'name' || $varName == 'uname' || $varName == 'email') {
            // TODO: replace with some roles API
            // TODO: not -^ but get rid of this entirely here.
            //$this->setUsersTableUserVar($varName, $value, $userId);
            throw new BadParameterException('name');

        } elseif (!$this->isVarDefined($varName)) {
            if ($xar->mod('roles')->getVar($varName)) {
                $xar->mem()->set('User.Variables.' . $userId, $varName, false);
                throw new IDNotFoundException($varName, 'User variable #(1) was not correctly registered');
            } else {
                $xar->mod('roles')->setUserVar($varName, $value, $userId);
            }
        } else {
            // retrieve the user item
            $itemid = $this->objectRef->getItem(['itemid' => $userId]);
            if (empty($itemid) || $itemid != $userId) {
                throw new IDNotFoundException($userId, 'User identified by id "#(1)" does not exist.');
            }

            // check if we need to update the item
            if ($value != $this->objectRef->properties[$varName]->value) {
                // validate the new value
                if (!$this->objectRef->properties[$varName]->validateValue($value)) {
                    return false;
                }
                // update the item
                $itemid = $this->objectRef->updateItem([$varName => $value]);
                if (!isset($itemid)) {
                    return false;
                } // throw back
            }

        }

        // Keep in sync the UserVariables cache
        $xar->mem()->set('User.Variables.' . $userId, $varName, $value);

        return true;
    }

    /**
     * Get current userId from session (if any) or anonymous userId or null
     */
    public function getId(): ?int
    {
        $xar = $this->getParent();
        // @todo see UserContext::getUserId() for userId without session
        return $xar->session()->getUserId();
    }

    public function getName(): string
    {
        return $this->getVar('name');
    }

    public function getUser(): string
    {
        return $this->getVar('uname');
    }

    public function getEmail(): string
    {
        return $this->getVar('email');
    }

    public function isLoggedIn(): bool
    {
        // @todo see UserContext::getUserId() for userId without session
        $userId = $this->getCurrentId();
        $xar = $this->getParent();
        return !empty($userId) && ($userId != $xar->session()->getAnonId());
    }

    public function isDebugAdmin(): bool
    {
        $userId = $this->getCurrentId();
        $xar = $this->getParent();
        return in_array($userId, $xar->config()->getVar('Site.User.DebugAdmins'));
    }

    public function isSiteAdmin(): bool
    {
        $userId = $this->getCurrentId();
        $xar = $this->getParent();
        return $userId == $xar->mod('roles')->getVar('admin');
    }

    public function getLocale(): mixed
    {
        $xar = $this->getParent();
        if ($this->isLoggedIn()) {
            $id = $this->getVar('id');
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

    public function setLocale(string $locale): bool
    {
        $xar = $this->getParent();
        $xar->log()->info("Changing the navigation locale from " . $this->getLocale() . " to " . $locale);
        if ($xar->mls()->getMode() != $xar->mls()::SINGLE_LANGUAGE_MODE) {
            $xar->session()->setVar('navigationLocale', $locale);
            if ($this->isLoggedIn()) {
                $xar->mod('roles')->setUserVar('locale', $locale);
            }
            return true;
        }
        return false;
    }

    public function getThemeName(): mixed
    {
        $xar = $this->getParent();
        $themeName = $xar->tpl()->getThemeName();

        if ($this->isLoggedIn() && (bool) $xar->mod('themes')->getVar('enable_user_menu')) {
            $userThemeName = $xar->mod('themes')->getUserVar('default_theme');
            if ($userThemeName) {
                $themeName = $userThemeName;
            }
        }

        return $themeName;
    }

    public function setThemeName(string $themeName): void
    {
        $xar = $this->getParent();
        assert($themeName != "");
        // uservar system takes care of dealing with anynomous
        $xar->mod('themes')->setUserVar('default_theme', $themeName);
    }

    public function logIn(string $userName, string $password, int $rememberMe = 0): bool
    {
        if ($this->isLoggedIn()) {
            return true;
        }

        if (empty($userName)) {
            throw new EmptyParameterException('userName');
        }
        if (empty($password)) {
            throw new EmptyParameterException('password');
        }
        $xar = $this->getParent();

        $userId = self::AUTH_FAILED;
        $args = ['uname' => $userName, 'pass' => $password];

        $authModName = 'authsystem';
        $modId = 42;
        foreach ($this->authenticationModules as $authModName) {
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
                return false; // throw back
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
            return false;
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

        // @checkme set currentId here too?
        //$this->currentId = $userId;
        $this->getContext()?->setUserId($userId);

        // User logged in successfully, trigger the proper event with the new userid
        $xar->events()->notify('UserLogin', $userId, $this->getContext());
        $xar->session()->delVar('privilegeset');
        return true;
    }

    public function logOut(): bool
    {
        if (!$this->isLoggedIn()) {
            return true;
        }
        $xar = $this->getParent();
        // get the current userid before logging out
        $userId = $xar->session()->getUserId();

        // Reset user session information
        $anonId = $xar->session()->getAnonId();
        $res = $xar->session()->setUserInfo($anonId, 0);
        if (!isset($res)) {
            return false; // throw back
        }

        $xar->session()->delVar('authenticationModule');

        // @checkme set currentId here too?
        //$this->currentId = $anonId;
        $this->getContext()?->setUserId($anonId);

        // User logged out successfully, trigger the proper event with the old userid
        $xar->events()->notify('UserLogout', $userId, $this->getContext());

        $xar->session()->delVar('privilegeset');
        return true;
    }

    public function comparePasswords(string $givenPassword, string $realPassword, string $userName, string $cryptSalt = ''): bool
    {
        // TODO: consider moving to something stronger like sha1
        $md5pass = md5($givenPassword);
        if (strcmp($md5pass, $realPassword ?? '') == 0) {
            // Huh? shouldn't this be true instead of the md5 ?
            return true;
        }

        return false;
    }

    /**
     * Summary of getAuthModules
     * @return array<string>
     */
    public function getAuthModules(): array
    {
        return $this->authenticationModules;
    }

    protected function getAuthModule(int $userId): mixed
    {
        $xar = $this->getParent();
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
        $result->close();

        if (!$xar->mod()->apiLoad($authModName, 'user')) {
            return null;
        }

        return $authModName;
    }

    protected function isVarDefined(string $varName): bool
    {
        $xar = $this->getParent();
        // Retrieve the dynamic user object if necessary
        if (!isset($this->objectRef) && $xar->mod()->isHooked('dynamicdata', 'roles')) {
            $this->objectRef = $xar->data()->getObject(['module' => 'roles']);
            if (empty($this->objectRef) || empty($this->objectRef->objectid)) {
                $this->objectRef = false;
            }
        }

        // Check if this property is defined for the dynamic user object
        if (empty($this->objectRef) || empty($this->objectRef->properties[$varName])) {
            return false;
        }
        return true;
    }

    /**
     * Get current userId if overridden
     */
    public function getCurrentId(): ?int
    {
        return $this->currentId ?? $this->getId();
    }

    /**
     * Override current userId when called as $this->user($userId)->...
     * @param int $userId
     * @return void
     */
    public function setCurrentId(int $userId): void
    {
        $this->currentId = $userId;
    }

    /**
     * Create a specialized version of this service for a specific user ID.
     * @param mixed ...$args
     * @return ServiceInterface
     */
    public function specialize(...$args): ServiceInterface
    {
        $clone = clone $this;
        if (isset($args[0])) {
            $clone->setCurrentId($args[0]);
        }
        return $clone;
    }
}

/**
 * Access xarUser::* User methods (getVar, setVar, ...)
 *
 * Available methods:
 * - getVar()
 * - setVar()
 * - getId()
 * - isLoggedIn()
 * - isDebugAdmin()
 * - isSiteAdmin()
 * - ...
 *
 */
class UserService implements UserInterface
{
    use UserTrait;

    // @todo remove this when all specialize() methods are implemented
    public function __clone()
    {
        $this->currentId = null;
    }
}
