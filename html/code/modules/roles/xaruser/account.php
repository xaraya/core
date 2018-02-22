<?php
/**
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */

/**
 * Displays the dynamic user menu.
 *
 * Currently does not work, due to design
 * of menu not in place, and DD not in place.
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @return array data for the template display
 * @todo   Finish this function.
 */
function roles_user_account()
{
    if(!xarVarFetch('moduleload','str', $moduleload, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('tab', 'pre:trim:str:1', $tab, '', XARVAR_NOT_REQUIRED)) return;

    //let's make sure other modules that refer here get to a default and existing login or logout form
    $defaultauthdata      = xarMod::apiFunc('roles','user','getdefaultauthdata');
    $defaultauthmodname   = $defaultauthdata['defaultauthmodname'];
    $defaultloginmodname  = $defaultauthdata['defaultloginmodname'];
    $defaultlogoutmodname = $defaultauthdata['defaultlogoutmodname'];

    if (!xarUserIsLoggedIn()){
        // bring the user back here after login :)
        $redirecturl = xarModURL('roles', 'user', 'account');
        xarController::redirect(xarModURL($defaultloginmodname,'user','showloginform', array('redirecturl' => urlencode($redirecturl))));
    }

    $id = xarUserGetVar('id');

    if ($id == XARUSER_LAST_RESORT) {
        $message = xarML('You are logged in as the last resort administrator.');
    } else  {

        $menutabs = array();
        $menutabs[] = array(
            'label' => xarML('Display Profile'),
            'title' => xarML('View your profile as it is seen by other site users'),
            'url' => xarModURL('roles', 'user', 'account', array('tab' => 'profile')),
            'active' => (empty($tab) || $tab == 'profile') && empty($moduleload) ? true : false
        );

        $menumods = array();
        // only display edit tabs if edit account is enabled
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
                'active' => $tab == 'basic' ? true : false
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
                    if ($isactive) {
                        // keep track of the current object
                        $object = $user_settings;
                    }
                }
            }
        }
        $menutabs[] = array(
            'label' => xarML('Logout'),
            'title' => xarML('Logout from the site'),
            'url' => xarModURL($defaultlogoutmodname, 'user', 'logout'),
            'active' => false
        );

        // we got user_settings, we're dealing with a user_settings (usermenu) object
        if (isset($object)) {
            // see if the current module has any form data for us
            try {
                // if function exists, use it to populate the data array
                $data = xarMod::apiFunc($moduleload, 'user', 'usermenu', array('phase' => 'showform', 'object' => $object));
            } catch (Exception $e) {
                // no function, build the data as we go along
                $data = array();
            }
            // if we didn't get an object back from api use the one we already have
            if (!isset($data['object'])) {
                $data['object'] = $object;
            }
            // template defaults to /roles/xartemplates/objects/showform-usermenu.xt
            if (empty($data['tplmodule'])) {
                $data['object']->tplmodule = 'roles'; // roles/xartemplates/objects/
            }
            if (empty($data['template'])) {
               $data['object']->template = 'usermenu'; // showform-usermenu.xt
            }
            if (empty($data['layout'])) {
                $data['object']->layout = ''; // default
            }
            if (empty($data['authid'])) {
                $data['authid'] = xarSecGenAuthKey($moduleload);
            }
        // no settings, we're dealing with the roles_user object
        } else {
            sys::import('modules.dynamicdata.class.objects');
            $object = DataObjectMaster::getObject(array('name' => 'roles_users'));
            $object->tplmodule = 'roles';   // roles/xartemplates/objects/
            $object->template = 'account';  // showdisplay- || showform- account.xt
            if (empty($tab) || $tab == 'profile') {
            } elseif ($tab == 'basic') {
                // set up the roles_user object for edit
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

                $upasswordupdate = xarModUserVars::get('roles','passwordupdate');
                // <chris> timezone is stored as a string not an array
                //$usertimezonedata = xarModUserVars::get('roles','usertimezone');
                //$utimezone = $usertimezonedata['timezone'];
                $utimezone = xarModUserVars::get('roles','usertimezone');
                $item['module'] = 'roles';
                $item['itemtype'] = xarRoles::ROLES_USERTYPE;

                $hooks = xarModCallHooks('item','modify',$id,$item);
                if (isset($hooks['dynamicdata'])) {
                    unset($hooks['dynamicdata']);
                }
                // put formdata in an array so it can be passed through
                // xar:data-form in one go to our showform-profile template
                $formdata = array(
                    'hooks'        => $hooks,
                    'id'          => $id,
                    'upasswordupdate' => $upasswordupdate,
                    'usercurrentlogin' => $usercurrentlogin,
                    'userlastlogin'    => $userlastlogin,
                    'utimezone'    => $utimezone
                );

                $data['formdata'] = $formdata;
            }
            $object->getItem(array('itemid' => $id));
            $data['object'] = $object;   
            // Bug 6566: name property only applies to roles_users object  
            $data['object']->properties['name']->display_layout = 'single';
        }
        // set some sensible defaults for common stuff
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
        if (empty($data['authid'])) {
            $data['authid'] = xarSecGenAuthKey('roles');
        }
        $data['menutabs'] = $menutabs;
        
    }
    $data['id']           = xarUserGetVar('id');
    $data['name']         = xarUserGetVar('name');
    $data['logoutmodule'] = $defaultlogoutmodname;
    $data['loginmodule']  = $defaultloginmodname;
    $data['authmodule']   = $defaultauthmodname;
    $data['moduleload']   = $moduleload;
    $data['tab'] = $tab;
    if (empty($message)) $data['message'] = '';

    return $data;
}

?>
