<?php

function roles_user_usermenu()
{

    // Security check
    if (!xarSecurityCheck('ViewRoles')) return;

    if(!xarVarFetch('phase','notempty', $phase, 'menu', XARVAR_NOT_REQUIRED)) {return;}

    xarTplSetPageTitle(xarVarPrepForDisplay(xarML('Your Account Preferences')));

    switch(strtolower($phase)) {
        case 'menu':

            $iconbasic = 'modules/roles/xarimages/home.gif';
            $iconenhanced = 'modules/roles/xarimages/home.gif';
            $data = xarTplModule('roles','user', 'user_menu_icon', array('iconbasic'    => $iconbasic,
                                                                         'iconenhanced' => $iconenhanced));

            break;

        case 'formbasic':
            $uname = xarUserGetVar('uname');
            $name = xarUserGetVar('name');
            $uid = xarUserGetVar('uid');
            $email = xarUserGetVar('email');
            $authid = xarSecGenAuthKey();
            $submitlabel = xarML('Submit');
            $data = xarTplModule('roles','user', 'user_menu_form', array('authid'       => $authid,
                                                                         'name'         => $name,
                                                                         'uname'        => $uname,
                                                                         'emailaddress' => $email,
                                                                         'submitlabel'  => $submitlabel,
                                                                         'uid'          => $uid));
            break;

        case 'formenhanced':
            $name = xarUserGetVar('name');
            $uid = xarUserGetVar('uid');
            $authid = xarSecGenAuthKey();

            $item['module'] = 'roles';
            $hooks = xarModCallHooks('item','modify',$uid,$item);
            if (empty($hooks)) {
                $hooks = '';
            } elseif (is_array($hooks)) {
                $hooks = join('',$hooks);
            }

            if (empty($hooks) || !is_string($hooks)) {
                $hooks = '';
            }

            $data = xarTplModule('roles','user', 'user_menu_formenhanced', array('authid'   => $authid,
                                                                                 'name'     => $name,
                                                                                 'uid'      => $uid,
                                                                                 'hooks'    => $hooks));
            break;

        case 'updatebasic':
            if(!xarVarFetch('uid',   'isset', $uid,    NULL, XARVAR_DONT_SET)) return;
            if(!xarVarFetch('name',  'isset', $name,   NULL, XARVAR_DONT_SET)) return;
            if(!xarVarFetch('email',  'isset', $email,   NULL, XARVAR_DONT_SET)) return;
            if(!xarVarFetch('pass1', 'isset', $pass1,  NULL, XARVAR_DONT_SET)) return;
            if(!xarVarFetch('pass2', 'isset', $pass2,  NULL, XARVAR_DONT_SET)) return;

            $uname = xarUserGetVar('uname');
            // Confirm authorisation code.
            if (!xarSecConfirmAuthKey()) return;

            if (!empty($pass1)){
                $minpasslength = xarModGetVar('roles', 'minpasslength');
                if (strlen($pass2) < $minpasslength) {
                    $msg = xarML('Your password must be ' . $minpasslength . ' characters long.');
                    xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
                    return;
                }
                // Check to make sure passwords match
                if ($pass1 == $pass2){
                    $pass = $pass1;
                } else {
                    $msg = xarML('The passwords do not match');
                    xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
                    return;
                }
                $email = xarUserGetVar('email');
                // The API function is called.
                if(!xarModAPIFunc('roles',
                                  'admin',
                                  'update',
                                   array('uid' => $uid,
                                         'uname' => $uname,
                                         'name' => $name,
                                         'email' => $email,
                                         'state' => 3,
                                         'pass' => $pass))) return;
            } elseif (!empty($email)){

            // Steps for changing email address.
            // 1) Validate the new email address for errors.
            // 2) Log user out.
            // 3) Change user status to 2 (if validation is set as option)
            // 4) Registration process takes over from there.

            // Step 1
            $emailcheck = xarModAPIFunc('roles',
                                        'user',
                                        'validatevar',
                                        array('var' => $email,
                                              'type' => 'email'));

            if ($emailcheck == false) {
                    $msg = xarML('There is an error in the supplied email address');
                    xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
                    return;
            }

                        // check for duplicate email address
            $user = xarModAPIFunc('roles',
                                  'user',
                                  'get',
                                   array('email' => $email));
            if ($user != false) {
                unset($user);
                $msg = xarML('That email address is already registered.');
                xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
                return;
            }

            // check for disallowed email addresses
            $disallowedemails = xarModGetVar('roles','disallowedemails');
            if (!empty($disallowedemails)) {
                $disallowedemails = unserialize($disallowedemails);
                $disallowedemails = explode("\r\n", $disallowedemails);
                if (in_array ($email, $disallowedemails)) {
                    $msg = xarML('That email address is either reserved or not allowed on this website');
                    xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
                    return;
                }
            }



            // Step 3
            $requireValidation = xarModGetVar('roles', 'requirevalidation');
            if ($requireValidation == false) {
                // The API function is called.
                if(!xarModAPIFunc('roles',
                                  'admin',
                                  'update',
                                   array('uid' => $uid,
                                         'uname' => $uname,
                                         'name' => $name,
                                         'email' => $email,
                                         'state' => 3))) return;
            } else {

                // Step 2
                xarUserLogOut();

                // Step 3

                // Create confirmation code and time registered
                $confcode = xarModAPIFunc('roles',
                                          'user',
                                          'makepass');

                // The API function is called.
                if(!xarModAPIFunc('roles',
                                  'admin',
                                  'update',
                                   array('uid'      => $uid,
                                         'uname'    => $uname,
                                         'name'     => $name,
                                         'email'    => $email,
                                         'valcode'  => $confcode,
                                         'state'    => 2))) return;

                    // TODO: make sending mail configurable too, depending on the other options ?
                    // Set up confirmation email
                    $confemail = xarModGetVar('roles', 'validationemail');
                    $conftitle = xarModGetVar('roles', 'validationtitle');

                    $sitename = xarModGetVar('themes', 'SiteName');
                    $siteadmin = xarModGetVar('mail', 'adminname');
                    $baseurl = xarServerGetBaseURL();

                    $confemailsearch = array('/%%link%%/',
                                             '/%%name%%/',
                                             '/%%username%%/',
                                             '/%%valcode%%/');

                    $confemailreplace = array("".$baseurl."val.php?v=".$confcode."&u=".$uid."",
                                              "$name",
                                              "$uname",
                                              "$confcode");

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
                                       array('info' => $email,
                                             'name' => $name,
                                             'subject' => $conftitle,
                                             'message' => $confemail))) return;
                }

            } else {
                $email = xarUserGetVar('email');
                // The API function is called.
                if(!xarModAPIFunc('roles',
                                  'admin',
                                  'update',
                                   array('uid' => $uid,
                                         'uname' => $uname,
                                         'name' => $name,
                                         'email' => $email,
                                         'state' => 3))) return;
            }

            // Redirect
            xarResponseRedirect(xarModURL('roles', 'user', 'account'));

            break;

        case 'updateenhanced':

            // Redirect
            xarResponseRedirect(xarModURL('roles', 'user', 'account'));

            break;
    }


    return $data;
}
?>
