<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\UserGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\UserGui;
use Xaraya\Modules\Roles\UserApi;
use Xaraya\Modules\Roles\AdminApi;
use GeneralException;
use xarController;
use xarMod;
use xarModUserVars;
use xarModVars;
use xarRoles;
use xarSecurity;
use xarTpl;
use xarUser;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles user getvalidation function
 * @extends MethodClass<UserGui>
 */
class GetvalidationMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * getvalidation validates a new user into the system
     * if their status is set to xarRoles::ROLES_STATE_NOTVALIDATED.
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @author Jo Dalle Nogare <jojodee@xaraya.com>
     * @param string $uname users name
     * @param string $valcode is the validation code sent to user on registration
     * @param string phase is the point in the function to return
     * @return array|string|bool|void if valcode matches valcode in user status table
     * @TODO jojodee - validation process, duplication of functions and call to registration module needs to be rethought
     * Rethink to provide cleaner separation between roles, authentication and registration
     * @see UserGui::getvalidation()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security check
        if (!$this->sec()->checkAccess('ViewRoles')) {
            return;
        }

        //If a user is already logged in, no reason to see this.
        //We are going to send them to their account.
        if (xarUser::isLoggedIn()) {
            $this->ctl()->redirect($this->ctl()->getModuleURL(
                'roles',
                'user',
                'account',
                ['id' => xarUser::getVar('id')]
            ));
            return true;
        }

        $this->var()->find('uname', $uname, 'str:1:100', '');
        $this->var()->find('valcode', $valcode, 'str:1:100', '');
        $this->var()->find('sent', $sent, 'int:0:2', 0);
        $this->var()->find('phase', $phase, 'str:1:100', 'startvalidation');

        $this->tpl()->setPageTitle($this->ml('Validate Your Account'));
        /* This function to be provided with support functions to ensure we have got a default regmodule,
            if we need it. Tis should make it easier to move the User registration validation out of
            email revalidation soon, once we have all the registration default module instances captured in the new function.

        //$defaultauthdata=$userapi->getdefaultregdata();

        */

        // What module are we using for registration?
        $regmodule = $this->mod()->getVar('defaultregmodule');
        if (empty($regmodule) || !$this->mod()->isAvailable($regmodule)) {
            return $this->tpl()->module('grader', 'user', 'errors', ['layout' => 'no_permission', 'message' => $this->ml('No registration module defined in the roles module')]);
        }
        $modinfo = $this->mod()->getInfo($regmodule);
        $regmodule = $modinfo['name'];

        $defaultauthdata = $userapi->getdefaultauthdata();
        $defaultloginmodname = $defaultauthdata['defaultloginmodname'];
        $authmodule = $defaultauthdata['defaultauthmodname'];

        //Set some general vars that we need in various options
        $pending = $this->mod($regmodule)->getVar('explicitapproval');
        $loginlink = $this->ctl()->getModuleURL($defaultloginmodname, 'user', 'main');

        $tplvars = [];
        $tplvars['loginlink'] = $loginlink;
        $tplvars['pending'] = $pending;

        switch (strtolower($phase)) {

            case 'startvalidation':
            default:
                $data = $this->tpl()->module(
                    $regmodule,
                    'user',
                    'startvalidation',
                    ['phase'   => $phase,
                        'uname'   => $uname,
                        'sent'    => $sent,
                        'valcode' => $valcode,
                        'validatelabel' => $this->ml('Validate Your Account'),
                        'resendlabel' => $this->ml('Resend Validation Information')]
                );
                break;

            case 'getvalidate':

                // Check for the user and grab the id if exists
                $status = $userapi->get(['uname' => $uname]);

                // Trick the system when a user has double validated.
                if (empty($status['valcode'])) {
                    $data = $this->tpl()->module('roles', 'user', 'getvalidation', $tplvars);
                    return $data;
                }

                // Check Validation codes to ensure a match.
                if ($valcode != $status['valcode']) {
                    return $this->tpl()->module('roles', 'user', 'errors', ['layout' => 'bad_validation']);
                }

                // Check if this is a new user
                $newuser = false;
                $lastlogin = xarModUserVars::get('roles', 'userlastlogin', $status['id']);
                if (!isset($lastlogin) || empty($lastlogin)) {
                    $newuser = true;
                }

                if (!$newuser) {
                    // This is an old user who e.g. changed his/her email. Reset the status and we are done
                    if (!$userapi->updatestatus(['uname' => $uname,
                        'state' => xarRoles::ROLES_STATE_ACTIVE])) {
                        return;
                    }
                    $this->ctl()->redirect($this->ctl()->getModuleURL('roles', 'user', 'main'));

                } elseif ($pending == 1 && ($status['id'] != $this->mod()->getVar('admin'))) {
                    // This is a new user and the site requires admin approval
                    // Update the user status table to reflect a pending account.
                    if (!$userapi->updatestatus(['uname' => $uname,
                        'state' => xarRoles::ROLES_STATE_PENDING]));

                    /*Send Pending Email toggable ?   User email
                    if (!$this->mod()->apiFunc( 'authentication',
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
                    if (!$userapi->updatestatus(['uname' => $uname,
                        'state' => xarRoles::ROLES_STATE_ACTIVE])) {
                        return;
                    }
                    //send welcome email (option)
                    if ($this->mod($regmodule)->getVar('sendwelcomeemail')) {
                        if (!$adminapi->senduseremail(['id' => [$status['id'] => '1'],
                            'mailtype' => 'welcome'])) {
                            throw new GeneralException(null, 'Problem sending welcome email');
                        }
                    }

                    $url = $this->ctl()->getModuleURL('roles', 'user', 'main');

                    $time = '4';
                    $this->var()->setCached('Meta.refresh', 'url', $url);
                    $this->var()->setCached('Meta.refresh', 'time', $time);
                }

                //TODO : This registration and validation processes need to be totally revamped and clearly defined - make do for now
                /* use the $newuser var to test for new user - no other way atm afaik as the process is shared for the new user
                                         process and the change email process and they may be totally separate
                                      */
                if (isset($regmodule) && ($this->mod($regmodule)->getVar('sendnotice') == 1) && $newuser) { // send the registration email for new
                    $terms = '';

                    if ($this->mod('registration')->getVar('showterms') == 1) {
                        // User has agreed to the terms and conditions.
                        $terms = $this->ml('This user has agreed to the site terms and conditions.');
                    }

                    $status = $userapi->get(['uname' => $uname]); //check status as it may have changed

                    $emailargs =  ['adminname'    => $this->mod('mail')->getVar('adminname'),
                        'adminemail'   => $this->mod('registration')->getVar('notifyemail'),
                        'userrealname' => $status['name'],
                        'username'     => $status['uname'],
                        'useremail'    => $status['email'],
                        'terms'        => $terms,
                        'id'          => $status['id'],
                        'userstatus'   => $status['state'],
                    ];
                    if (!$this->mod()->apiFunc('registration', 'user', 'notifyadmin', $emailargs)) {
                        return; // TODO ...something here if the email is not sent..
                    }

                } elseif ((bool) $this->mod()->getVar('requirevalidation') && !$newuser && $this->mod()->getVar('askwelcomeemail')) {
                    //send this email if we know for sure email validation only is required, not validation for new users - a roles function

                    $adminname = $this->mod('mail')->getVar('adminname');
                    $adminemail = $this->mod('mail')->getVar('adminmail');
                    $message = "" . $this->ml('A user has revalidated their changed email address.  Here are the details') . " \n\n";
                    $message .= "" . $this->ml('Username') . " = $status[name]\n";
                    $message .= "" . $this->ml('Email Address') . " = $status[email]";

                    $messagetitle = "" . $this->ml('A user has updated information') . "";

                    if (!$this->mod()->apiFunc(
                        'mail',
                        'admin',
                        'sendmail',
                        ['info' => $adminemail,
                            'name' => $adminname,
                            'subject' => $messagetitle,
                            'message' => $message]
                    )) {
                        return;
                    }
                }

                $this->mod()->setVar('lastuser', $status['id']);

                $data = $this->tpl()->module('roles', 'user', 'getvalidation', $tplvars);

                break;

            case 'resend':
                // check for user and grab id if exists
                $status = $userapi->get(['uname' => $uname]);

                if (!$adminapi->senduseremail(['id' => [$status['id'] => '1'],
                    'mailtype' => 'confirmation',
                    'ip' => $this->ml('Cannot resend IP'),
                    'pass' => $this->ml('Can Not Resend Password')])) {
                    throw new GeneralException(null, 'Problem resending confirmation email');
                }

                $data = $this->tpl()->module('roles', 'user', 'getvalidation', $tplvars);

                // Redirect
                $this->ctl()->redirect($this->ctl()->getModuleURL(
                    'roles',
                    'user',
                    'getvalidation',
                    ['sent' => 1]
                ));

        }

        return $data;
    }
}
