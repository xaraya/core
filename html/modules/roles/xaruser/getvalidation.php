<?php

/**
 * getvalidation validates a new user into the system
 * if their status is set to two.
 *
 * @param   uname users name
 * @param   valcode is the validation code sent to user on registration
 * @param   phase is the point in the function to return
 * @return  true if valcode matches valcode in user status table
 * @raise   exceptions raised valcode does not match
 */
function roles_user_getvalidation()
{
    // Security check
    if (!xarSecurityCheck('ViewRoles')) return;

    //If a user is already logged in, no reason to see this.
    //We are going to send them to their account.
    if (xarUserIsLoggedIn()) {
       xarResponseRedirect(xarModURL('roles',
                                     'user',
                                     'account',
                                      array('uid' => xarUserGetVar('uid'))));
       return true;
    }

    if (!xarVarFetch('uname','str:1:100',$uname,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('valcode','str:1:100',$valcode,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('sent','str:1:100',$sent,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('phase','str:1:100',$phase,'startvalidation',XARVAR_NOT_REQUIRED)) return;

    xarTplSetPageTitle(xarVarPrepForDisplay(xarML('Validate Your Account')));

    switch(strtolower($phase)) {

        case 'startvalidation':
        default:

            $data = xarTplModule('roles','user', 'startvalidation', array('phase'   => $phase,
                                                                          'uname'   => $uname,
                                                                          'sent'    => $sent,
                                                                          'valcode' => $valcode,
                                                                           'validatelabel' => xarML('Validate Your Account'),
                                                                           'resendlabel' => xarML('Resend Validation Information')));
            break;

        case 'getvalidate':

            // check for user and grab uid if exists
            $status = xarModAPIFunc('roles',
                                    'user',
                                    'get',
                                     array('uname' => $uname));

            // Check Validation codes to ensure a match.
            if ($valcode != $status['valcode']) {
                $msg = xarML('The validation codes do not match');
                xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
                return;
            }

            $pending = xarModGetVar('roles', 'welcomeemail');
            if ($pending == 1){

                // Update the user status table to reflect a pending account.
                if (!xarModAPIFunc('roles',
                                   'user',
                                   'updatestatus',
                                    array('uname' => $uname,
                                          'state' => '4'))) return;
            } else {
                // Update the user status table to reflect a validated account.
                if (!xarModAPIFunc('roles',
                                   'user',
                                   'updatestatus',
                                    array('uname' => $uname,
                                          'state' => '3'))) return;
            }


            // Send Welcome Email
            $sitename = xarModGetVar('themes', 'SiteName');
            $siteadmin = xarModGetVar('mail', 'adminname');
            $welcomeemail = xarModGetVar('roles', 'welcomeemail');
            $welcometitle = xarModGetVar('roles', 'welcometitle');

            $confemailsearch = array('/%%link%%/',
                                     '/%%name%%/',
                                     '/%%username%%/',
                                     '/%%sitename%%/',
                                     '/%%siteadmin%%/');

            $confemailreplace = array(xarServerGetBaseURL(),
                                      "$status[name]",
                                      "$status[uname]",
                                      "$sitename",
                                      "$siteadmin");

            $welcomeemail = preg_replace($confemailsearch,
                                         $confemailreplace,
                                         $welcomeemail);

            $welcometitle = preg_replace($confemailsearch,
                                         $confemailreplace,
                                         $welcometitle);

            // Send confirmation email
            if (!xarModAPIFunc('mail',
                               'admin',
                               'sendmail',
                               array('info' => $status['email'],
                                     'name' => $status['name'],
                                     'subject' => $welcometitle,
                                     'message' => $welcomeemail))) return;

            if (xarModGetVar('roles', 'sendnotice')){

                $adminname = xarModGetVar('mail', 'adminname');
                $adminemail = xarModGetVar('mail', 'adminmail');

                $message = "".xarML('A new user has registered.  Here are the details')." \n\n";
                $message .= "".xarML('Username')." = $status[name]\n";
                $message .= "".xarML('Email Address')." = $status[email]";

                $messagetitle = "".xarML('A New User Has Registered')."";

                if (!xarModAPIFunc('mail',
                                   'admin',
                                   'sendmail',
                                   array('info' => $adminemail,
                                         'name' => $adminname,
                                         'subject' => $messagetitle,
                                         'message' => $message))) return;
            }

            xarModSetVar('roles', 'lastuser', $username);

            $data = xarTplModule('roles','user', 'getvalidation');

            break;

        case 'resend':

            // check for user and grab uid if exists
            $status = xarModAPIFunc('roles',
                                    'user',
                                    'get',
                                    array('uname' => $uname));

            // Set up confirmation email
            $confemail = xarModGetVar('roles', 'confirmationemail');
            $conftitle = xarModGetVar('roles', 'confirmationtitle');

            $sitename = xarModGetVar('themes', 'SiteName');
            $siteadmin = xarModGetVar('mail', 'adminname');
            
            $baseurl = xarServerGetBaseURL();
            $confemailsearch = array('/%%link%%/',
                                     '/%%name%%/',
                                     '/%%username%%/',
                                     '/%%ipaddr%%/',
                                     '/%%sitename%%/',
                                     '/%%password%%/',
                                     '/%%siteadmin%%/',
                                     '/%%valcode%%/');

            $confemailreplace = array("".$baseurl."val.php?v=".$status['valcode']."&u=".$status['uid']."",
                                      "$status[name]",
                                      "$status[uname]",
                                      "".xarML('Cannot resend IP')."",
                                      "$sitename",
                                      "".xarML('Can Not Resend Password')."",
                                      "$siteadmin",
                                       $status['valcode']);

            $confemail = preg_replace($confemailsearch,
                                      $confemailreplace,
                                      $confemail);

            $conftitle = preg_replace($confemailsearch,
                                      $confemailreplace,
                                      $conftitle);

            // TODO Make HTML Message.
            // Send confirmation email
            if (!xarModAPIFunc('mail',
                               'admin',
                               'sendmail',
                               array('info' => $status['email'],
                                     'name' => $status['name'],
                                     'subject' => $conftitle,
                                     'message' => $confemail))) return;

            $data = xarTplModule('roles','user', 'getvalidation');

            // Redirect
            xarResponseRedirect(xarModURL('roles', 'user', 'getvalidation',array('sent' => 1)));

    }

    return $data;

}

?>