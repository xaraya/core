<?php
/**
 * Log user in to system
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * log user in to system
 * Description of status
 * Status 0 = deleted user
 * Status 1 = inactive user
 * Status 2 = not validated user
 * Status 3 = actve user
 *
 * @param   uname users name
 * @param   pass user password
 * @param   rememberme session set to expire
 * @param   redirecturl page to return user if possible
 * @return  true if status is 3
 * @throws   exceptions raised if status is 0, 1, or 2
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 */
function authsystem_user_login()
{
    global $xarUser_authenticationModules;

    if (!$_COOKIE) {
        throw new BadParameterException(null,xarML('You must enable cookies on your browser to run Xaraya. Check the browser configuration options to make sure cookies are enabled, click on  the "Back" button of the browser and try again.'));
    }

    $unlockTime  = (int) xarSessionGetVar('authsystem.login.lockedout');
    $lockouttime=xarModGetVar('authsystem','lockouttime')? xarModGetVar('authsystem','lockouttime') : 15;
    $lockouttries =xarModGetVar('authsystem','lockouttries') ? xarModGetVar('authsystem','lockouttries') : 3;

    if ((time() < $unlockTime) && (xarModGetVar('authsystem','uselockout')==true)) {
        throw new ForbiddenOperationException($lockouttime,xarML('Your account has been locked for #(1) minutes.'));
    }

    if (!xarVarFetch('uname','str:1:100',$uname)) {
        throw new EmptyParameterException('username');
    }
    if (!xarVarFetch('pass','str:1:100',$pass)) {
        throw new EmptyParameterException('password');
    }
    $redirect=xarServerGetBaseURL();
    if (!xarVarFetch('rememberme','checkbox',$rememberme,false,XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('redirecturl','str:1:254',$redirecturl,$redirect,XARVAR_NOT_REQUIRED)) return;

    // Defaults
    if (preg_match('/authsystem/',$redirecturl)) {
        $redirecturl = $redirect;
    }

    // Scan authentication modules and set user state appropriately
    $extAuthentication = false;
    foreach($xarUser_authenticationModules as $authModName) {

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
                $state =ROLES_STATE_ACTIVE;
                $extAuthentication = true;
                break;

            case 'authimap':
            case 'authsso':

                // The authsso module delegates login authority to
                // web server (trusts the web server to authenticate
                // the user's credentials), just as authldap
                // delegates to an LDAP server. Behavior same as
                // described in authldap case.
                $state = ROLES_STATE_ACTIVE;
                $extAuthentication = true;
                break;

            case 'authsystem':
                //Set a $lastresort flag var
                $lastresort=false;
                // Still need to check if user exists as the user may be
                // set to inactive in the user table
                //Get and check last resort first before going to db table
                $lastresortvalue=array();
                $lastresortvalue=xarModGetVar('privileges','lastresort');
                if (isset($lastresortvalue)) {
                    $secret = @unserialize(xarModGetVar('privileges','lastresort'));
                    if (is_array($secret)) {
                        if ($secret['name'] == MD5($uname) && $secret['password'] == MD5($pass)) {
                            $lastresort=true;
                            $state = ROLES_STATE_ACTIVE;
                            break; //let's go straight to login api
                        }
                    }
                }
                // check for user and grab uid if exists
                $user = xarModAPIFunc('roles','user','get', array('uname' => $uname));

                // Make sure we haven't already found authldap module
                if (empty($user) && ($extAuthentication == false))
                {
                    throw new BadParameterException(null,xarML('Problem logging in: Invalid username or password.'));
                } elseif (empty($user)) {
                    // Check if user has been deleted.
                    try {
                        $user = xarModAPIFunc('roles','user','getdeleteduser',
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
                $state = ROLES_STATE_ACTIVE;
                $extAuthentication = true;
                break;
        }
    }

    switch(strtolower($state)) {

        case ROLES_STATE_DELETED:

            // User is deleted by all means.  Return a message that says the same.
            throw new ForbiddenOperationException(null,xarML('Your account has been terminated by your request or at the adminstrator\'s discretion.'));
            break;

        case ROLES_STATE_INACTIVE:

            // User is inactive.  Return message stating.
                throw new ForbiddenOperationException(null,xarML('Your account has been marked as inactive.  Contact the adminstrator with further questions.'));
            break;

        case ROLES_STATE_NOTVALIDATED:
            //User still must validate
            xarResponseRedirect(xarModURL('roles', 'user', 'getvalidation'));

            break;

        case ROLES_STATE_ACTIVE:
        default:

            // User is active.

                // TODO: remove this when everybody has moved to 1.0
                if(!xarModGetVar('roles', 'lockdata')) {
                    $lockdata = array('roles' => array( array('uid' => 4,
                                                              'name' => 'Administrators',
                                                              'notify' => TRUE)
                                                       ),
                                      'message' => '',
                                      'locked' => 0,
                                      'notifymsg' => '');
                    xarModSetVar('roles', 'lockdata', serialize($lockdata));
                }

            // Check if the site is locked and this user is allowed in
            $lockvars = unserialize(xarModGetVar('roles','lockdata'));
            if ($lockvars['locked'] ==1) {
                $rolesarray = array();
                $rolemaker = new xarRoles();
                $roles = $lockvars['roles'];
                for($i=0, $max = count($roles); $i < $max; $i++)
                        $rolesarray[] = $rolemaker->getRole($roles[$i]['uid']);
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
                    // This is *not* an error condition, consider making a template
                    throw new GeneralException(null,$lockvars['message']);
                }
            }


            // Get the default authentication data - we need to check again as authsystem is always installed and users could get here direct
            $defaultauthdata=xarModAPIFunc('roles','user','getdefaultauthdata');
            $defaultloginmodname=$defaultauthdata['defaultloginmodname'];
            $res = xarModAPIFunc($defaultloginmodname,'user','login',array('uname' => $uname, 'pass' => $pass, 'rememberme' => $rememberme));

            if ($res === NULL) return;
            elseif ($res == false) {
                // Problem logging in
                // TODO - work out flow, put in appropriate HTML

                // Cast the result to an int in case VOID is returned
                $attempts = (int) xarSessionGetVar('authsystem.login.attempts');

                if (($attempts >= $lockouttries) && (xarModGetVar('authsystem','uselockout')==true)){
                    // set the time for fifteen minutes from now
                    xarSessionSetVar('authsystem.login.lockedout', time() + (60 * $lockouttime));
                    xarSessionSetVar('authsystem.login.attempts', 0);
                    throw new ForbiddenOperationException($lockouttime,xarML('Problem logging in: Invalid username or password.  Your account has been locked for #(1) minutes.'));
                } else{
                    $newattempts = $attempts + 1;
                    xarSessionSetVar('authsystem.login.attempts', $newattempts);
                    throw new ForbiddenOperationException($newattempts,xarML('Problem logging in: Invalid username or password.  You have tried to log in #(1) times.'));
                    return;
                }
            }
            //FR for last login - first capture the last login for this user
            $thislastlogin =xarModGetUserVar('roles','userlastlogin');
            if (!empty($thislastlogin)) {
                //move this to a session var for this user
                    xarSessionSetVar('roles_thislastlogin',$thislastlogin);
            }
            xarModSetUserVar('roles','userlastlogin',time()); //this is what everyone else will see

            $externalurl=false; //used as a flag for userhome external url
            if (xarModGetVar('roles', 'loginredirect')) { //only redirect to home page if this option is set
                if (xarModGetVar('roles', 'setuserhome')) {
                    $truecurrenturl = xarServerGetCurrentURL(array(), false);
                    $role = xarUFindRole($uname);
                    $url = $lastresort ? '[base]' : $role->getHome();
                    if (!isset($url) || empty($url)) {
                       //jojodee - we now have primary parent implemented so can use this if activated
                       if (xarModGetVar('roles','setprimaryparent')) { //primary parent is activated
                          //TODO: we should really take this out and do this once somewhere for use in other cases
                           $primaryparent = $role->getPrimaryParent();
                           $primaryparentrole = xarUFindRole($primaryparent);
                           $parenturl = $primaryparentrole->getHome();
                           if (!empty($parenturl)) $url= $parenturl;
                       } else {
                           // take the first home url encountered.
                           // TODO: what would be a more logical choice?
                            foreach ($role->getParents() as $parent) {
                                $parenturl = $parent->getHome();
                                if (!empty($parenturl))  {
                                    $url = $parenturl;
                                    break;
                                }
                            }
                        }
                    }

                    /* move the half page of code out to a Roles function. No need to repeat everytime it's used*/
                    $urldata=xarModAPIFunc('roles','user','userhome',array('url'=>$url,'truecurrenturl'=>$truecurrenturl));
                    $data=array();
                    if (!is_array($urldata) || !$urldata) {
                        $externalurl=false;
                        $redirecturl=xarServerGetBaseURL();
                    } else{
                        $externalurl=$urldata['externalurl'];
                        $redirecturl=$urldata['redirecturl'];
                    }
                }
            } //end get homepage redirect data
            if ($externalurl) {
                /* Open in IFrame - works if you need it */
                /* $data['page'] = $redirecturl;
                   $data['title'] = xarML('Home Page');
                   return xarTplModule('roles','user','homedisplay', $data);
                 */
                 xarResponseRedirect($redirecturl);
            }else {
                xarResponseRedirect($redirecturl);
            }

            return true;
            break;
        case ROLES_STATE_PENDING:

            // User is pending activation
                    throw new ForbiddenOperationException(null,xarML('Your account has not yet been activated by the site administrator'));
            break;
    }

    return true;

}
?>
