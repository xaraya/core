<?php

/**
 * Sends a new password to the user if they have forgotten theirs.
 *
 */
function roles_user_lostpassword()
{
    // Security check
    if (!xarSecurityCheck('ViewRoles')) return;

    //If a user is already logged in, no reason to see this.
    //We are going to send them to their account.
    if (xarUserIsLoggedIn()) {
        xarResponseRedirect(xarModURL('roles',
                                      'user',
                                      'account',
                                       array('uid' => $item['uid'])));
       return true;
    }

    xarTplSetPageTitle(xarModGetVar('themes', 'SiteName').' :: '.
                       xarVarPrepForDisplay(xarML('Users'))
               .' :: '.xarVarPrepForDisplay(xarML('Lost Password')));

    $phase = xarVarCleanFromInput('phase');

    if (empty($phase)){
        $phase = 'request';
    }

    switch(strtolower($phase)) {

        case 'request':
        default:
            $authid = xarSecGenAuthKey();
            $data = xarTplModule('roles','user', 'requestpw', array('authid'    => $authid));

            break;

        case 'send':

            list($uname,
                 $email) = xarVarCleanFromInput('uname',
                                                'email');

            // Confirm authorisation code.
            if (!xarSecConfirmAuthKey()) return;

            if ((empty($uname)) && (empty($email))) {
                $msg = xarML('You must enter your username or your email to proceed');
                xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
                return;
            }

            // check for user and grab uid if exists
            $user = xarModAPIFunc('roles',
                                  'user',
                                  'get',
                                   array('uname' => $uname,
                                         'email' => $email));

            if (empty($user)) {
                $msg = xarML('That email address or username is not registered');
                xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
                return;
            }
            // Make new password
            $pass = xarModAPIFunc('roles',
                                  'user',
                                  'makepass');

            if (empty($pass)) {
                $msg = xarML('Problem generating new password');
                xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
                return;
            }

            //Update user password
            // check for user and grab uid if exists
            $userupdate = xarModAPIFunc('roles',
                                        'admin',
                                        'update',
                                         array('uid'     => $user['uid'],
                                               'uname'   => $user['uname'],
                                               'name'    => $user['name'],
                                               'email'   => $user['email'],
                                               'state'   => $user['state'],
                                               'pass'    => $pass));

            if ($userupdate == false) return;

            // Send Email
            $sitename = xarModGetVar('themes', 'SiteName');
            $adminname = xarModGetVar('mail', 'adminname');

            $subject = "Replacement login information for $user[name] at $sitename";

            $message = "$user[name],\r\n\n";
            $message .= "Here is your new password for $sitename. You may now login to ".xarServerGetBaseURL() ." using the following username and password:\n\n";
            $message .= "username: $user[uname] \n";
            $message .= "password: $pass \n\n";
            $message .= "-- $adminname";

            if (!xarModAPIFunc('mail',
                               'admin',
                               'sendmail',
                               array('info' => $user['email'],
                                     'name' => $user['name'],
                                     'subject' => $subject,
                                     'message' => $message))) return;

            // Let user know that they have an email on the way.
            $data = xarTplModule('roles','user', 'requestpwconfirm');

            break;
    }

    return $data;
}

?>