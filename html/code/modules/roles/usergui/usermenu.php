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
use DataObjectFactory;
use DuplicateException;
use Exception;
use ForbiddenOperationException;
use xarController;
use xarHooks;
use xarMod;
use xarModHooks;
use xarModUserVars;
use xarModVars;
use xarRoles;
use xarSec;
use xarSecurity;
use xarServer;
use xarSession;
use xarTpl;
use xarUser;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles user usermenu function
 * @extends MethodClass<UserGui>
 */
class UsermenuMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Show the user menu
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return string|void output display string
     * @see UserGui::usermenu()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        if (!xarSecurity::check('ViewRoles')) {
            return;
        }
        extract($args);

        $this->var()->find('moduleload', $moduleload, 'pre:trim:str:1', '');
        $this->var()->find('returnurl', $returnurl, 'pre:trim:str:1', '');
        //let's make sure other modules that refer here get to a default and existing login or logout form
        $defaultauthdata      = $userapi->getdefaultauthdata();
        $defaultauthmodname   = $defaultauthdata['defaultauthmodname'];
        $defaultloginmodname  = $defaultauthdata['defaultloginmodname'];
        $defaultlogoutmodname = $defaultauthdata['defaultlogoutmodname'];

        if (!xarUser::isLoggedIn()) {
            xarController::redirect(xarController::URL($defaultloginmodname, 'user', 'showloginform'), null, $this->getContext());
        }

        $id = xarUser::getVar('id');

        if (empty($moduleload)) {
            // we're updating basic user details (roles_user object)
            $phase = 'updatebasic';
        } else {
            // updating user settings for a module (modname_user_settings)
            $phase = 'updatesettings';
        }

        switch (strtolower($phase)) {

            case 'updatebasic':

                sys::import('modules.dynamicdata.class.objects.factory');

                $object = DataObjectFactory::getObject(['name' => 'roles_users']);
                $object->getItem(['itemid' => $id]);

                $oldpass = $object->properties['password']->value;
                $oldemail = $object->properties['email']->value;

                $isvalid = $object->checkInput();

                if ($isvalid) {
                    $email = $object->properties['email']->value;
                    if ($oldemail != $email) {
                        if (xarModVars::get('roles', 'uniqueemail')) {
                            // check for duplicate email address
                            $user = $userapi->get(['email' => $email]);
                            if ($user != false) {
                                unset($user);
                                //throw new DuplicateException(array('email address',$email));
                                $object->properties['email']->invalid = xarML('Email address must be unique to this site');
                                $isvalid = false;
                            }
                        }
                        if ($isvalid) {
                            // check for disallowed email addresses
                            $disallowedemails = xarModVars::get('roles', 'disallowedemails');
                            if (!empty($disallowedemails)) {
                                $disallowedemails = unserialize($disallowedemails);
                                $disallowedemails = explode("\r\n", $disallowedemails);
                                if (in_array($email, $disallowedemails)) {
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
                    if (!xarSec::confirmAuthKey('roles')) {
                        return xarController::badRequest('bad_author', $this->getContext());
                    }

                    $newpass = $object->properties['password']->value;
                    $passchanged = false;
                    if ($oldpass != $newpass) {
                        $passchanged = true;
                        $object->properties['password']->value = $newpass;
                    }

                    $object->updateItem();

                    if ($passchanged) {
                        // @todo CHECKME: Send an email?
                    }
                    $email = $object->properties['email']->value;
                    if ($oldemail != $email) {
                        /* updated steps for changing email address
                           1) Check if validation is required (1a) and if so create confirmation code (1b)
                           2) Change user status to 2 (if validation is set as option)
                           3) If validation is required for a change, send the user an email about validation
                           4) if user is logged in (ie existing user), log user out
                           5) Display appropriate message
                        */
                        // Step 1a Check for validation required or not
                        $requireValidation = (bool) xarModVars::get('roles', 'requirevalidation');
                        if ($requireValidation || (xarUser::getVar('uname') != 'admin')) {

                            // Step 1
                            // Create confirmation code and time registered
                            $confcode = $userapi->makepass();

                            // Step 2
                            // Set the user to not validated
                            $object->properties['state']->setValue(xarRoles::ROLES_STATE_NOTVALIDATED);
                            // Set the validation code
                            $object->properties['valcode']->setValue($confcode);
                            // Reset the password based on the validation code
                            $object->properties['password']->setValue($confcode);
                            $object->updateItem();

                            // Step 3
                            //Send validation email
                            if (!$adminapi->senduseremail(['id' => [$id => '1'], 'mailtype' => 'validation'])) {

                                $msg = xarML('Problem sending confirmation email');
                                throw new Exception($msg);
                            }

                            // Step 4
                            // Log the user out. This needs to happen last
                            xarUser::logOut();

                            //Step 5
                            //Show a nice message for the person about email validation
                            $data = xarTpl::module('roles', 'user', 'waitingconfirm');
                            return $data;
                        }
                    }
                    if (empty($returnurl)) {
                        $returnurl = xarController::URL('roles', 'user', 'account', ['tab' => 'basic']);
                    }
                    return xarController::redirect($returnurl, null, $this->getContext());
                } else {
                    // invalid, we need to show the form data again
                    $data = [];
                    $object->tplmodule = 'roles';
                    $object->template = 'account';

                    if (xarModVars::get('roles', 'setuserlastlogin')) {
                        //only display it for current user or admin
                        if (xarUser::isLoggedIn() && xarUser::getVar('id') == $id) { //they should be but ..
                            $userlastlogin = xarSession::getVar('roles_thislastlogin');
                            $usercurrentlogin = xarModUserVars::get('roles', 'userlastlogin', $id);
                        } elseif (xarSecurity::check('AdminRoles', 0, 'Roles', $name) && xarModUserVars::get('roles', 'userlastlogin', $id)) {
                            $usercurrentlogin = '';
                            $userlastlogin = xarModUserVars::get('roles', 'userlastlogin', $id);
                        } else {
                            $userlastlogin = '';
                            $usercurrentlogin = '';
                        }
                    } else {
                        $userlastlogin = '';
                        $usercurrentlogin = '';
                    }
                    $authid = xarSec::genAuthKey('roles');

                    $upasswordupdate = xarModUserVars::get('roles', 'passwordupdate');
                    $usertimezonedata = xarModUserVars::get('roles', 'usertimezone');
                    $utimezone = $usertimezonedata['timezone'];

                    $item['module'] = 'roles';
                    $item['itemtype'] = xarRoles::ROLES_USERTYPE;

                    $hooks = xarModHooks::call('item', 'modify', $id, $item);
                    if (isset($hooks['dynamicdata'])) {
                        unset($hooks['dynamicdata']);
                    }
                    // put formdata in an array so it can be passed through
                    // xar:data-form in one go to our showform-usermenu template
                    $formdata = [
                        'authid'       => $authid,
                        'hooks'        => $hooks,
                        'id'          => $id,
                        'upasswordupdate' => $upasswordupdate,
                        'usercurrentlogin' => $usercurrentlogin,
                        'userlastlogin'    => $userlastlogin,
                        'utimezone'    => $utimezone,
                    ];

                    $data['formdata'] = $formdata;
                    $data['object'] = $object;
                    $data['formaction'] = xarController::URL('roles', 'user', 'usermenu');
                    $data['tplmodule'] = 'roles';
                    $data['template'] = 'account';
                    $menutabs = [];
                    $menutabs[] = [
                        'label' => xarML('Display Profile'),
                        'title' => xarML('View your profile as it is seen by other site users'),
                        'url' => xarController::URL('roles', 'user', 'account', ['tab' => 'profile']),
                        'active' => false,
                    ];

                    $menumods = [];
                    // for now, roles must be hooked to roles in order for usermenus to be available
                    if (xarHooks::isAttached('roles', 'roles')) {
                        // get a list of modules with user menu enabled
                        $allmods = xarMod::apiFunc('modules', 'admin', 'getlist');
                        foreach ($allmods as $modinfo) {
                            if (xarModVars::get($modinfo['name'], 'enable_user_menu') != 1) {
                                continue;
                            }
                            $menumods[] = $modinfo['name'];
                        }
                        // add a link to edit this users profile
                        $menutabs[] = [
                            'label' => xarML('Edit Account'),
                            'title' => xarML('Edit your basic account information'),
                            'url' => xarController::URL('roles', 'user', 'account', ['tab' => 'basic']),
                            'active' => true,
                        ];
                    }

                    if (!empty($menumods)) {
                        foreach ($menumods as $modname) {
                            $user_settings = xarMod::apiFunc('base', 'admin', 'getusersettings', ['module' => $modname, 'itemid' => $id]);
                            if (isset($user_settings)) {
                                $menutabs[] = [
                                    'label' => $user_settings->label,
                                    'title' => $user_settings->label,
                                    'url' => xarController::URL('roles', 'user', 'account', ['moduleload' => $modname]),
                                    'active' => false,
                                ];
                            }
                        }
                    }
                    $menutabs[] = [
                        'label' => xarML('Logout'),
                        'title' => xarML('Logout from the site'),
                        'url' => xarController::URL($defaultlogoutmodname, 'user', 'logout'),
                        'active' => false,
                    ];
                    $data['menutabs'] = $menutabs;
                    $data['authid'] = xarSec::genAuthKey('roles');
                    $data['id']          = xarUser::getVar('id');
                    $data['name']         = xarUser::getVar('name');
                    $data['logoutmodule'] = $defaultlogoutmodname;
                    $data['loginmodule']  = $defaultloginmodname;
                    $data['authmodule']   = $defaultauthmodname;
                    $data['moduleload'] = '';
                    $data['tab'] = 'basic';
                    if (empty($message)) {
                        $data['message'] = '';
                    }
                    if (empty($returnurl)) {
                        $returnurl = xarController::URL('roles', 'user', 'account', ['tab' => 'basic']);
                    }
                    $data['returnurl'] = $returnurl;
                    $data['submitlabel'] = xarML('Update Settings');
                    $data['context'] ??= $this->getContext();
                    return xarTpl::module('roles', 'user', 'account', $data);
                }

                // no break
            case 'updatesettings':
                $object = xarMod::apiFunc('base', 'admin', 'getusersettings', ['module' => $moduleload, 'itemid' => $id]);

                // Disable any fields that aren't set by the users
                $skipped = ['primaryparent'];
                $fieldlist = [];
                foreach ($object->properties as $key => $value) {
                    if (in_array($key, $skipped)) {
                        continue;
                    }
                    $fieldlist[] = $key;
                }
                $object->setFieldList($fieldlist);
                try {
                    $isvalid = xarMod::apiFunc($moduleload, 'user', 'usermenu', ['phase' => 'checkinput', 'object' => $object]);
                } catch (Exception $e) {
                    $isvalid = $object->checkInput();
                }
                if ($isvalid) {
                    try {
                        xarMod::apiFunc($moduleload, 'user', 'usermenu', ['phase' => 'updateitem', 'object' => $object]);
                    } catch (Exception $e) {
                        if (!xarSec::confirmAuthKey($moduleload)) {
                            return xarController::badRequest('bad_author', $this->getContext());
                        }
                        $object->updateItem();
                    }
                    if (empty($returnurl)) {
                        $returnurl = xarController::URL('roles', 'user', 'account', ['moduleload' => $moduleload]);
                    }
                    return xarController::redirect($returnurl, null, $this->getContext());
                }

                // must have invalid data, show the form again
                $menutabs = [];
                $menutabs[] = [
                    'label' => xarML('Display Profile'),
                    'title' => xarML('View your profile as it is seen by other site users'),
                    'url' => xarController::URL('roles', 'user', 'account', ['tab' => 'profile']),
                    'active' => false,
                ];

                $menumods = [];
                if ((bool) xarModVars::get('roles', 'usereditaccount')) {
                    // get a list of modules with user menu enabled
                    $allmods = xarMod::apiFunc('modules', 'admin', 'getlist');
                    foreach ($allmods as $modinfo) {
                        if (xarModVars::get($modinfo['name'], 'enable_user_menu') != 1) {
                            continue;
                        }
                        $menumods[] = $modinfo['name'];
                    }
                    // add a link to edit this users profile
                    $menutabs[] = [
                        'label' => xarML('Edit Account'),
                        'title' => xarML('Edit your basic account information'),
                        'url' => xarController::URL('roles', 'user', 'account', ['tab' => 'basic']),
                        'active' => false,
                    ];
                }

                if (!empty($menumods)) {
                    foreach ($menumods as $modname) {
                        $user_settings = xarMod::apiFunc('base', 'admin', 'getusersettings', ['module' => $modname, 'itemid' => $id]);
                        if (isset($user_settings)) {
                            $isactive = $moduleload == $modname ? true : false;
                            $menutabs[] = [
                                'label' => $user_settings->label,
                                'title' => $user_settings->label,
                                'url' => xarController::URL('roles', 'user', 'account', ['moduleload' => $modname]),
                                'active' => $isactive,
                            ];
                        }
                    }
                }
                $menutabs[] = [
                    'label' => xarML('Logout'),
                    'title' => xarML('Logout from the site'),
                    'url' => xarController::URL($defaultlogoutmodname, 'user', 'logout'),
                    'active' => false,
                ];

                try {
                    $data = xarMod::apiFunc($moduleload, 'user', 'usermenu', ['phase' => 'showform', 'object' => $object]);
                } catch (Exception $e) {
                    $data = [];
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
                    $data['authid'] = xarSec::genAuthKey($moduleload);
                }

                // and set some sensible defaults for common stuff
                if (empty($data['formaction'])) {
                    $data['formaction'] = xarController::URL('roles', 'user', 'usermenu');
                }
                if (empty($data['submitlabel'])) {
                    $data['submitlabel'] = xarML('Update Settings');
                }
                if (empty($data['returnurl'])) {
                    $data['returnurl'] = xarServer::GetCurrentURL();
                }
                if (empty($data['formdata'])) {
                    $data['formdata'] = [];
                }
                $data['menutabs'] = $menutabs;
                $data['id']          = $id;
                $data['name']         = xarUser::getVar('name');
                $data['logoutmodule'] = $defaultlogoutmodname;
                $data['loginmodule']  = $defaultloginmodname;
                $data['authmodule']   = $defaultauthmodname;
                $data['moduleload'] = $moduleload;
                $data['tab'] = '';
                if (empty($message)) {
                    $data['message'] = '';
                }
                $data['context'] ??= $this->getContext();
                return xarTpl::module('roles', 'user', 'account', $data);

        }

    }
}
