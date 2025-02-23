<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\AdminGui;
use Xaraya\Modules\Roles\UserApi;
use Exception;
use xarConfigVars;
use xarController;
use xarDB;
use xarHooks;
use xarMod;
use xarModHooks;
use xarModVars;
use xarRoles;
use xarSec;
use xarSecurity;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles admin modifyconfig function
 * @extends MethodClass<AdminGui>
 */
class ModifyconfigMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Modify the configuration settings of this module
     * Standard GUI function to display and update the configuration settings of the module based on input data.
     * @return mixed data array for the template display or output display string if invalid data submitted
     * @see AdminGui::modifyconfig()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Security
        if (!xarSecurity::check('AdminRoles')) {
            return;
        }

        $data = [];
        $this->var()->find('phase', $phase, 'str:1:100', 'modify');
        $this->var()->find('tab', $data['tab'], 'str:1:100', 'general');

        // get a list of everyone with admin privileges
        // TODO: find a more elegant way to do this
        // first find the id of the admin privilege
        $role  = xarRoles::get(xarModVars::get('roles', 'admin'));
        $privs = array_merge($role->getInheritedPrivileges(), $role->getAssignedPrivileges());
        foreach ($privs as $priv) {
            if ($priv->getLevel() == 800) {
                $adminpriv = $priv->getID();
                break;
            }
        }
        if (!isset($adminpriv)) {
            throw new Exception('The designated site admin does not have administration privileges');
        }

        $dbconn   = xarDB::getConn();
        $xartable = xarDB::getTables();
        $acltable = xarDB::getPrefix() . '_security_acl';
        $query    = "SELECT role_id FROM $acltable
                     WHERE privilege_id  = ?";
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery([(int) $adminpriv]);

        // so now we have the list of all roles with *assigned* admin privileges
        // now we have to find which ones ar candidates for admin:
        // 1. They are users, not groups
        // 2. They inherit the admin privilege
        $admins = [];
        while ($result->next()) {
            [$id] = $result->fields;
            $role     = xarRoles::get($id);
            $admins[] = $role;
            $admins   = array_merge($admins, $role->getDescendants());
        }

        $siteadmins = [];
        $adminids = [];
        foreach ($admins as $admin) {
            if ($admin->isUser() && !in_array($admin->getID(), $adminids)) {
                $siteadmins[] = ['name' => $admin->getName(),
                    'id'   => $admin->getID(),
                ];
            }
        }

        $checkip = xarModVars::get('roles', 'disallowedips');
        if (empty($checkip)) {
            $ip = serialize('10.0.0.1'); // <mrb> why 10.0.0.1 ?
            xarModVars::set('roles', 'disallowedips', $ip);
        }
        $data['siteadmins']   = $siteadmins;
        $data['defaultgroup'] = (int) xarModVars::get('roles', 'defaultgroup');

        $hooks = [];

        switch ($data['tab']) {

            case 'hooks':
                $item = ['module' => 'roles', 'itemtype' => xarRoles::ROLES_USERTYPE];
                $hooks = xarHooks::notify('ModuleModifyconfig', $item);
                /*
                // Item type 1 is the default itemtype for 'user' roles.
                $hooks = xarModHooks::call('module', 'modifyconfig', 'roles',
                                         array('module' => 'roles',
                                               'itemtype' => xarRoles::ROLES_USERTYPE));
                */
                break;
            case 'grouphooks':
                $item = ['module' => 'roles', 'itemtype' => xarRoles::ROLES_GROUPTYPE];
                $hooks = xarHooks::notify('ModuleModifyconfig', $item);
                /*
                // Item type 2 is the itemtype for 'group' roles.
                $hooks = xarModHooks::call('module', 'modifyconfig', 'roles',
                                         array('module' => 'roles',
                                               'itemtype' => xarRoles::ROLES_GROUPTYPE));
                */
                break;
            case 'duvs':
                $data['user_settings'] = xarMod::apiFunc('base', 'admin', 'getusersettings', ['module' => 'roles', 'itemid' => 0]);
                $data['user_settings']->setFieldList('duvsettings');
                $data['user_settings']->getItem();
                break;
            default:
                $data['module_settings'] = xarMod::apiFunc('base', 'admin', 'getmodulesettings', ['module' => 'roles']);
                $data['module_settings']->setFieldList('items_per_page, use_module_alias, module_alias_name, enable_short_urls, enable_user_menu, user_menu_link');
                $data['module_settings']->getItem();

                $data['user_settings'] = xarMod::apiFunc('base', 'admin', 'getusersettings', ['module' => 'roles', 'itemid' => 0]);
                $settings = explode(',', xarModVars::get('roles', 'duvsettings'));
                $required = ['usereditaccount', 'primaryparent', 'allowemail', 'emailformat', 'requirevalidation', 'displayrolelist', 'searchbyemail'];
                $skiplist = ['userhome', 'passwordupdate', 'duvsettings', 'userlastlogin', 'usertimezone', 'useremailformat'];
                $homelist = ['allowuserhomeedit', 'allowexternalurl', 'loginredirect'];
                if (!in_array('userhome', $settings)) {
                    $skiplist = array_merge($skiplist, $homelist);
                } else {
                    $required = array_merge($required, $homelist);
                }
                $fieldlist = [];
                $extrafields = [];
                foreach ($data['user_settings']->properties as $fieldname => $propval) {
                    if (in_array($fieldname, $skiplist)) {
                        continue;
                    }
                    if (!in_array($fieldname, $required)) {
                        $extrafields[] = $fieldname;
                    }
                    $fieldlist[] = $fieldname;
                }
                $data['user_settings']->setFieldList(join(',', $fieldlist));
                $data['user_settings']->getItem();
                $data['fieldlist'] = $fieldlist;
                $data['extrafields'] = $extrafields;
                break;
        }

        $data['hooks'] = $hooks;
        $data['defaultauthmod']    = xarModVars::get('roles', 'defaultauthmodule');
        $data['defaultregmod']     = xarModVars::get('roles', 'defaultregmodule');
        $data['allowuserhomeedit'] = (bool) xarModVars::get('roles', 'allowuserhomeedit');
        $data['requirevalidation'] = (bool) xarModVars::get('roles', 'requirevalidation');

        switch (strtolower($phase)) {
            case 'modify':
                switch ($data['tab']) {
                    case 'debugging':
                        $debugadmins = [];
                        $candidates = xarConfigVars::get(null, 'Site.User.DebugAdmins');
                        foreach ($candidates as $candidate) {
                            try {
                                $admin = $userapi->get(['id' => (int) $candidate]);
                                if (!empty($admin)) {
                                    $debugadmins[] = $admin['uname'];
                                }
                            } catch (Exception $e) {
                            }
                        }
                        $data['debugadmins'] = implode(',', $debugadmins);
                        break;
                }
                // no break
            default:
                break;

            case 'update':
                // Confirm authorisation code
                if (!xarSec::confirmAuthKey()) {
                    return xarController::badRequest('bad_author', $this->getContext());
                }
                switch ($data['tab']) {
                    case 'general':
                        $this->var()->find('defaultauthmodule', $defaultauthmodule, 'int:1:', xarMod::getRegID('authsystem'));
                        $this->var()->find('defaultregmodule', $defaultregmodule, 'int:1:', '');
                        $this->var()->find('siteadmin', $siteadmin, 'int:1', (int) xarModVars::get('roles', 'admin'));
                        $this->var()->find('defaultgroup', $defaultgroup, 'str:1', 'Users');

                        $isvalid = $data['module_settings']->checkInput();
                        if (!$isvalid) {
                            $data['context'] ??= $this->getContext();
                            return xarTpl::module('roles', 'admin', 'modifyconfig', $data);
                        } else {
                            $itemid = $data['module_settings']->updateItem();
                        }

                        xarModVars::set('roles', 'defaultauthmodule', $defaultauthmodule);
                        xarModVars::set('roles', 'defaultregmodule', $defaultregmodule);
                        xarModVars::set('roles', 'defaultgroup', $defaultgroup);
                        xarModVars::set('roles', 'admin', $siteadmin);

                        // no break
                    case 'hooks':
                        // Role type 'user' (itemtype 1).
                        xarModHooks::call(
                            'module',
                            'updateconfig',
                            'roles',
                            ['module' => 'roles',
                                'itemtype' => xarRoles::ROLES_USERTYPE]
                        );
                        break;
                    case 'grouphooks':
                        // Role type 'group' (itemtype 2).
                        xarModHooks::call(
                            'module',
                            'updateconfig',
                            'roles',
                            ['module' => 'roles',
                                'itemtype' => xarRoles::ROLES_GROUPTYPE]
                        );
                        break;
                    case 'memberlist':
                    case 'duvs':
                        $isvalid = $data['user_settings']->checkInput();
                        if (!$isvalid) {
                            $data['context'] ??= $this->getContext();
                            return xarTpl::module('roles', 'admin', 'modifyconfig', $data);
                        } else {
                            $itemid = $data['user_settings']->updateItem();
                        }
                        break;
                    case 'debugging':
                        $this->var()->find('debugadmins', $candidates, 'str', '');

                        // Remove unwanted characters
                        $candidates = trim($candidates, " ,\n\r\t\v\0");

                        // Get the users to be shown the debug messages
                        if (empty($candidates)) {
                            $candidates = [];
                        } else {
                            $candidates = explode(',', $candidates);
                        }
                        $debugadmins = [];
                        foreach ($candidates as $candidate) {
                            $admin = $userapi->get(['uname' => $candidate]);
                            if (!empty($admin)) {
                                $debugadmins[] = (int) $admin['id'];
                            }
                        }
                        xarConfigVars::set(null, 'Site.User.DebugAdmins', $debugadmins);
                        break;
                }
                xarController::redirect(xarController::URL(
                    'roles',
                    'admin',
                    'modifyconfig',
                    ['tab' => $data['tab']]
                ), null, $this->getContext());
                break;
        }
        return $data;
    }
}
