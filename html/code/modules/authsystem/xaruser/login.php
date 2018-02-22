<?php
/**
 * Log a user in to the system
 *
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/42.html
 */

/**
 * Log a user into the system
 * 
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param  void N/A
 * @return boolean|int Returns true if the user was logged in successfully.<br/>
 *                     Else it returns an error code:<br/>
 *                     0: Deleted User<br/>
 *                     1: Inactive User<br/>
 *                     2: Not Validated User
 */
function authsystem_user_login()
{
    if (!$_COOKIE) {
        return xarTpl::module('authsystem','user','errors',array('layout' => 'no_cookies'));
    }

    $unlockTime  = (int) xarSession::getVar('authsystem.login.lockedout');
    $lockouttime = xarModVars::get('authsystem','lockouttime')? xarModVars::get('authsystem','lockouttime') : 15;
    $lockouttries = xarModVars::get('authsystem','lockouttries') ? xarModVars::get('authsystem','lockouttries') : 3;

    if ((time() < $unlockTime) && (xarModVars::get('authsystem','uselockout') == true)) {
        return xarTpl::module('authsystem','user','errors',array('layout' => 'locked_out', 'lockouttime' => $lockouttime));
    }

    if (!xarVarFetch('uname','str:0:64',$uname,'',XARVAR_NOT_REQUIRED)) return;
    if (empty($uname))
        return xarTpl::module('authsystem','user','errors',array('layout' => 'missing_data', 'lockouttime' => $lockouttime));
    if (!xarVarFetch('pass','str:0:254',$pass,'',XARVAR_NOT_REQUIRED)) return;
    if (empty($pass))
        return xarTpl::module('authsystem','user','errors',array('layout' => 'missing_data', 'lockouttime' => $lockouttime));

    $redirect = xarServer::getBaseURL();
    if (!xarVarFetch('rememberme','checkbox',$rememberme,false,XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('redirecturl','str:1:254',$redirecturl,$redirect,XARVAR_NOT_REQUIRED)) return;

    // Defaults
    if (preg_match('/authsystem/',$redirecturl)) {
        $redirecturl = $redirect;
    }
    $redirecturl = xarVarPrepHTMLDisplay($redirecturl);
    $rememberme = xarVarPrepHTMLDisplay($rememberme);

    // Scan authentication modules and set user state appropriately
    $extAuthentication = false;
    foreach(xarUser::$authenticationModules as $authModName) {

       switch(strtolower($authModName)) {
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
                $lastresort=false;
                // Still need to check if user exists as the user may be
                // set to inactive in the user table
                //Get and check last resort first before going to db table
                $lastresortvalue=array();
                $lastresortvalue=xarModVars::get('privileges','lastresort');
                if (isset($lastresortvalue)) {
                    $secret = @unserialize(xarModVars::get('privileges','lastresort'));
                    if (is_array($secret)) {
                        if ($secret['name'] == MD5($uname) && $secret['password'] == MD5($pass)) {
                            $lastresort=true;
                            $state = xarRoles::ROLES_STATE_ACTIVE;
                            break; //let's go straight to login api
                        }
                    }
                }
                // check for user and grab id if exists
                $user = xarMod::apiFunc('roles','user','get', array('uname' => $uname));

                // Make sure we haven't already found authldap module
                if (empty($user) && ($extAuthentication == false))
                {
                    return xarTpl::module('authsystem','user','errors',array('layout' => 'bad_data'));
                } elseif (empty($user)) {
                    // Check if user has been deleted.
                    try {
                        $user = xarMod::apiFunc('roles','user','getdeleteduser',
                                                array('uname' => $uname));
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

    switch(strtolower($state)) {

        case xarRoles::ROLES_STATE_DELETED:

            // User is deleted by all means.  Return a message that says the same.
            return xarTpl::module('authsystem','user','errors',array('layout' => 'account_deleted'));
            break;

        case xarRoles::ROLES_STATE_INACTIVE:

            // User is inactive.  Return message stating.
            return xarTpl::module('authsystem','user','errors',array('layout' => 'account_inactive'));
            break;

        case xarRoles::ROLES_STATE_NOTVALIDATED:
            //User still must validate
            xarController::redirect(xarModURL('roles', 'user', 'getvalidation', array('uname' => $uname, 'valcode' => $pass, 'phase' => 'getvalidate')));
            break;

        case xarRoles::ROLES_STATE_ACTIVE:
        default:

            // User is active.

                // TODO: remove this when everybody has moved to 1.0
                if(!xarModVars::get('roles', 'lockdata')) {
                    $lockdata = array('roles' => array( array('id' => 4,
                                                              'name' => 'Administrators',
                                                              'notify' => TRUE)
                                                       ),
                                      'message' => '',
                                      'locked' => 0,
                                      'notifymsg' => '');
                    xarModVars::set('roles', 'lockdata', serialize($lockdata));
                }

            // Check if the site is locked and this user is allowed in
            $lockvars = unserialize(xarModVars::get('roles','lockdata'));
            if ($lockvars['locked'] == 1) {
                $rolesarray = array();
                $roles = $lockvars['roles'];
                for($i=0, $max = count($roles); $i < $max; $i++)
                        $rolesarray[] = xarRoles::get($roles[$i]['id']);
                $letin = array();
                foreach($rolesarray as $roletoletin) {
                    if ($roletoletin->isUser()) $letin[] = $roletoletin;
                    else $letin = array_merge($letin,$roletoletin->getUsers());
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
                    xarTpl::setPageTemplateName('locked');
                    return xarTpl::module('authsystem','user','errors',array('layout' => 'site_locked', 'message'  => $lockvars['message']));
                }
            }

            // Get the default authentication data - we need to check again as authsystem is always installed and users could get here direct
            $res = xarMod::apiFunc('authsystem','user','login',array('uname' => $uname, 'pass' => $pass, 'rememberme' => $rememberme));

            if ($res === NULL) return;
            elseif ($res == false) {
                // Problem logging in
                // TODO - work out flow, put in appropriate HTML

                // Cast the result to an int in case VOID is returned
                $attempts = (int) xarSession::getVar('authsystem.login.attempts');

                if (($attempts >= $lockouttries) && (xarModVars::get('authsystem','uselockout')==true)){
                    // Set the time for fifteen minutes from now
                    xarSession::setVar('authsystem.login.lockedout', time() + (60 * $lockouttime));
                    xarSession::setVar('authsystem.login.attempts', 0);
                    return xarTpl::module('authsystem','user','errors',array('layout' => 'bad_tries_exceeded', 'lockouttime' => $lockouttime));
                } else{
                    $newattempts = $attempts + 1;
                    xarSession::setVar('authsystem.login.attempts', $newattempts);
                    return xarTpl::module('authsystem','user','errors',array('layout' => 'bad_try', 'attempts' => $newattempts));
                }
            }
            //FR for last login - first capture the last login for this user
            $thislastlogin =xarModUserVars::get('roles','userlastlogin');
            if (!empty($thislastlogin)) {
                //move this to a session var for this user
                    xarSession::setVar('roles_thislastlogin',$thislastlogin);
            }
            xarModUserVars::set('roles','userlastlogin',time()); //this is what everyone else will see

            $externalurl=false; //used as a flag for userhome external url
            if(isset($redirecturl)) {
                $redirecturl = $redirecturl;
            } else {
                if ((bool)xarModVars::get('roles', 'loginredirect')) {
                    $truecurrenturl = xarServer::getCurrentURL(array(), false);
                    $url = xarMod::apiFunc('roles','user','getuserhome',array('itemid' => $user['id']));
                    if (empty($url)) {
                        $urldata['redirecturl'] = xarModURL(xarModVars::get('modules','defaultmodule'),xarModVars::get('modules','defaulttypename'),xarModVars::get('modules','defaultfuncname'));
                        $urldata['externalurl'] = false;
                    } else {
                        try {
                            $urldata = xarMod::apiFunc('roles','user','parseuserhome',array('url'=>$url,'truecurrenturl'=>$truecurrenturl));
                        } catch (Exception $e) {
                            return xarTpl::module('roles','user','errors',array('layout' => 'bad_userhome', 'message' => $e->getMessage()));
                        }
                    }
                    $data = array();
                    if (!is_array($urldata) || !$urldata) {
                        $externalurl = false;
                        $redirecturl = xarServer::getBaseURL();
    
                    } else{
                        $externalurl = $urldata['externalurl'];
                        $redirecturl = $urldata['redirecturl'];
                    }
                }
            }

            if ($externalurl) {
                /* Open in IFrame - works if you need it */
                /* $data['page'] = $redirecturl;
                   $data['title'] = xarML('Home Page');
                   return xarTpl::module('roles','user','homedisplay', $data);
                 */
                 xarController::redirect($redirecturl);
            } else {
                xarController::redirect($redirecturl);
            }

            return true;
            break;
        case xarRoles::ROLES_STATE_PENDING:

            // User is pending activation
                return xarTpl::module('authsystem','user','errors',array('layout' => 'account_pending'));
            break;
    }

    return true;

}
?>