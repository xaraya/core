<?php

/**
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Authsystem\UserGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Authsystem\UserGui;
use Xaraya\Modules\Authsystem\UserApi;
use Exception;
use xarExceptions;
use xarRoles;
use sys;

sys::import('xaraya.modules.method');

/**
 * authsystem user login function
 * @extends MethodClass<UserGui>
 */
class LoginMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Log a user into the system
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args when called from elsewhere
     * @return mixed Returns true if the user was logged in successfully.<br/>
     * Else it returns an error code:<br/>
     * 0: Deleted User<br/>
     * 1: Inactive User<br/>
     * 2: Not Validated User
     * @see UserGui::login()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        if (empty($args) && !$_COOKIE) {
            return $this->tpl()->module('authsystem', 'user', 'errors', ['layout' => 'no_cookies']);
        }

        $unlockTime  = (int) $this->session()->getVar('authsystem.login.lockedout');
        $lockouttime = $this->mod()->getVar('lockouttime') ? $this->mod()->getVar('lockouttime') : 15;
        $lockouttries = $this->mod()->getVar('lockouttries') ? $this->mod()->getVar('lockouttries') : 3;

        if ((time() < $unlockTime) && ($this->mod()->getVar('uselockout') == true)) {
            return $this->tpl()->module('authsystem', 'user', 'errors', ['layout' => 'locked_out', 'lockouttime' => $lockouttime]);
        }

        extract($args);

        $this->var()->find('uname', $uname, 'str:0:64', '');
        if (empty($uname)) {
            return $this->tpl()->module('authsystem', 'user', 'errors', ['layout' => 'missing_data', 'lockouttime' => $lockouttime]);
        }
        $this->var()->find('pass', $pass, 'str:0:254', '');
        if (empty($pass)) {
            return $this->tpl()->module('authsystem', 'user', 'errors', ['layout' => 'missing_data', 'lockouttime' => $lockouttime]);
        }

        $redirect = $this->ctl()->getBaseURL();
        $this->var()->find('rememberme', $rememberme, 'checkbox', false);
        $this->var()->find('redirecturl', $redirecturl, 'str:1:254', $redirect);

        // Defaults
        if (preg_match('/authsystem/', $redirecturl)) {
            $redirecturl = $redirect;
        }
        $redirecturl = $this->prep()->html($redirecturl);
        $rememberme = $this->prep()->html($rememberme);

        // Scan authentication modules and set user state appropriately
        $extAuthentication = false;
        foreach ($this->user()->getAuthModules() as $authModName) {

            switch (strtolower($authModName)) {
                // Ooof, didn't realize we were doing this.  We really need a hook here.
                case 'authldap':

                    // The authldap module allows the admin to allow an
                    // LDAP user to automatically login to Xaraya without
                    // having a Xaraya user account in the roles table.
                    // If the user is successfully retrieved from LDAP,
                    // then a corresponding entry will be created in the
                    // roles table.  So set the user state to allow for
                    // login.
                    $state = xarRoles::ROLES_STATE_ACTIVE;
                    $extAuthentication = true;
                    break;

                case 'authimap':
                case 'authsso':

                    // The authsso module delegates login authority to
                    // web server (trusts the web server to authenticate
                    // the user's credentials), just as authldap
                    // delegates to an LDAP server. Behavior same as
                    // described in authldap case.
                    $state = xarRoles::ROLES_STATE_ACTIVE;
                    $extAuthentication = true;
                    break;

                case 'authsystem':
                    //Set a $lastresort flag var
                    $lastresort = false;
                    // Still need to check if user exists as the user may be
                    // set to inactive in the user table
                    //Get and check last resort first before going to db table
                    $lastresortvalue = [];
                    $lastresortvalue = $this->mod('privileges')->getVar('lastresort');
                    if (isset($lastresortvalue)) {
                        $secret = @unserialize((string) $this->mod('privileges')->getVar('lastresort'));
                        if (is_array($secret)) {
                            if ($secret['name'] == MD5($uname) && $secret['password'] == MD5($pass)) {
                                $lastresort = true;
                                $state = xarRoles::ROLES_STATE_ACTIVE;
                                break; //let's go straight to login api
                            }
                        }
                    }
                    // check for user and grab id if exists
                    $user = $this->mod()->apiFunc('roles', 'user', 'get', ['uname' => $uname]);

                    // Make sure we haven't already found authldap module
                    if (empty($user) && ($extAuthentication == false)) {
                        return $this->tpl()->module('authsystem', 'user', 'errors', ['layout' => 'bad_data']);
                    } elseif (empty($user)) {
                        // Check if user has been deleted.
                        try {
                            $user = $this->mod()->apiFunc(
                                'roles',
                                'user',
                                'getdeleteduser',
                                ['uname' => $uname]
                            );
                        } catch (xarExceptions $e) {
                            //getdeleteduser raised an exception
                        }
                    }

                    if (!empty($user)) {
                        $rolestate = $user['state'];
                        // If external authentication has already been set but
                        // the Xaraya users table has a different state (ie invalid)
                        // then override the external state
                        if (($extAuthentication == true) && ($state != $rolestate)) {
                            $state = $rolestate;
                        } else {
                            // No external authentication, so set state
                            $state = $rolestate;
                        }
                    }

                    break;
                default:
                    // some other auth module is being used.  We're going to assume
                    // that xaraya will be the slave to the other system and
                    // if the user is successfully retrieved from that auth system,
                    // then a corresponding entry will be created in the
                    // roles table.  So set the user state to allow for
                    // login.
                    $state = xarRoles::ROLES_STATE_ACTIVE;
                    $extAuthentication = true;
                    break;
            }
        }

        switch (strtolower($state)) {

            case xarRoles::ROLES_STATE_DELETED:

                // User is deleted by all means.  Return a message that says the same.
                return $this->tpl()->module('authsystem', 'user', 'errors', ['layout' => 'account_deleted']);

            case xarRoles::ROLES_STATE_INACTIVE:

                // User is inactive.  Return message stating.
                return $this->tpl()->module('authsystem', 'user', 'errors', ['layout' => 'account_inactive']);

            case xarRoles::ROLES_STATE_NOTVALIDATED:
                //User still must validate
                $this->ctl()->redirect($this->ctl()->getModuleURL('roles', 'user', 'getvalidation', ['uname' => $uname, 'valcode' => $pass, 'phase' => 'getvalidate']));
                return true;

            case xarRoles::ROLES_STATE_ACTIVE:
            default:

                // User is active.

                // Check if the site is locked and this user is allowed in
                $lockvars = unserialize((string) $this->mod('roles')->getVar('lockdata'));
                if ($lockvars['locked'] == 1) {
                    $rolesarray = [];
                    $roles = $lockvars['roles'];
                    for ($i = 0, $max = count($roles); $i < $max; $i++) {
                        $rolesarray[] = xarRoles::get($roles[$i]['id']);
                    }
                    $letin = [];
                    foreach ($rolesarray as $roletoletin) {
                        // If this is a user, add it to the list
                        if ($roletoletin->isUser()) {
                            $letin[] = $roletoletin;
                        }
                        // If this is a group, add its users to the list
                        else {
                            $letin = array_merge($letin, $roletoletin->getUsers());
                        }
                    }
                    $letthru = false;
                    foreach ($letin as $roletoletin) {
                        if (strtolower($uname) == strtolower($roletoletin->getUser())) {
                            $letthru = true;
                            break;
                        }
                    }

                    if (!$letthru) {
                        // If there is a locked.xt page then use that, otherwise show the default.xt page
                        $this->tpl()->setPageTemplateName('locked');
                        return $this->tpl()->module('authsystem', 'user', 'errors', ['layout' => 'site_locked', 'message'  => $lockvars['message']]);
                    }
                }

                // Get the default authentication data - we need to check again as authsystem is always installed and users could get here direct
                $res = $userapi->login(['uname' => $uname, 'pass' => $pass, 'rememberme' => $rememberme]);

                if ($res === null) {
                    return;
                } elseif ($res == false) {
                    // Problem logging in
                    // TODO - work out flow, put in appropriate HTML

                    // Cast the result to an int in case VOID is returned
                    $attempts = (int) $this->session()->getVar('authsystem.login.attempts');

                    if (($attempts >= $lockouttries) && ($this->mod()->getVar('uselockout') == true)) {
                        // Set the time for fifteen minutes from now
                        $this->session()->setVar('authsystem.login.lockedout', time() + (60 * $lockouttime));
                        $this->session()->setVar('authsystem.login.attempts', 0);
                        return $this->tpl()->module('authsystem', 'user', 'errors', ['layout' => 'bad_tries_exceeded', 'lockouttime' => $lockouttime]);
                    } else {
                        $newattempts = $attempts + 1;
                        $this->session()->setVar('authsystem.login.attempts', $newattempts);
                        return $this->tpl()->module('authsystem', 'user', 'errors', ['layout' => 'bad_try', 'attempts' => $newattempts]);
                    }
                }
                //FR for last login - first capture the last login for this user
                $thislastlogin = $this->mod('roles')->getUserVar('userlastlogin');
                if (!empty($thislastlogin)) {
                    //move this to a session var for this user
                    $this->session()->setVar('roles_thislastlogin', $thislastlogin);
                }
                $this->mod('roles')->setUserVar('userlastlogin', time()); //this is what everyone else will see

                $externalurl = false; //used as a flag for userhome external url
                if (isset($redirecturl)) {
                    //$redirecturl = $redirecturl;
                } else {
                    if ((bool) $this->mod('roles')->getVar('loginredirect')) {
                        $truecurrenturl = $this->ctl()->getCurrentURL([], false);
                        $url = $this->mod()->apiFunc('roles', 'user', 'getuserhome', ['itemid' => $user['id']]);
                        if (empty($url)) {
                            $urldata['redirecturl'] = $this->ctl()->getModuleURL($this->mod('modules')->getVar('defaultmodule'), $this->mod('modules')->getVar('defaulttypename'), $this->mod('modules')->getVar('defaultfuncname'));
                            $urldata['externalurl'] = false;
                        } else {
                            try {
                                $urldata = $this->mod()->apiFunc('roles', 'user', 'parseuserhome', ['url' => $url,'truecurrenturl' => $truecurrenturl]);
                            } catch (Exception $e) {
                                return $this->tpl()->module('roles', 'user', 'errors', ['layout' => 'bad_userhome', 'message' => $e->getMessage()]);
                            }
                        }
                        $data = [];
                        if (!is_array($urldata) || !$urldata) {
                            $externalurl = false;
                            $redirecturl = $this->ctl()->getBaseURL();

                        } else {
                            $externalurl = $urldata['externalurl'];
                            $redirecturl = $urldata['redirecturl'];
                        }
                    }
                }

                if ($externalurl) {
                    /* Open in IFrame - works if you need it */
                    /* $data['page'] = $redirecturl;
                       $data['title'] = $this->ml('Home Page');
                       return $this->tpl()->module('roles','user','homedisplay', $data);
                     */
                    $this->ctl()->redirect($redirecturl);
                } else {
                    $this->ctl()->redirect($redirecturl);
                }

                return true;

            case xarRoles::ROLES_STATE_PENDING:

                // User is pending activation
                return $this->tpl()->module('authsystem', 'user', 'errors', ['layout' => 'account_pending']);
        }

    }
}
