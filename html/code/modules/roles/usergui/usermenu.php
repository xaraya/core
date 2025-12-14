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
use xarRoles;
use DuplicateException;
use Exception;
use ForbiddenOperationException;

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
        if (!$this->sec()->checkAccess('ViewRoles')) {
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

        if (!$this->user()->isLoggedIn()) {
            $this->ctl()->redirect($this->ctl()->getModuleURL($defaultloginmodname, 'user', 'showloginform'));
            return true;
        }

        $id = $this->user()->getId();

        if (empty($moduleload)) {
            // we're updating basic user details (roles_user object)
            $phase = 'updatebasic';
        } else {
            // updating user settings for a module (modname_user_settings)
            $phase = 'updatesettings';
        }

        switch (strtolower($phase)) {

            case 'updatebasic':

                $object = $this->data()->getObject(['name' => 'roles_users']);
                $object->getItem(['itemid' => $id]);

                $oldpass = $object->properties['password']->value;
                $oldemail = $object->properties['email']->value;

                $isvalid = $object->checkInput();

                if ($isvalid) {
                    $email = $object->properties['email']->value;
                    if ($oldemail != $email) {
                        if ($this->mod()->getVar('uniqueemail')) {
                            // check for duplicate email address
                            $user = $userapi->get(['email' => $email]);
                            if ($user != false) {
                                unset($user);
                                //throw new DuplicateException(array('email address',$email));
                                $object->properties['email']->invalid = $this->ml('Email address must be unique to this site');
                                $isvalid = false;
                            }
                        }
                        if ($isvalid) {
                            // check for disallowed email addresses
                            $disallowedemails = $this->mod()->getVar('disallowedemails');
                            if (!empty($disallowedemails)) {
                                $disallowedemails = unserialize($disallowedemails);
                                $disallowedemails = explode("\r\n", $disallowedemails);
                                if (in_array($email, $disallowedemails)) {
                                    $msg = 'That email address is either reserved or not allowed on this website';
                                    $object->properties['email']->invalid = $this->ml($msg);
                                    $isvalid = false;
                                    //throw new ForbiddenOperationException(null,$msg);
                                }
                            }
                        }
                    }
                }

                if ($isvalid) {
                    if (!$this->sec()->confirmAuthKey('roles')) {
                        return $this->ctl()->badRequest('bad_author');
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
                        $requireValidation = (bool) $this->mod()->getVar('requirevalidation');
                        if ($requireValidation || ($this->user()->getUser() != 'admin')) {

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

                                $msg = $this->ml('Problem sending confirmation email');
                                throw new Exception($msg);
                            }

                            // Step 4
                            // Log the user out. This needs to happen last
                            $this->user()->logOut();

                            //Step 5
                            //Show a nice message for the person about email validation
                            $data = $this->tpl()->module('roles', 'user', 'waitingconfirm');
                            return $data;
                        }
                    }
                    if (empty($returnurl)) {
                        $returnurl = $this->ctl()->getModuleURL('roles', 'user', 'account', ['tab' => 'basic']);
                    }
                    return $this->ctl()->redirect($returnurl);
                } else {
                    // invalid, we need to show the form data again
                    $data = [];
                    $object->tplmodule = 'roles';
                    $object->template = 'account';

                    if ($this->mod()->getVar('setuserlastlogin')) {
                        //only display it for current user or admin
                        if ($this->user()->isLoggedIn() && $this->user()->getId() == $id) { //they should be but ..
                            $userlastlogin = $this->session()->getVar('roles_thislastlogin');
                            $usercurrentlogin = $this->mod()->getUserVar('userlastlogin');
                        } elseif ($this->sec()->check('AdminRoles', 0, 'Roles', $name) && $this->mod()->getUserVar('userlastlogin')) {
                            $usercurrentlogin = '';
                            $userlastlogin = $this->mod()->getUserVar('userlastlogin');
                        } else {
                            $userlastlogin = '';
                            $usercurrentlogin = '';
                        }
                    } else {
                        $userlastlogin = '';
                        $usercurrentlogin = '';
                    }
                    $authid = $this->sec()->genAuthKey('roles');

                    $upasswordupdate = $this->mod()->getUserVar('passwordupdate');
                    $usertimezonedata = $this->mod()->getUserVar('usertimezone');
                    $utimezone = $usertimezonedata['timezone'];

                    $item['module'] = 'roles';
                    $item['itemtype'] = xarRoles::ROLES_USERTYPE;

                    $hooks = $this->mod()->callHooks('item', 'modify', $id, $item);
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
                    $data['formaction'] = $this->ctl()->getModuleURL('roles', 'user', 'usermenu');
                    $data['tplmodule'] = 'roles';
                    $data['template'] = 'account';
                    $menutabs = [];
                    $menutabs[] = [
                        'label' => $this->ml('Display Profile'),
                        'title' => $this->ml('View your profile as it is seen by other site users'),
                        'url' => $this->ctl()->getModuleURL('roles', 'user', 'account', ['tab' => 'profile']),
                        'active' => false,
                    ];

                    $menumods = [];
                    // for now, roles must be hooked to roles in order for usermenus to be available
                    if ($this->mod()->isHooked('roles', 'roles')) {
                        // get a list of modules with user menu enabled
                        $allmods = $this->mod()->apiFunc('modules', 'admin', 'getlist');
                        foreach ($allmods as $modinfo) {
                            if ($this->mod($modinfo['name'])->getVar('enable_user_menu') != 1) {
                                continue;
                            }
                            $menumods[] = $modinfo['name'];
                        }
                        // add a link to edit this users profile
                        $menutabs[] = [
                            'label' => $this->ml('Edit Account'),
                            'title' => $this->ml('Edit your basic account information'),
                            'url' => $this->ctl()->getModuleURL('roles', 'user', 'account', ['tab' => 'basic']),
                            'active' => true,
                        ];
                    }

                    if (!empty($menumods)) {
                        foreach ($menumods as $modname) {
                            $user_settings = $this->mod()->apiFunc('base', 'admin', 'getusersettings', ['module' => $modname, 'itemid' => $id]);
                            if (isset($user_settings)) {
                                $menutabs[] = [
                                    'label' => $user_settings->label,
                                    'title' => $user_settings->label,
                                    'url' => $this->ctl()->getModuleURL('roles', 'user', 'account', ['moduleload' => $modname]),
                                    'active' => false,
                                ];
                            }
                        }
                    }
                    $menutabs[] = [
                        'label' => $this->ml('Logout'),
                        'title' => $this->ml('Logout from the site'),
                        'url' => $this->ctl()->getModuleURL($defaultlogoutmodname, 'user', 'logout'),
                        'active' => false,
                    ];
                    $data['menutabs'] = $menutabs;
                    $data['authid'] = $this->sec()->genAuthKey('roles');
                    $data['id']          = $this->user()->getId();
                    $data['name']         = $this->user()->getName();
                    $data['logoutmodule'] = $defaultlogoutmodname;
                    $data['loginmodule']  = $defaultloginmodname;
                    $data['authmodule']   = $defaultauthmodname;
                    $data['moduleload'] = '';
                    $data['tab'] = 'basic';
                    if (empty($message)) {
                        $data['message'] = '';
                    }
                    if (empty($returnurl)) {
                        $returnurl = $this->ctl()->getModuleURL('roles', 'user', 'account', ['tab' => 'basic']);
                    }
                    $data['returnurl'] = $returnurl;
                    $data['submitlabel'] = $this->ml('Update Settings');
                    return $this->tpl()->module('roles', 'user', 'account', $data);
                }

                // no break
            case 'updatesettings':
                $object = $this->mod()->apiFunc('base', 'admin', 'getusersettings', ['module' => $moduleload, 'itemid' => $id]);

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
                    $isvalid = $this->mod()->apiFunc($moduleload, 'user', 'usermenu', ['phase' => 'checkinput', 'object' => $object]);
                } catch (Exception $e) {
                    $isvalid = $object->checkInput();
                }
                if ($isvalid) {
                    try {
                        $this->mod()->apiFunc($moduleload, 'user', 'usermenu', ['phase' => 'updateitem', 'object' => $object]);
                    } catch (Exception $e) {
                        if (!$this->sec()->confirmAuthKey($moduleload)) {
                            return $this->ctl()->badRequest('bad_author');
                        }
                        $object->updateItem();
                    }
                    if (empty($returnurl)) {
                        $returnurl = $this->ctl()->getModuleURL('roles', 'user', 'account', ['moduleload' => $moduleload]);
                    }
                    return $this->ctl()->redirect($returnurl);
                }

                // must have invalid data, show the form again
                $menutabs = [];
                $menutabs[] = [
                    'label' => $this->ml('Display Profile'),
                    'title' => $this->ml('View your profile as it is seen by other site users'),
                    'url' => $this->ctl()->getModuleURL('roles', 'user', 'account', ['tab' => 'profile']),
                    'active' => false,
                ];

                $menumods = [];
                if ((bool) $this->mod()->getVar('usereditaccount')) {
                    // get a list of modules with user menu enabled
                    $allmods = $this->mod()->apiFunc('modules', 'admin', 'getlist');
                    foreach ($allmods as $modinfo) {
                        if ($this->mod($modinfo['name'])->getVar('enable_user_menu') != 1) {
                            continue;
                        }
                        $menumods[] = $modinfo['name'];
                    }
                    // add a link to edit this users profile
                    $menutabs[] = [
                        'label' => $this->ml('Edit Account'),
                        'title' => $this->ml('Edit your basic account information'),
                        'url' => $this->ctl()->getModuleURL('roles', 'user', 'account', ['tab' => 'basic']),
                        'active' => false,
                    ];
                }

                if (!empty($menumods)) {
                    foreach ($menumods as $modname) {
                        $user_settings = $this->mod()->apiFunc('base', 'admin', 'getusersettings', ['module' => $modname, 'itemid' => $id]);
                        if (isset($user_settings)) {
                            $isactive = $moduleload == $modname ? true : false;
                            $menutabs[] = [
                                'label' => $user_settings->label,
                                'title' => $user_settings->label,
                                'url' => $this->ctl()->getModuleURL('roles', 'user', 'account', ['moduleload' => $modname]),
                                'active' => $isactive,
                            ];
                        }
                    }
                }
                $menutabs[] = [
                    'label' => $this->ml('Logout'),
                    'title' => $this->ml('Logout from the site'),
                    'url' => $this->ctl()->getModuleURL($defaultlogoutmodname, 'user', 'logout'),
                    'active' => false,
                ];

                try {
                    $data = $this->mod()->apiFunc($moduleload, 'user', 'usermenu', ['phase' => 'showform', 'object' => $object]);
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
                    $data['authid'] = $this->sec()->genAuthKey($moduleload);
                }

                // and set some sensible defaults for common stuff
                if (empty($data['formaction'])) {
                    $data['formaction'] = $this->ctl()->getModuleURL('roles', 'user', 'usermenu');
                }
                if (empty($data['submitlabel'])) {
                    $data['submitlabel'] = $this->ml('Update Settings');
                }
                if (empty($data['returnurl'])) {
                    $data['returnurl'] = $this->ctl()->getCurrentURL();
                }
                if (empty($data['formdata'])) {
                    $data['formdata'] = [];
                }
                $data['menutabs'] = $menutabs;
                $data['id']          = $id;
                $data['name']         = $this->user()->getName();
                $data['logoutmodule'] = $defaultlogoutmodname;
                $data['loginmodule']  = $defaultloginmodname;
                $data['authmodule']   = $defaultauthmodname;
                $data['moduleload'] = $moduleload;
                $data['tab'] = '';
                if (empty($message)) {
                    $data['message'] = '';
                }
                return $this->tpl()->module('roles', 'user', 'account', $data);

        }

    }
}
