<?php
/**
 * File: $Id$
 *
 * Validate a new user into the system
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
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

    xarTplSetPageTitle(xarML('Validate Your Account'));

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

            // Trick the system when a user has double validated.
            if (empty($status['valcode'])){
                $data = xarTplModule('roles','user', 'getvalidation');
                return $data;
            }

            // Check Validation codes to ensure a match.
            if ($valcode != $status['valcode']) {
                $msg = xarML('The validation codes do not match');
                xarErrorSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
                return;
            }

            $pending = xarModGetVar('roles', 'explicitapproval');
            if ($pending == 1 && ($status['uid'] != xarModGetVar('roles','admin')))
            {
                // Update the user status table to reflect a pending account.
                if (!xarModAPIFunc('roles',
                                   'user',
                                   'updatestatus',
                                    array('uname' => $uname,
                                          'state' => ROLES_STATE_PENDING))) return;
                /*Send Pending Email toggable ?
                if (!xarModAPIFunc( 'roles',
                                'admin',
                                'sendpendingemail',
                                array('uid'     => $status["uid"],
                                      'uname'    => $uname,
                                      'name'     => $status["name"],
                                      'email'    => $status["email"]))) {
                    $msg = xarML('Problem sending pending email');
                    xarErrorSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
                }*/
            } else {
                // Update the user status table to reflect a validated account.
                if (!xarModAPIFunc('roles',
                                   'user',
                                   'updatestatus',
                                    array('uname' => $uname,
                                          'state' => ROLES_STATE_ACTIVE))) return;
                //send welcome email (option)
                if (xarModGetVar('roles', 'sendwelcomeemail')) {
                    if (!xarModAPIFunc( 'roles',
                                    'admin',
                                    'senduseremail',
                                    array('uid' => array($status['uid'] => '1'),
                                          'mailtype' => 'welcome'))) {
                        $msg = xarML('Problem sending welcome email');
                        xarErrorSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
                    }
                }

                $url = xarModUrl('roles', 'user', 'register');
                $time = '3';
                xarVarSetCached('Meta.refresh','url', $url);
                xarVarSetCached('Meta.refresh','time', $time);
            }

            if (xarModGetVar('roles', 'sendnotice')){

                $adminname = xarModGetVar('mail', 'adminname');
                $adminemail = xarModGetVar('mail', 'adminmail');
                $message = "".xarML('A new user has registered or changed their email address.  Here are the details')." \n\n";
                $message .= "".xarML('Username')." = $status[name]\n";
                $message .= "".xarML('Email Address')." = $status[email]";

                $messagetitle = "".xarML('A user has registered or updated information')."";

                if (!xarModAPIFunc('mail',
                                   'admin',
                                   'sendmail',
                                   array('info' => $adminemail,
                                         'name' => $adminname,
                                         'subject' => $messagetitle,
                                         'message' => $message))) return;
            }

            xarModSetVar('roles', 'lastuser', $status['uid']);

            $data = xarTplModule('roles','user', 'getvalidation');

            break;

        case 'resend':
            // check for user and grab uid if exists
            $status = xarModAPIFunc('roles',
                                    'user',
                                    'get',
                                    array('uname' => $uname));
            if (!xarModAPIFunc( 'roles',
                                'admin',
                                'senduseremail',
                                array('uid' => array($status['uid'] => '1'),
                                      'mailtype' => 'confirmation',
                                      'ip' => xarML('Cannot resend IP'),
                                      'pass' => xarML('Can Not Resend Password')))) {
                    $msg = xarML('Problem resending confirmation email');
                    xarErrorSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
                }

            $data = xarTplModule('roles','user', 'getvalidation');

            // Redirect
            xarResponseRedirect(xarModURL('roles', 'user', 'getvalidation',array('sent' => 1)));

    }
    return $data;
}
?>