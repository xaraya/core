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
 * @raise   exceptions raised if status is 0, 1, or 2
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
    if (!xarVarFetch('rememberme','checkbox',$rememberme,false,XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('redirecturl','str:1:100',$redirecturl,'index.php',XARVAR_NOT_REQUIRED)) return;

    // Defaults
    if (preg_match('/authsystem/',$redirecturl)) {
        $redirecturl = 'index.php';
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

                // Still need to check if user exists as the user may be
                // set to inactive in the user table
                //Get and check last resort first before going to db table
                if (xarModGetVar('privileges','lastresort')) {
                    $secret = unserialize(xarModGetVar('privileges','lastresort'));
                    if ($secret['name'] == MD5($uname) && $secret['password'] == MD5($pass)) {
                        $state = ROLES_STATE_ACTIVE;
                        break; //let's go straight to login api
                    }
                }
                // check for user and grab uid if exists
                $user = xarModAPIFunc('roles',
                            'user',
                            'get',
                           array('uname' => $uname));

                // Make sure we haven't already found authldap module
                if (empty($user) && ($extAuthentication == false))
                {
					throw new BadParameterException(null,xarML('Problem logging in: Invalid username or password.'));
                } elseif (empty($user)) {
                    // Check if user has been deleted.
                    $user = xarModAPIFunc('roles',
                                          'user',
                                          'getdeleteduser',
                                          array('uname' => $uname));
                    if (xarCurrentErrorType() == XAR_USER_EXCEPTION)
                    {
                        //getdeleteduser raised an exception
                        xarErrorFree();
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

            // User has not validated.
            xarResponseRedirect(xarModURL('registration', 'user', 'getvalidation'));

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
                    xarErrorSet(XAR_SYSTEM_MESSAGE,
                    'SITE_LOCKED',
                     new SystemMessage($lockvars['message']));
                     return;
                }
            }

            // Log the user in
            $res = xarModAPIFunc('authsystem','user','login',array('uname' => $uname, 'pass' => $pass, 'rememberme' => $rememberme));
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

            xarResponseRedirect($redirecturl);
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