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
use DataObjectFactory;
use Exception;
use xarController;
use xarMod;
use xarModHooks;
use xarModUserVars;
use xarModVars;
use xarRoles;
use xarSec;
use xarSecurity;
use xarServer;
use xarSession;
use xarUser;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles user account function
 * @extends MethodClass<UserGui>
 */
class AccountMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Displays the dynamic user menu.
     * Currently does not work, due to design
     * of menu not in place, and DD not in place.
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return array|void data for the template display
     * @todo Finish this function.
     * @see UserGui::account()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        $this->var()->find('moduleload', $moduleload, 'str', '');
        $this->var()->find('tab', $tab, 'pre:trim:str:1', '');

        //let's make sure other modules that refer here get to a default and existing login or logout form
        $defaultauthdata      = $userapi->getdefaultauthdata();
        $defaultauthmodname   = $defaultauthdata['defaultauthmodname'];
        $defaultloginmodname  = $defaultauthdata['defaultloginmodname'];
        $defaultlogoutmodname = $defaultauthdata['defaultlogoutmodname'];

        if (!xarUser::isLoggedIn()) {
            // bring the user back here after login :)
            $redirecturl = $this->ctl()->getModuleURL('roles', 'user', 'account');
            $this->ctl()->redirect($this->ctl()->getModuleURL(
                $defaultloginmodname,
                'user',
                'showloginform',
                ['redirecturl' => urlencode($redirecturl)]
            ));
        }

        $id = xarUser::getVar('id');

        if ($id == xarUser::LAST_RESORT) {
            $message = $this->ml('You are logged in as the last resort administrator.');
        } else {

            $menutabs = [];
            $menutabs[] = [
                'label' => $this->ml('Display Profile'),
                'title' => $this->ml('View your profile as it is seen by other site users'),
                'url' => $this->ctl()->getModuleURL('roles', 'user', 'account', ['tab' => 'profile']),
                'active' => (empty($tab) || $tab == 'profile') && empty($moduleload) ? true : false,
            ];

            $menumods = [];
            // only display edit tabs if edit account is enabled
            if ((bool) xarModVars::get('roles', 'usereditaccount')) {
                // get a list of modules with user menu enabled
                $allmods = $this->mod()->apiFunc('modules', 'admin', 'getlist');
                foreach ($allmods as $modinfo) {
                    if (xarModVars::get($modinfo['name'], 'enable_user_menu') != 1) {
                        continue;
                    }
                    $menumods[] = $modinfo['name'];
                }
                // add a link to edit this users profile
                $menutabs[] = [
                    'label' => $this->ml('Edit Account'),
                    'title' => $this->ml('Edit your basic account information'),
                    'url' => $this->ctl()->getModuleURL('roles', 'user', 'account', ['tab' => 'basic']),
                    'active' => $tab == 'basic' ? true : false,
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
                        if ($isactive) {
                            // keep track of the current object
                            $object = $user_settings;
                        }
                    }
                }
            }
            $menutabs[] = [
                'label' => $this->ml('Logout'),
                'title' => $this->ml('Logout from the site'),
                'url' => $this->ctl()->getModuleURL($defaultlogoutmodname, 'user', 'logout'),
                'active' => false,
            ];

            // we got user_settings, we're dealing with a user_settings (usermenu) object
            if (isset($object)) {
                // see if the current module has any form data for us
                try {
                    // if function exists, use it to populate the data array
                    $data = $this->mod()->apiFunc($moduleload, 'user', 'usermenu', ['phase' => 'showform', 'object' => $object]);
                } catch (Exception $e) {
                    // no function, build the data as we go along
                    $data = [];
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
                    $data['authid'] = $this->sec()->genAuthKey($moduleload);
                }
                // no settings, we're dealing with the roles_user object
            } else {
                sys::import('modules.dynamicdata.class.objects');
                $object = $this->data()->getObject(['name' => 'roles_users']);
                $object->tplmodule = 'roles';   // roles/xartemplates/objects/
                $object->template = 'account';  // showdisplay- || showform- account.xt
                if (empty($tab) || $tab == 'profile') {
                } elseif ($tab == 'basic') {
                    // set up the roles_user object for edit
                    if (xarModVars::get('roles', 'setuserlastlogin')) {
                        //only display it for current user or admin
                        if (xarUser::isLoggedIn() && xarUser::getVar('id') == $id) { //they should be but ..
                            $userlastlogin = $this->session()->getVar('roles_thislastlogin');
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

                    $upasswordupdate = xarModUserVars::get('roles', 'passwordupdate');
                    // <chris> timezone is stored as a string not an array
                    //$usertimezonedata = xarModUserVars::get('roles','usertimezone');
                    //$utimezone = $usertimezonedata['timezone'];
                    $utimezone = xarModUserVars::get('roles', 'usertimezone');
                    $item['module'] = 'roles';
                    $item['itemtype'] = xarRoles::ROLES_USERTYPE;

                    $hooks = $this->mod()->callHooks('item', 'modify', $id, $item);
                    if (isset($hooks['dynamicdata'])) {
                        unset($hooks['dynamicdata']);
                    }
                    // put formdata in an array so it can be passed through
                    // xar:data-form in one go to our showform-profile template
                    $formdata = [
                        'hooks'        => $hooks,
                        'id'          => $id,
                        'upasswordupdate' => $upasswordupdate,
                        'usercurrentlogin' => $usercurrentlogin,
                        'userlastlogin'    => $userlastlogin,
                        'utimezone'    => $utimezone,
                    ];

                    $data['formdata'] = $formdata;
                }
                $object->getItem(['itemid' => $id]);
                $data['object'] = $object;
                // Bug 6566: name property only applies to roles_users object
                $data['object']->properties['name']->display_layout = 'single';
            }
            // set some sensible defaults for common stuff
            if (empty($data['formaction'])) {
                $data['formaction'] = $this->ctl()->getModuleURL('roles', 'user', 'usermenu');
            }
            if (empty($data['submitlabel'])) {
                $data['submitlabel'] = $this->ml('Update Settings');
            }
            if (empty($data['returnurl'])) {
                $data['returnurl'] = xarServer::GetCurrentURL();
            }
            if (empty($data['formdata'])) {
                $data['formdata'] = [];
            }
            if (empty($data['authid'])) {
                $data['authid'] = $this->sec()->genAuthKey('roles');
            }
            $data['menutabs'] = $menutabs;

        }
        $data['id']           = xarUser::getVar('id');
        $data['name']         = xarUser::getVar('name');
        $data['logoutmodule'] = $defaultlogoutmodname;
        $data['loginmodule']  = $defaultloginmodname;
        $data['authmodule']   = $defaultauthmodname;
        $data['moduleload']   = $moduleload;
        $data['tab'] = $tab;
        if (empty($message)) {
            $data['message'] = '';
        }

        return $data;
    }
}
