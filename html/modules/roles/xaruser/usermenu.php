<?php

function roles_user_usermenu()
{

    // Security check
    if (!xarSecurityCheck('ViewRoles')) return;

    if(!xarVarFetch('phase','str:1', $phase, 'menu', XARVAR_NOT_REQUIRED)) {return;}

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
                                                                         'email'        => $email,
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
    if(!xarVarFetch('uid',   'isset', $uid,    , XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('name',  'isset', $name,   , XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('pass1', 'isset', $pass1,  , XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('pass2', 'isset', $pass2,  , XARVAR_NOT_REQUIRED)) {return;}

            $uname = xarUserGetVar('uname');
            $email = xarUserGetVar('email');
            // Confirm authorisation code.
            if (!xarSecConfirmAuthKey()) return;

            if (!empty($pass1)){
                // Check to make sure passwords match
                if ($pass1 == $pass2){
                    $pass = $pass1;
                } else {
                    $msg = xarML('The passwords do not match');
                    xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
                    return;
                }
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
            } else{

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
