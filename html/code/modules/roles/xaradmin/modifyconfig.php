<?php
/**
 * Modify the configuration settings of this module
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * Modify the configuration settings of this module
 *
 * Standard GUI function to display and update the configuration settings of the module based on input data.
 *
 * @return mixed data array for the template display or output display string if invalid data submitted
 */
function roles_admin_modifyconfig()
{
    // Security
    if (!xarSecurityCheck('AdminRoles')) return;
    
    if (!xarVarFetch('phase', 'str:1:100', $phase,       'modify',  XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('tab',   'str:1:100', $data['tab'], 'general', XARVAR_NOT_REQUIRED)) return;

    // get a list of everyone with admin privileges
    // TODO: find a more elegant way to do this
    // first find the id of the admin privilege
    $role  = xarRoles::get(xarModVars::get('roles','admin'));
    $privs = array_merge($role->getInheritedPrivileges(),$role->getAssignedPrivileges());
    foreach ($privs as $priv)
    {
        if ($priv->getLevel() == 800)
        {
            $adminpriv = $priv->getID();
            break;
        }
    }
    if (!isset($adminpriv))
        throw new Exception('The designated site admin does not have administration privileges');

    $dbconn   = xarDB::getConn();
    $xartable = xarDB::getTables();
    $acltable = xarDB::getPrefix() . '_security_acl';
    $query    = "SELECT role_id FROM $acltable
                 WHERE privilege_id  = ?";
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery(array((int) $adminpriv));

    // so now we have the list of all roles with *assigned* admin privileges
    // now we have to find which ones ar candidates for admin:
    // 1. They are users, not groups
    // 2. They inherit the admin privilege
    $admins = array();
    while ($result->next())
    {
        list($id) = $result->fields;
        $role     = xarRoles::get($id);
        $admins[] = $role;
        $admins   = array_merge($admins,$role->getDescendants());
    }

    $siteadmins = array();
    $adminids = array();
   foreach ($admins as $admin)
    {
        if($admin->isUser() && !in_array($admin->getID(),$adminids)){
            $siteadmins[] = array('name' => $admin->getName(),
                             'id'   => $admin->getID()
                            );
        }
    }

    $checkip = xarModVars::get('roles', 'disallowedips');
    if (empty($checkip)) {
        $ip = serialize('10.0.0.1'); // <mrb> why 10.0.0.1 ?
        xarModVars::set('roles', 'disallowedips', $ip);
    }
    $data['siteadmins']   = $siteadmins;
    $data['defaultgroup'] = (int)xarModVars::get('roles', 'defaultgroup');

    $hooks = array();

    switch ($data['tab']) {

        case 'hooks':
            $item = array('module' => 'roles', 'itemtype' => xarRoles::ROLES_USERTYPE);
            $hooks = xarHooks::notify('ModuleModifyconfig', $item);
            /* 
            // Item type 1 is the default itemtype for 'user' roles.
            $hooks = xarModCallHooks('module', 'modifyconfig', 'roles',
                                     array('module' => 'roles',
                                           'itemtype' => xarRoles::ROLES_USERTYPE));
            */            
            break;
        case 'grouphooks':
            $item = array('module' => 'roles', 'itemtype' => xarRoles::ROLES_GROUPTYPE);
            $hooks = xarHooks::notify('ModuleModifyconfig', $item);
            /*
            // Item type 2 is the itemtype for 'group' roles.
            $hooks = xarModCallHooks('module', 'modifyconfig', 'roles',
                                     array('module' => 'roles',
                                           'itemtype' => xarRoles::ROLES_GROUPTYPE));
            */            
            break;
        case 'duvs':
            $data['user_settings'] = xarMod::apiFunc('base', 'admin', 'getusersettings', array('module' => 'roles', 'itemid' => 0));
            $data['user_settings']->setFieldList('duvsettings');
            $data['user_settings']->getItem();
        break;
        default:
            $data['module_settings'] = xarMod::apiFunc('base','admin','getmodulesettings',array('module' => 'roles'));
            $data['module_settings']->setFieldList('items_per_page, use_module_alias, module_alias_name, enable_short_urls, enable_user_menu, user_menu_link');
            $data['module_settings']->getItem();
            $data['user_settings'] = xarMod::apiFunc('base', 'admin', 'getusersettings', array('module' => 'roles', 'itemid' => 0));
            $settings = explode(',',xarModVars::get('roles', 'duvsettings'));
            $required = array('usereditaccount', 'allowemail', 'requirevalidation', 'displayrolelist', 'searchbyemail');
            $skiplist = array('userhome', 'primaryparent', 'passwordupdate', 'duvsettings', 'userlastlogin', 'emailformat', 'usertimezone');
            $homelist = array('allowuserhomeedit', 'allowexternalurl', 'loginredirect');
            if (!in_array('userhome', $settings)) {
                $skiplist = array_merge($skiplist, $homelist);
            } else {
                $required = array_merge($required, $homelist);
            }
            $fieldlist = array();
            $extrafields = array();
            foreach ($data['user_settings']->properties as $fieldname => $propval) {
                if (in_array($fieldname, $skiplist)) continue;
                if (!in_array($fieldname, $required)) {
                    $extrafields[] = $fieldname;
                }
                $fieldlist[] = $fieldname;
            }
            $data['user_settings']->setFieldList(join(',',$fieldlist));
            $data['user_settings']->getItem();
            $data['fieldlist'] = $fieldlist;
            $data['extrafields'] = $extrafields;
        break;
    }

    $data['hooks'] = $hooks;
    $data['defaultauthmod']    = xarModVars::get('roles', 'defaultauthmodule');
    $data['defaultregmod']     = xarModVars::get('roles', 'defaultregmodule');
    $data['allowuserhomeedit'] = (bool)xarModVars::get('roles', 'allowuserhomeedit');
    $data['requirevalidation'] = (bool)xarModVars::get('roles', 'requirevalidation');

    switch (strtolower($phase)) {
        case 'modify':
        default:
            break;

        case 'update':
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) {
                return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
            }
            switch ($data['tab']) {
                case 'general':
                    if (!xarVarFetch('defaultauthmodule', 'int:1:',   $defaultauthmodule, xarMod::getRegID('authsystem'), XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                    if (!xarVarFetch('defaultregmodule',  'int:1:',   $defaultregmodule, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                    if (!xarVarFetch('siteadmin',         'int:1',    $siteadmin,        (int)xarModVars::get('roles','admin'), XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('defaultgroup',      'str:1',    $defaultgroup,     'Users', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;

                    $isvalid = $data['module_settings']->checkInput();
                    if (!$isvalid) {
                        return xarTpl::module('roles','admin','modifyconfig', $data);
                    } else {
                        $itemid = $data['module_settings']->updateItem();
                    }

                    xarModVars::set('roles', 'defaultauthmodule', $defaultauthmodule);
                    xarModVars::set('roles', 'defaultregmodule', $defaultregmodule);
                    xarModVars::set('roles', 'defaultgroup', $defaultgroup);
                    xarModVars::set('roles', 'admin', $siteadmin);

                case 'hooks':
                    // Role type 'user' (itemtype 1).
                    xarModCallHooks('module', 'updateconfig', 'roles',
                                    array('module' => 'roles',
                                          'itemtype' => xarRoles::ROLES_USERTYPE));
                    break;
                case 'grouphooks':
                    // Role type 'group' (itemtype 2).
                    xarModCallHooks('module', 'updateconfig', 'roles',
                                    array('module' => 'roles',
                                          'itemtype' => xarRoles::ROLES_GROUPTYPE));
                    break;
                case 'memberlist':
                case 'duvs':
                    $isvalid = $data['user_settings']->checkInput();
                    if (!$isvalid) {
                        return xarTpl::module('roles','admin','modifyconfig', $data);
                    } else {
                        $itemid = $data['user_settings']->updateItem();
                    }
                    break;
                case 'debugging':
                    if (!xarVarFetch('debugadmins', 'str', $candidates, '', XARVAR_NOT_REQUIRED)) return;

                    // Get the users to be shown the debug messages
                    if (empty($candidates)) {
                        $candidates = array();
                    } else {
                        $candidates = explode(',',$candidates);
                    }
                    $debugadmins = array();
                    foreach ($candidates as $candidate) {
                        $admin = xarMod::apiFunc('roles','user','get',array('uname' => trim($candidate)));
                        if(!empty($admin)) $debugadmins[] = $admin['uname'];
                    }
                    xarConfigVars::set(null, 'Site.User.DebugAdmins', $debugadmins);
                break;
            }
            xarController::redirect(xarModURL('roles','admin','modifyconfig',array('tab' => $data['tab'])));
            break;
    }
    return $data;
}
?>
