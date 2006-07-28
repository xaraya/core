<?php
/**
 * Getvalidation validates a new user into the system
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * getvalidation validates a new user into the system
 * if their status is set to two.
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @author  Jo Dalle Nogare <jojodee@xaraya.com>
 * @param   uname users name
 * @param   valcode is the validation code sent to user on registration
 * @param   phase is the point in the function to return
 * @return  true if valcode matches valcode in user status table
 * @raise   exceptions raised valcode does not match
 * @TODO jojodee - validation process, duplication of functions and call to registration module needs to be rethought
 *         Rethink to provide cleaner separation between roles, authentication and registration
 */
function roles_user_getvalidation()
{
    // Security check
    if (!xarSecurityCheck('ViewRoles')) return;

    //If a user is already logged in, no reason to see this.
    //We are going to send them to their account.
    if (xarUserIsLoggedIn()) {
       xarResponseRedirect(xarModURL('roles', 'user', 'account',
                                      array('uid' => xarUserGetVar('uid'))));
       return true;
    }

    if (!xarVarFetch('uname','str:1:100',$uname,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('valcode','str:1:100',$valcode,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('sent','int:0:2',$sent,0,XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('phase','str:1:100',$phase,'startvalidation',XARVAR_NOT_REQUIRED)) return;

    xarTplSetPageTitle(xarML('Validate Your Account'));

    //Get default registration module info if any
    $defaultregdata=xarModAPIFunc('roles','user','getdefaultregdata');
    $regmodule=$defaultregdata['defaultregmodname'];
    $regmoduleactive=$defaultregdata['defaultregmodactive'];

    //Get default authentication module info if any
    $defaultauthdata=xarModAPIFunc('roles','user','getdefaultauthdata');
    $defaultloginmodname=$defaultauthdata['defaultloginmodname'];
    $authmodule=$defaultauthdata['defaultauthmodname'];

    //Set some general vars that we need in various options
    $pending = xarModGetVar($regmodule, 'explicitapproval');
    $loginlink =xarModURL($defaultloginmodname,'user','main');

    $tplvars=array();
    $tplvars['loginlink']=$loginlink;
    $tplvars['pending']=$pending;

    // Users can be newly registering, or existing users revalidating their accounts
    // Using this workaround to tell the difference until we can separate the processes further
    $newuser=false;
    if (isset($uname) && !empty($uname)) {
        // check for user and grab uid if exists
        $status = xarModAPIFunc('roles', 'user', 'get', array('uname' => $uname));
        $lastlogin =xarModGetUserVar('roles','userlastlogin',$status['uid']);
        if (!isset($lastlogin) || empty($lastlogin)) {
            $newuser=true;
        }
    } else {
       //they shouldn't be in here
    }
    switch(strtolower($phase)) {

        case 'startvalidation':
        default:
            $data = xarTplModule($regmodule,'user', 'startvalidation',
                                                    array('phase'   => $phase,
                                                          'uname'   => $uname,
                                                          'sent'    => $sent,
                                                          'valcode' => $valcode,
                                                          'validatelabel' => xarML('Validate Your Account'),
                                                          'resendlabel' => xarML('Resend Validation Information')));
            break;

        case 'getvalidate':

            // Trick the system when a user has double validated.
            if (empty($status['valcode'])){
                $data = xarTplModule('roles','user','getvalidation',$tplvars);
                    return $data;
            }

            // Check Validation codes to ensure a match.
            if ($valcode != $status['valcode']) {
                $msg = xarML('The validation codes do not match');
                xarErrorSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
                return;
            }

            //Process users that need to be approved first and put in status PENDING
            if ($pending == 1 && ($status['uid'] != xarModGetVar('roles','admin')))  {
                // Update the user status table to reflect a pending account.
                if (!xarModAPIFunc('roles', 'user', 'updatestatus',
                                    array('uname' => $uname,
                                          'state' => ROLES_STATE_PENDING)));

                //Do Pending email notifications
                //Only for existing users. New users are handled below with all new user notifications

                if (!$newuser && xarModGetVar('roles','askpendingemail')==1) {
                // Admin wants to be notified when accounts of existing users are set to pending
                    if (!xarModAPIFunc( 'roles', 'admin', 'sendpendingemail',
                                  array('uid'     => $status['uid'],
                                        'uname'    => $uname,
                                        'name'     => $status['name'],
                                        'email'    => $status['email']))) {
                      $msg = xarML('Problem sending pending email');
                      xarErrorSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
                    }
                }

            } else {// Update the user status table to reflect a validated account.

                if (!xarModAPIFunc('roles', 'user', 'updatestatus',
                                    array('uname' => $uname,
                                          'state' => ROLES_STATE_ACTIVE))) return;

                //send welcome email to the user
                if (xarModGetVar($regmodule, 'sendwelcomeemail')) {
                    if (!xarModAPIFunc('roles','admin','senduseremail',
                                    array('uid' => array($status['uid'] => '1'),
                                          'mailtype' => 'welcome'))) {

                        $msg = xarML('Problem sending welcome email');
                        xarErrorSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
                    }
                }

                //The user has validated their account and now is redirected to login
                $url = xarModUrl('roles', 'user', 'main');

                $time = '4';
                xarVarSetCached('Meta.refresh','url', $url);
                xarVarSetCached('Meta.refresh','time', $time);
            }
            
            //If we have registration of new user and the admin wants notificaton, let's send an email
            if ($regmoduleactive && (xarModGetVar($regmodule, 'sendnotice')==1) && $newuser){
                $terms= '';
                if (xarModGetVar($regmodule, 'showterms') == 1) {
                    // User has agreed to the terms and conditions.
                        $terms = xarML('This user has agreed to the site terms and conditions.');
                }

                $status = xarModAPIFunc('roles','user','get',array('uname' => $uname)); //check status as it may have changed

                $emailargs =  array('adminname'    => xarModGetVar('mail', 'adminname'),
                                    'adminemail'   => xarModGetVar('registration', 'notifyemail'),
                                    'userrealname' => $status['name'],
                                    'username'     => $status['uname'],
                                    'useremail'    => $status['email'],
                                    'terms'        => $terms,
                                    'uid'          => $status['uid'],
                                    'userstatus'   => $status['state']
                                    );
                if (!xarModAPIFunc('registration', 'user', 'notifyadmin', $emailargs)) {
                    return; // TODO ...something here if the email is not sent..
                }

            //else if this is an existing user who has just revalidated their email account,
            //let's send a different email to admin to tell them if they have requested this
            } elseif  (xarModGetVar('roles', 'requirevalidation') && !$newuser && xarModGetVar('roles','askwelcomeemail')) {
            //Note - we are taking advantage of user notification vars here - there is no separate admin vars for notifys
                $adminname = xarModGetVar('mail', 'adminname');
                $adminemail = xarModGetVar('mail', 'adminmail');
                $message = "".xarML('A user has revalidated their changed email address.  Here are the details')." \n\n";
                $message .= "".xarML('Username')." = $status[name]\n";
                $message .= "".xarML('Email Address')." = $status[email]";

                $messagetitle = "".xarML('A user has updated information')."";

                if (!xarModAPIFunc('mail', 'admin', 'sendmail',
                                   array('info' => $adminemail,
                                         'name' => $adminname,
                                         'subject' => $messagetitle,
                                         'message' => $message))) return;
            }

            xarModSetVar('roles', 'lastuser', $status['uid']);

            $data = xarTplModule('roles','user', 'getvalidation', $tplvars);

            break;

        case 'resend':
            // check for user and grab uid if exists
            $status = xarModAPIFunc('roles', 'user', 'get', array('uname' => $uname));

            if (!xarModAPIFunc( 'roles','admin','senduseremail',
                                array('uid' => array($status['uid'] => '1'),
                                      'mailtype' => 'confirmation',
                                      'ip' => xarML('Cannot resend IP'),
                                      'pass' => xarML('Can Not Resend Password')))) {

                    $msg = xarML('Problem resending confirmation email');
                    xarErrorSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
                }

            $data = xarTplModule('roles','user', 'getvalidation', $tplvars);

            // Redirect
            xarResponseRedirect(xarModURL('roles', 'user', 'getvalidation',array('sent' => 1)));

    }
    return $data;
}
?>