<?php
/**
 * Getvalidation validates a new user into the system
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */
/**
 * getvalidation validates a new user into the system
 * if their status is set to xarRoles::ROLES_STATE_NOTVALIDATED.
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @author  Jo Dalle Nogare <jojodee@xaraya.com>
 * @param   string $uname users name
 * @param   string $valcode is the validation code sent to user on registration
 * @param   string phase is the point in the function to return
 * @return  array|string|bool|void if valcode matches valcode in user status table
 * @TODO jojodee - validation process, duplication of functions and call to registration module needs to be rethought
 *         Rethink to provide cleaner separation between roles, authentication and registration
 */
function roles_user_getvalidation()
{
    // Security check
    if (!xarSecurity::check('ViewRoles')) return;

    //If a user is already logged in, no reason to see this.
    //We are going to send them to their account.
    if (xarUser::isLoggedIn()) {
       xarController::redirect(xarController::URL('roles', 'user', 'account',
                                      array('id' => xarUser::getVar('id'))));
       return true;
    }

    if (!xarVar::fetch('uname','str:1:100',$uname,'',xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('valcode','str:1:100',$valcode,'',xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('sent','int:0:2',$sent,0,xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('phase','str:1:100',$phase,'startvalidation',xarVar::NOT_REQUIRED)) return;

    xarTpl::setPageTitle(xarML('Validate Your Account'));
    /* This function to be provided with support functions to ensure we have got a default regmodule,
        if we need it. Tis should make it easier to move the User registration validation out of
        email revalidation soon, once we have all the registration default module instances captured in the new function.

    //$defaultauthdata=xarMod::apiFunc('roles','user','getdefaultregdata');

    */

    // What module are we using for registration?
    $regmodule = xarModVars::get('roles','defaultregmodule');
    if (empty($regmodule) || !xarMod::isAvailable($regmodule)) {
        return xarTpl::module('grader','user','errors',array('layout' => 'no_permission', 'message' => xarML('No registration module defined in the roles module')));
    }
    $modinfo = xarMod::getInfo($regmodule);
    $regmodule = $modinfo['name'];

    $defaultauthdata = xarMod::apiFunc('roles','user','getdefaultauthdata');
    $defaultloginmodname = $defaultauthdata['defaultloginmodname'];
    $authmodule = $defaultauthdata['defaultauthmodname'];

    //Set some general vars that we need in various options
    $pending = xarModVars::get($regmodule, 'explicitapproval');
    $loginlink = xarController::URL($defaultloginmodname,'user','main');

    $tplvars=array();
    $tplvars['loginlink'] = $loginlink;
    $tplvars['pending'] = $pending;

    switch(strtolower($phase)) {

        case 'startvalidation':
        default:
            $data = xarTpl::module($regmodule,'user', 'startvalidation',
                                                    array('phase'   => $phase,
                                                          'uname'   => $uname,
                                                          'sent'    => $sent,
                                                          'valcode' => $valcode,
                                                          'validatelabel' => xarML('Validate Your Account'),
                                                          'resendlabel' => xarML('Resend Validation Information')));
            break;

        case 'getvalidate':

            // Check for the user and grab the id if exists
            $status = xarMod::apiFunc('roles', 'user', 'get', array('uname' => $uname));

            // Trick the system when a user has double validated.
            if (empty($status['valcode'])){
                $data = xarTpl::module('roles','user','getvalidation',$tplvars);
                return $data;
            }

            // Check Validation codes to ensure a match.
            if ($valcode != $status['valcode']) {
                return xarTpl::module('roles','user','errors',array('layout' => 'bad_validation'));
            }

            // Check if this is a new user
            $newuser = false;
            $lastlogin = xarModUserVars::get('roles','userlastlogin',$status['id']);
            if (!isset($lastlogin) || empty($lastlogin)) $newuser = true;

            if (!$newuser) {
                // This is an old user who e.g. changed his/her email. Reset the status and we are done
                if (!xarMod::apiFunc('roles', 'user', 'updatestatus',
                                    array('uname' => $uname,
                                          'state' => xarRoles::ROLES_STATE_ACTIVE))) return;
                xarController::redirect(xarController::URL('roles', 'user', 'main'));
                
            } elseif  ($pending == 1 && ($status['id'] != xarModVars::get('roles','admin')))  {
                // This is a new user and the site requires admin approval
                // Update the user status table to reflect a pending account.
                if (!xarMod::apiFunc('roles', 'user', 'updatestatus',
                                    array('uname' => $uname,
                                          'state' => xarRoles::ROLES_STATE_PENDING)));

                /*Send Pending Email toggable ?   User email
                if (!xarMod::apiFunc( 'authentication',
                                'admin',
                                'sendpendingemail',
                                array('id'     => $status["id"],
                                      'uname'    => $uname,
                                      'name'     => $status["name"],
                                      'email'    => $status["email"]))) {
                    throw new GeneralException(null,'Problem sending pending email');
                }*/

            } else {
                // This is a new user and validation is complete
                // Update the user status table to reflect a validated account.
                if (!xarMod::apiFunc('roles', 'user', 'updatestatus',
                                    array('uname' => $uname,
                                          'state' => xarRoles::ROLES_STATE_ACTIVE))) return;
                //send welcome email (option)
                if (xarModVars::get($regmodule, 'sendwelcomeemail')) {
                    if (!xarMod::apiFunc('roles','admin','senduseremail',
                                    array('id' => array($status['id'] => '1'),
                                          'mailtype' => 'welcome'))) {
                        throw new GeneralException(null, 'Problem sending welcome email');
                    }
                }

                $url = xarController::URL('roles', 'user', 'main');

                $time = '4';
                xarVar::setCached('Meta.refresh','url', $url);
                xarVar::setCached('Meta.refresh','time', $time);
            }

            //TODO : This registration and validation processes need to be totally revamped and clearly defined - make do for now
            /* use the $newuser var to test for new user - no other way atm afaik as the process is shared for the new user
                                     process and the change email process and they may be totally separate
                                  */
            if (isset($regmodule) && (xarModVars::get($regmodule, 'sendnotice')==1) && $newuser){ // send the registration email for new
                $terms= '';

                if (xarModVars::get('registration', 'showterms') == 1) {
                    // User has agreed to the terms and conditions.
                        $terms = xarML('This user has agreed to the site terms and conditions.');
                }

                $status = xarMod::apiFunc('roles','user','get',array('uname' => $uname)); //check status as it may have changed

                $emailargs =  array('adminname'    => xarModVars::get('mail', 'adminname'),
                                    'adminemail'   => xarModVars::get('registration', 'notifyemail'),
                                    'userrealname' => $status['name'],
                                    'username'     => $status['uname'],
                                    'useremail'    => $status['email'],
                                    'terms'        => $terms,
                                    'id'          => $status['id'],
                                    'userstatus'   => $status['state']
                                    );
                if (!xarMod::apiFunc('registration', 'user', 'notifyadmin', $emailargs)) {
                    return; // TODO ...something here if the email is not sent..
                }

            } elseif  ((bool)xarModVars::get('roles', 'requirevalidation') && !$newuser && xarModVars::get('roles','askwelcomeemail')) {
             //send this email if we know for sure email validation only is required, not validation for new users - a roles function

                $adminname = xarModVars::get('mail', 'adminname');
                $adminemail = xarModVars::get('mail', 'adminmail');
                $message = "".xarML('A user has revalidated their changed email address.  Here are the details')." \n\n";
                $message .= "".xarML('Username')." = $status[name]\n";
                $message .= "".xarML('Email Address')." = $status[email]";

                $messagetitle = "".xarML('A user has updated information')."";

                if (!xarMod::apiFunc('mail', 'admin', 'sendmail',
                                   array('info' => $adminemail,
                                         'name' => $adminname,
                                         'subject' => $messagetitle,
                                         'message' => $message))) return;
            }

            xarModVars::set('roles', 'lastuser', $status['id']);

            $data = xarTpl::module('roles','user', 'getvalidation', $tplvars);

            break;

        case 'resend':
            // check for user and grab id if exists
            $status = xarMod::apiFunc('roles', 'user', 'get', array('uname' => $uname));

            if (!xarMod::apiFunc( 'roles','admin','senduseremail',
                                array('id' => array($status['id'] => '1'),
                                      'mailtype' => 'confirmation',
                                      'ip' => xarML('Cannot resend IP'),
                                      'pass' => xarML('Can Not Resend Password')))) {
                    throw new GeneralException(null,'Problem resending confirmation email');
                }

            $data = xarTpl::module('roles','user', 'getvalidation', $tplvars);

            // Redirect
            xarController::redirect(xarController::URL('roles', 'user', 'getvalidation',array('sent' => 1)));

        }

    return $data;
}
