<?php
/**
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */

/**
 * Show the user menu
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_user_usermenu($args)
{
    if (!xarSecurityCheck('ViewRoles')) return;
    extract($args);

    if (!xarVarFetch('moduleload', 'pre:trim:str:1', $moduleload, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('returnurl', 'pre:trim:str:1', $returnurl, '', XARVAR_NOT_REQUIRED)) return;
    //let's make sure other modules that refer here get to a default and existing login or logout form
    $defaultauthdata      = xarMod::apiFunc('roles','user','getdefaultauthdata');
    $defaultauthmodname   = $defaultauthdata['defaultauthmodname'];
    $defaultloginmodname  = $defaultauthdata['defaultloginmodname'];
    $defaultlogoutmodname = $defaultauthdata['defaultlogoutmodname'];

    if (!xarUserIsLoggedIn()){
        xarController::redirect(xarModURL($defaultloginmodname,'user','showloginform'));
    }

    $id = xarUserGetVar('id');

    if (empty($moduleload)) {
        // we're updating basic user details (roles_user object)
        $phase = 'updatebasic';
    } else {
        // updating user settings for a module (modname_user_settings)
        $phase = 'updatesettings';
    }

    switch(strtolower($phase)) {

        case 'updatebasic':

            sys::import('modules.dynamicdata.class.objects.master');

            $object = DataObjectMaster::getObject(array('name' => 'roles_users'));
            $object->getItem(array('itemid' => $id));

            $oldpass = $object->properties['password']->getValue();
            $oldemail = $object->properties['email']->getValue();

            $isvalid = $object->checkInput();

            if ($isvalid) {
                $email = $object->properties['email']->getValue();
                if ($oldemail != $email){
                    if(xarModVars::get('roles','uniqueemail')) {
                        // check for duplicate email address
                        $user = xarMod::apiFunc('roles', 'user','get',
                                           array('email' => $email));
                        if ($user != false) {
                            unset($user);
                            //throw new DuplicateException(array('email address',$email));
                            $object->properties['email']->invalid = xarML('Email address must be unique to this site');
                            $isvalid = false;
                        }
                    }
                    if ($isvalid) {
                         // check for disallowed email addresses
                        $disallowedemails = xarModVars::get('roles','disallowedemails');
                        if (!empty($disallowedemails)) {
                            $disallowedemails = unserialize($disallowedemails);
                            $disallowedemails = explode("\r\n", $disallowedemails);
                            if (in_array ($email, $disallowedemails)) {
                                $msg = 'That email address is either reserved or not allowed on this website';
                                $object->properties['email']->invalid = xarML($msg);
                                $isvalid = false;
                                //throw new ForbiddenOperationException(null,$msg);
                            }
                        }
                    }
                }
            }

            if ($isvalid) {
                if (!xarSecConfirmAuthKey('roles')) {
                    return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
                }

                $newpass = $object->properties['password']->getValue();
                $passchanged = false;
                if ($oldpass != $newpass) {
                    $passchanged = true;
                    $object->properties['password']->value = $newpass;
                }

                $object->updateItem();

                if ($passchanged){
                    // @todo CHECKME: Send an email?
                }
                $email = $object->properties['email']->getValue();
                if ($oldemail != $email){
                    /* updated steps for changing email address
                       1) Check if validation is required (1a) and if so create confirmation code (1b)
                       2) Change user status to 2 (if validation is set as option)
                       3) If validation is required for a change, send the user an email about validation
                       4) if user is logged in (ie existing user), log user out
                       5) Display appropriate message
                    */
                    // Step 1a Check for validation required or not
                    $requireValidation = (bool)xarModVars::get('roles', 'requirevalidation');
                    if ($requireValidation || (xarUserGetVar('uname') != 'admin')) {
                        // Step 1b
                        // Create confirmation code and time registered
                        $confcode = xarMod::apiFunc('roles','user','makepass');

                        // Step 2
                        // Set the user to not validated
                         $object->properties['valcode']->setValue($confcode);
                        // Step 3
                        //Send validation email
                        if (!xarMod::apiFunc( 'roles',  'admin', 'senduseremail',
                                      array('id' => array($id => '1'), 'mailtype' => 'validation'))) {

                            $msg = xarML('Problem sending confirmation email');
                            throw new Exception($msg);
                        }
                        $object->updateItem();
                        // Step 4
                        // Log the user out. This needs to happen last
                        xarUserLogOut();

                        //Step 5
                        //Show a nice message for the person about email validation
                        $data = xarTplModule('roles','user', 'waitingconfirm');
                        return $data;
                    }
                }
                if (empty($returnurl))
                    $returnurl = xarModURL('roles', 'user', 'account', array('tab' => 'basic'));
                return xarController::redirect($returnurl);
            } else {
                // invalid, we need to show the form data again
                $data = array();
                $object->tplmodule = 'roles';
                $object->template = 'account';

                if (xarModVars::get('roles','setuserlastlogin')) {
                    //only display it for current user or admin
                    if (xarUserIsLoggedIn() && xarUserGetVar('id')==$id) { //they should be but ..
                        $userlastlogin = xarSession::getVar('roles_thislastlogin');
                        $usercurrentlogin = xarModUserVars::get('roles','userlastlogin',$id);
                    }elseif (xarSecurityCheck('AdminRoles',0,'Roles',$name) && xarModUserVars::get('roles','userlastlogin',$id)){
                        $usercurrentlogin = '';
                        $userlastlogin = xarModUserVars::get('roles','userlastlogin',$id);
                    }else{
                        $userlastlogin = '';
                        $usercurrentlogin = '';
                    }
                } else {
                    $userlastlogin='';
                    $usercurrentlogin='';
                }
                $authid = xarSecGenAuthKey('roles');

                $upasswordupdate = xarModUserVars::get('roles','passwordupdate');
                $usertimezonedata = xarModUserVars::get('roles','usertimezone');
                $utimezone = $usertimezonedata['timezone'];

                $item['module'] = 'roles';
                $item['itemtype'] = xarRoles::ROLES_USERTYPE;

                $hooks = xarModCallHooks('item','modify',$id,$item);
                if (isset($hooks['dynamicdata'])) {
                    unset($hooks['dynamicdata']);
                }
                // put formdata in an array so it can be passed through
                // xar:data-form in one go to our showform-usermenu template
                $formdata = array(
                    'authid'       => $authid,
                    'hooks'        => $hooks,
                    'id'          => $id,
                    'upasswordupdate' => $upasswordupdate,
                    'usercurrentlogin' => $usercurrentlogin,
                    'userlastlogin'    => $userlastlogin,
                    'utimezone'    => $utimezone,
                );

                $data['formdata'] = $formdata;
                $data['object'] = $object;
                $data['formaction'] = xarModURL('roles', 'user', 'usermenu');
                $data['tplmodule'] = 'roles';
                $data['template'] = 'account';
                $menutabs = array();
                $menutabs[] = array(
                    'label' => xarML('Display Profile'),
                    'title' => xarML('View your profile as it is seen by other site users'),
                    'url' => xarModURL('roles', 'user', 'account', array('tab' => 'profile')),
                    'active' => false
                );

                $menumods = array();
                // for now, roles must be hooked to roles in order for usermenus to be available
                if (xarModIsHooked('roles', 'roles')) {
                    // get a list of modules with user menu enabled
                    $allmods = xarMod::apiFunc('modules', 'admin', 'getlist');
                    foreach ($allmods as $modinfo) {
                        if (xarModVars::get($modinfo['name'], 'enable_user_menu') != 1) continue;
                        $menumods[] = $modinfo['name'];
                    }
                    // add a link to edit this users profile
                    $menutabs[] = array(
                        'label' => xarML('Edit Account'),
                        'title' => xarML('Edit your basic account information'),
                        'url' => xarModURL('roles', 'user', 'account', array('tab' => 'basic')),
                        'active' => true
                    );
                }

                if (!empty($menumods)) {
                    foreach ($menumods as $modname) {
                        $user_settings = xarMod::apiFunc('base', 'admin', 'getusersettings', array('module' => $modname, 'itemid' => $id));
                        if (isset($user_settings)) {
                            $menutabs[] = array(
                                'label' => $user_settings->label,
                                'title' => $user_settings->label,
                                'url' => xarModURL('roles', 'user', 'account', array('moduleload' => $modname)),
                                'active' => false
                            );
                        }
                    }
                }
                $menutabs[] = array(
                    'label' => xarML('Logout'),
                    'title' => xarML('Logout from the site'),
                    'url' => xarModURL($defaultlogoutmodname, 'user', 'logout'),
                    'active' => false
                );
                $data['menutabs'] = $menutabs;
                $data['authid'] = xarSecGenAuthKey('roles');
                $data['id']          = xarUserGetVar('id');
                $data['name']         = xarUserGetVar('name');
                $data['logoutmodule'] = $defaultlogoutmodname;
                $data['loginmodule']  = $defaultloginmodname;
                $data['authmodule']   = $defaultauthmodname;
                $data['moduleload'] = '';
                $data['tab'] = 'basic';
                if (empty($message)) $data['message'] = '';
                if (empty($returnurl))
                    $returnurl = xarModURL('roles', 'user', 'account', array('tab' => 'basic'));
                $data['returnurl'] = $returnurl;
                $data['submitlabel'] = xarML('Update Settings');
                return xarTplModule('roles','user','account', $data);
            }
        break;

        case 'updatesettings':
            $object = xarMod::apiFunc('base', 'admin', 'getusersettings', array('module' => $moduleload, 'itemid' => $id));

            try {
                $isvalid = xarMod::apiFunc($moduleload, 'user', 'usermenu', array('phase' => 'checkinput', 'object' => $object));
            } catch (Exception $e) {
                $isvalid = $object->checkInput();
            }
            if ($isvalid) {
                try {
                    xarMod::apiFunc($moduleload, 'user', 'usermenu', array('phase' => 'updateitem', 'object' => $object));
                } catch (Exception $e) {
                    if (!xarSecConfirmAuthKey($moduleload)) {
                        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
                    }
                    $object->updateItem();
                }
                if (empty($returnurl))
                    $returnurl = xarModURL('roles', 'user', 'account', array('moduleload' => $moduleload));
                return xarController::redirect($returnurl);
            }

            // must have invalid data, show the form again
            $menutabs = array();
            $menutabs[] = array(
                'label' => xarML('Display Profile'),
                'title' => xarML('View your profile as it is seen by other site users'),
                'url' => xarModURL('roles', 'user', 'account', array('tab' => 'profile')),
                'active' => false
            );

            $menumods = array();
            if ((bool)xarModVars::get('roles', 'usereditaccount')) {
                // get a list of modules with user menu enabled
                $allmods = xarMod::apiFunc('modules', 'admin', 'getlist');
                foreach ($allmods as $modinfo) {
                    if (xarModVars::get($modinfo['name'], 'enable_user_menu') != 1) continue;
                    $menumods[] = $modinfo['name'];
                }
                // add a link to edit this users profile
                $menutabs[] = array(
                    'label' => xarML('Edit Account'),
                    'title' => xarML('Edit your basic account information'),
                    'url' => xarModURL('roles', 'user', 'account', array('tab' => 'basic')),
                    'active' => false
                );
            }

            if (!empty($menumods)) {
                foreach ($menumods as $modname) {
                    $user_settings = xarMod::apiFunc('base', 'admin', 'getusersettings', array('module' => $modname, 'itemid' => $id));
                    if (isset($user_settings)) {
                        $isactive = $moduleload == $modname ? true : false;
                        $menutabs[] = array(
                            'label' => $user_settings->label,
                            'title' => $user_settings->label,
                            'url' => xarModURL('roles', 'user', 'account', array('moduleload' => $modname)),
                            'active' => $isactive
                        );
                    }
                }
            }
            $menutabs[] = array(
                'label' => xarML('Logout'),
                'title' => xarML('Logout from the site'),
                'url' => xarModURL($defaultlogoutmodname, 'user', 'logout'),
                'active' => false
            );

            try {
                $data = xarMod::apiFunc($moduleload, 'user', 'usermenu', array('phase' => 'showform', 'object' => $object));
            } catch (Exception $e) {
                $data = array();
            }

            // if we didn't get an object back from api use the one we already have
            if (!isset($data['object'])) {
                $data['object'] = $object;
            }
            // template defaults to /roles/xartemplates/objects/showform-usermenu.xt
            if (empty($data['tplmodule'])) {
                $data['object']->tplmodule = 'roles';
            }
            if (empty($data['template'])) {
               $data['object']->template = 'usermenu';
            }
            if (empty($data['layout'])) {
                $data['object']->layout = '';
            }
            if (empty($data['authid'])) {
                $data['authid'] = xarSecGenAuthKey($moduleload);
            }

            // and set some sensible defaults for common stuff
            if (empty($data['formaction'])) {
                $data['formaction'] = xarModURL('roles', 'user', 'usermenu');
            }
            if (empty($data['submitlabel'])) {
                $data['submitlabel'] = xarML('Update Settings');
            }
            if (empty($data['returnurl'])) {
                $data['returnurl'] = xarServer::GetCurrentURL();
            }
            if (empty($data['formdata'])) {
                $data['formdata'] = array();
            }
            $data['menutabs'] = $menutabs;
            $data['id']          = $id;
            $data['name']         = xarUserGetVar('name');
            $data['logoutmodule'] = $defaultlogoutmodname;
            $data['loginmodule']  = $defaultloginmodname;
            $data['authmodule']   = $defaultauthmodname;
            $data['moduleload'] = $moduleload;
            $data['tab'] = '';
            if (empty($message)) $data['message'] = '';
            return xarTPLModule('roles', 'user', 'account', $data);
        break;

    }

}
?>
