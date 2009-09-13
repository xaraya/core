<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * modify configuration
 */
function roles_admin_modifyconfig()
{
    // Security Check
    if (!xarSecurityCheck('AdminRole')) return;
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
            // Item type 0 is the default itemtype for 'user' roles.
            $hooks = xarModCallHooks('module', 'modifyconfig', 'roles',
                                     array('module' => 'roles',
                                           'itemtype' => ROLES_USERTYPE));
            break;
        case 'grouphooks':
            // Item type 1 is the (current) itemtype for 'group' roles.
            $hooks = xarModCallHooks('module', 'modifyconfig', 'roles',
                                     array('module' => 'roles',
                                           'itemtype' => ROLES_GROUPTYPE));
            break;
        default:
            $data['module_settings'] = xarModAPIFunc('base','admin','getmodulesettings',array('module' => 'roles'));
            $data['module_settings']->setFieldList('items_per_page, use_module_alias, module_alias_name, enable_short_urls, enable_user_menu');
            $data['module_settings']->getItem();
            break;
    }

    $data['hooks'] = $hooks;
    $data['defaultauthmod']    = xarModVars::get('roles', 'defaultauthmodule');
    $data['defaultregmod']     = xarModVars::get('roles', 'defaultregmodule');
    $data['allowuserhomeedit'] = (bool)xarModVars::get('roles', 'allowuserhomeedit');
    $data['requirevalidation'] = (bool)xarModVars::get('roles', 'requirevalidation');
    //check for roles hook in case it's set independently elsewhere
    if (xarModIsHooked('roles', 'roles')) {
        xarModVars::set('roles','usereditaccount',true);
    } else {
        xarModVars::set('roles','usereditaccount',false);
    }

    switch (strtolower($phase)) {
        case 'modify':
        default:
            break;

        case 'update':
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) {
                return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
            }        
            switch ($data['tab']) {
                case 'general':
                    if (!xarVarFetch('defaultauthmodule', 'str:1:',   $defaultauthmodule, xarMod::getRegID('authsystem'), XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                    if (!xarVarFetch('defaultregmodule',  'str:1:',   $defaultregmodule, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                    if (!xarVarFetch('siteadmin',         'int:1',    $siteadmin,        (int)xarModVars::get('roles','admin'), XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('defaultgroup',      'str:1',    $defaultgroup,     'Users', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;

                    $isvalid = $data['module_settings']->checkInput();
                    if (!$isvalid) {
                        return xarTplModule('roles','admin','modifyconfig', $data);        
                    } else {
                        $itemid = $data['module_settings']->updateItem();
                    }

                    xarModVars::set('roles', 'defaultauthmodule', $defaultauthmodule);
                    xarModVars::set('roles', 'defaultregmodule', $defaultregmodule);
                    xarModVars::set('roles', 'defaultgroup', $defaultgroup);
                    xarModVars::set('roles', 'admin', $siteadmin);

                    // Adjust the usermenu hook according to the setting
                    sys::import('xaraya.structures.hooks.observer');
                    $observer = new BasicObserver('roles','user','usermenu');
                    $subject = new HookSubject('roles');
                    if (xarModVars::get('themes','enable_user_menu')) {
                        $subject->attach($observer);
                    } else {
                        $subject->detach($observer);
                    }
                    break;

                case 'hooks':
                    // Role type 'user' (itemtype 1).
                    xarModCallHooks('module', 'updateconfig', 'roles',
                                    array('module' => 'roles',
                                          'itemtype' => ROLES_USERTYPE));
                    break;
                case 'grouphooks':
                    // Role type 'group' (itemtype 2).
                    xarModCallHooks('module', 'updateconfig', 'roles',
                                    array('module' => 'roles',
                                          'itemtype' => ROLES_GROUPTYPE));
                    break;
                case 'memberlist':
                    if (!xarVarFetch('searchbyemail',    'checkbox', $searchbyemail,     false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('displayrolelist',  'checkbox', $displayrolelist,   false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('allowemail',       'checkbox', $allowemail,        false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('usereditaccount',  'checkbox', $usereditaccount,   true,  XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('userhomeedit',     'checkbox', $userhomeedit,      false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('allowexternalurl', 'checkbox', $allowexternalurl,  false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('loginredirect',    'checkbox', $loginredirect,     true,  XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('requirevalidation','checkbox', $requirevalidation, true,  XARVAR_NOT_REQUIRED)) return;

                    xarModVars::set('roles', 'searchbyemail', $searchbyemail); //search by email
                    xarModVars::set('roles', 'allowemail', $allowemail);
                    xarModVars::set('roles', 'displayrolelist', $displayrolelist); //display member list in Roles menu links
                    xarModVars::set('roles', 'usereditaccount', $usereditaccount); //allow users to edit account
                    xarModVars::set('roles', 'allowexternalurl', $allowexternalurl); //allow users to set external urls for home page
                    xarModVars::set('roles', 'loginredirect', $loginredirect); //search by email
                    xarModVars::set('roles', 'requirevalidation', $requirevalidation); //require revalidation if email changed
                    if (xarModVars::get('roles', 'setuserhome')==true) { //we only want to allow option of users editing home page if we are using homepages
                       $allowuserhomeedit = $userhomeedit ==true ? true:false;
                    }else {
                        $allowuserhomeedit=false;
                    }
                    xarModVars::set('roles', 'allowuserhomeedit', $allowuserhomeedit); //allow users to set their own homepage
                    if ($usereditaccount) {
                        //check and hook Roles to roles if not already hooked
                         if (!xarModIsHooked('roles', 'roles')) {
                         xarModAPIFunc('modules','admin','enablehooks',
                                 array('callerModName' => 'roles',
                                       'hookModName'   => 'roles'));
                         }
                    } else {
                         //unhook roles from roles
                         if (xarModIsHooked('roles', 'roles')) {
                         xarModAPIFunc('modules','admin','disablehooks',
                                 array('callerModName' => 'roles',
                                       'hookModName'   => 'roles'));
                         }
                   }
                    break;
                case 'duvs':
                    if (!xarVarFetch('duvsettings', 'array', $duvs, array(), XARVAR_DONT_SET)) return;
                    $settings = array();
                    foreach ($duvs as $duv) $settings[] = $duv;
                    xarModVars::set('roles','duvsettings', serialize($settings));
                    break;
            }
//            if (!xarVarFetch('allowinvisible', 'checkbox', $allowinvisible, false, XARVAR_NOT_REQUIRED)) return;
            // Update module variables
//            xarModVars::set('roles', 'allowinvisible', $allowinvisible);
            break;
    }

    return $data;
}
?>
