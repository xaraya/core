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

    xarTplSetPageTitle(xarVarPrepForDisplay(xarML('Lost Password')));

    if (!xarVarFetch('phase','str:1:100',$phase,'request',XARVAR_NOT_REQUIRED)) return;

    switch(strtolower($phase)) {

        case 'request':
        default:
            $authid = xarSecGenAuthKey();
            $data = xarTplModule('roles','user', 'requestpw', array('authid'    => $authid,
                                                                    'emaillabel' => xarML('E-Mail New Password')));

            break;

        case 'send':

            if (!xarVarFetch('uname','str:1:100',$uname,'',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('email','str:1:100',$email,'',XARVAR_NOT_REQUIRED)) return;

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
            $sitename       = xarModGetVar('themes', 'SiteName');
            $adminname      = xarModGetVar('mail', 'adminname');
            $subject        = xarModGetVar('roles', 'remindertitle'); 
            $message        = xarModGetVar('roles', 'reminderemail');
            $htmlmessage    = xarModGetVar('roles', 'reminderemail');

            $search = array('/%%name%%/',
                            '/%%username%%/',
                            '/%%password%%/');

            $replace = array("$user[name]",
                             "$user[uname]",
                             "$pass");

            $subject         = preg_replace($search,
                                            $replace,
                                            $subject);

            $message         = preg_replace($search,
                                            $replace,
                                            $message);

            $htmlmessage     = preg_replace($search,
                                            $replace,
                                            $htmlmessage);

            if (!xarModAPIFunc('mail',
                               'admin',
                               'sendmail',
                               array('info'         => $user['email'],
                                     'name'         => $user['name'],
                                     'subject'      => $subject,
                                     'message'      => $message,
                                     'htmlmessage'  => $htmlmessage))) return;

            // Let user know that they have an email on the way.
            $data = xarTplModule('roles','user', 'requestpwconfirm');

            break;
    }

    return $data;
}

?>