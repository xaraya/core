<?php

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
 */
function roles_user_login()
{
    global $xarUser_authenticationModules;

    if (!xarVarFetch('uname','str:1:100',$uname)) {
        xarExceptionFree();
        $msg = xarML('You must provide a username.');
        xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
        return;
    }
    if (!xarVarFetch('pass','str:1:100',$pass)) {
        xarExceptionFree();
        $msg = xarML('You must provide a password.');
        xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
        return;
    }
    if (!xarVarFetch('rememberme','checkbox',$rememberme,false,XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('redirecturl','str:1:100',$redirecturl,'index.php',XARVAR_NOT_REQUIRED)) return;

    // Defaults
    if (preg_match('/roles/',$redirecturl)) {
        $redirecturl = 'index.php';
    }

    // Scan authentication modules and set user state appropriately
    $extAuthentication = false;
    foreach($xarUser_authenticationModules as $authModName) {

       switch(strtolower($authModName)) {

            case 'authldap':

                // The authldap module allows the admin to allow an
                // LDAP user to automatically login to Xaraya without
                // having a Xaraya user account in the roles table.
                // If the user is successfully retrieved from LDAP,
                // then a corresponding entry will be created in the
                // roles table.  So set the user state to allow for
                // login.
                $state = 3;
                $extAuthentication = true;
                break;

            case 'authimap':
            case 'authsso':

                // The authsso module delegates login authority to
                // web server (trusts the web server to authenticate
                // the user's credentials), just as authldap
                // delegates to an LDAP server. Behavior same as
                // described in authldap case.
                $state = 3;
                $extAuthentication = true;
                break;

            case 'authsystem':

                // Still need to check if user exists as the user may be
                // set to inactive in the user table

                // check for user and grab uid if exists
                $user = xarModAPIFunc('roles',
                            'user',
                            'get',
                           array('uname' => $uname));

                // Make sure we haven't already found authldap module
                if (empty($user) && ($extAuthentication == false))
                {
                    $msg = xarML('Problem logging in: Invalid username or password.');
                    xarExceptionSet(XAR_USER_EXCEPTION, 'LOGGIN_IN', new DefaultUserException($msg));
                    return;
                }

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

                break;
			default:
                // some other auth module is being used.  We're going to assume
                // that xaraya will be the slave to the other system and
                // if the user is successfully retrieved from that auth system,
                // then a corresponding entry will be created in the
                // roles table.  So set the user state to allow for
                // login.
                $state = 3;
                $extAuthentication = true;
                break;
        }
    }

    switch(strtolower($state)) {

        case '0':

            // User is deleted by all means.  Return a message that says the same.
            $msg = xarML('Your account has been terminated by your request or by the adminstrators discression.');
            xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            return;

            break;

        case '1':

            // User is inactive.  Return message stating.
            $msg = xarML('Your account has been marked as inactive.  Contact the adminstrator with further questions.');
            xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            return;

            break;

        case '2':

            // User has not validated.
            xarResponseRedirect(xarModURL('roles', 'user', 'getvalidation'));

            break;

        case '3':
        default:

            // User is active.
            // Log the user in
            $res = xarModAPIFunc('roles','user','login',array('uname' => $uname, 'pass' => $pass, 'rememberme' => $rememberme));
            if ($res === NULL) return;
            elseif ($res == false) {
                // Problem logging in
                // TODO - work out flow, put in appropriate HTML
                $msg = xarML('Problem logging in: Invalid username or password.');
                xarExceptionSet(XAR_USER_EXCEPTION, 'LOGGIN_IN', new DefaultUserException($msg));
                return;
            }

            xarResponseRedirect($redirecturl);
            return true;

            break;

        case '4':

            // User is pending activation
            $msg = xarML('Your account has not activated by the site administrator');
            xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            return;

            break;
    }

    return true;

}

?>
