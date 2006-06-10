<?php
/**
 * Modify configuration
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * modify configuration
 */
function roles_admin_modifyconfig()
{
    // Security Check
    if (!xarSecurityCheck('AdminRole')) return;
    if (!xarVarFetch('phase', 'str:1:100', $phase, 'modify', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('tab', 'str:1:100', $data['tab'], 'general', XARVAR_NOT_REQUIRED)) return;
    switch (strtolower($phase)) {
        case 'modify':
        default:
            // get a list of everyone with admin privileges
            // TODO: find a more elegant way to do this
            // first find the id of the admin privilege
            $roles = new xarRoles();
            $role = $roles->getRole(xarModGetVar('roles','admin'));
            $privs = array_merge($role->getInheritedPrivileges(),$role->getAssignedPrivileges());
            foreach ($privs as $priv)
            {
                if ($priv->getLevel() == 800)
                {
                    $adminpriv = $priv->getID();
                    break;
                }
            }

            $dbconn =& xarDBGetConn();
            $xartable =& xarDBGetTables();
            $acltable = xarDBGetSiteTablePrefix() . '_security_acl';
            $query = "SELECT xar_partid FROM $acltable
                    WHERE xar_permid   = ?";
            $result =& $dbconn->Execute($query, array((int) $adminpriv));
            if (!$result) return;


            // so now we have the list of all roles with *assigned* admin privileges
            // now we have to find which ones ar candidates for admin:
            // 1. They are users, not groups
            // 2. They inherit the admin privilege
            $admins = array();
            while (!$result->EOF)
            {
                list($id) = $result->fields;
                $role = $roles->getRole($id);
                $admins[] = $role;
                $admins = array_merge($admins,$role->getDescendants());
                $result->MoveNext();
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

            // create the dropdown of groups for the template display
            // get the array of all groups
            // remove duplicate entries from the list of groups
            $groups = array();
            $names = array();
            foreach($roles->getgroups() as $temp) {
                $nam = $temp['name'];
                if (!in_array($nam, $names)) {
                    array_push($names, $nam);
                    array_push($groups, $temp);
                }
            }

            $checkip = xarModGetVar('roles', 'disallowedips');
            if (empty($checkip)) {
                $ip = serialize('10.0.0.1');
                xarModSetVar('roles', 'disallowedips', $ip);
            }
            $data['siteadmins'] = $siteadmins;
            $data['defaultgroup'] = xarModGetVar('roles', 'defaultgroup');
            $data['groups'] = $groups;

            $data['authid'] = xarSecGenAuthKey();
            $data['updatelabel'] = xarML('Update Roles Configuration');
            $hooks = array();

            switch ($data['tab']) {

                case 'hooks':
                    // Item type 0 is the default itemtype for 'user' roles.
                    $hooks = xarModCallHooks('module', 'modifyconfig', 'roles',
                                             array('module' => 'roles',
                                                   'itemtype' => 0));
                    break;
                case 'grouphooks':
                    // Item type 1 is the (current) itemtype for 'group' roles.
                    $hooks = xarModCallHooks('module', 'modifyconfig', 'roles',
                                             array('module' => 'roles',
                                                   'itemtype' => 1));
                    break;
                default:
                    break;
            }

            $data['hooks'] = $hooks;
            $data['defaultauthmod'] = xarModGetVar('roles', 'defaultauthmodule');
            $data['defaultregmod'] = xarModGetVar('roles', 'defaultregmodule');
            $data['allowuserhomeedit'] = xarModGetVar('roles', 'allowuserhomeedit');
            $data['requirevalidation'] = xarModGetVar('roles', 'requirevalidation');
            //check for roles hook in case it's set independently elsewhere
            if (xarModIsHooked('roles', 'roles')) {
                xarModSetVar('roles','usereditaccount',true);
            } else {
                xarModSetVar('roles','usereditaccount',false);
            }

            break;

        case 'update':
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) return;
            switch ($data['tab']) {
                case 'general':
                    if (!xarVarFetch('itemsperpage', 'str:1:4:', $itemsperpage, '20', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                    if (!xarVarFetch('defaultauthmodule', 'str:1:', $defaultauthmodule, xarModGetIDFromName('authsystem'), XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                    if (!xarVarFetch('defaultregmodule', 'str:1:', $defaultregmodule, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                    if (!xarVarFetch('shorturls', 'checkbox', $shorturls, false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('siteadmin', 'int:1', $siteadmin, xarModGetVar('roles','admin'), XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('defaultgroup', 'str:1', $defaultgroup, 'Users', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;

                    xarModSetVar('roles', 'itemsperpage', $itemsperpage);
                    xarModSetVar('roles', 'defaultauthmodule', $defaultauthmodule);
                    xarModSetVar('roles', 'defaultregmodule', $defaultregmodule);                    
                    xarModSetVar('roles', 'defaultgroup', $defaultgroup);
                    xarModSetVar('roles', 'SupportShortURLs', $shorturls);
                    xarModSetVar('roles', 'admin', $siteadmin);
                    break;
                case 'hooks':
                    // Role type 'user' (itemtype 0).
                    xarModCallHooks('module', 'updateconfig', 'roles',
                                    array('module' => 'roles',
                                          'itemtype' => 0));
                    break;
                case 'grouphooks':
                    // Role type 'group' (itemtype 1).
                    xarModCallHooks('module', 'updateconfig', 'roles',
                                    array('module' => 'roles',
                                          'itemtype' => 1));
                    break;
                case 'memberlist':
                    if (!xarVarFetch('searchbyemail', 'checkbox', $searchbyemail, false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('displayrolelist', 'checkbox', $displayrolelist, false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('usersendemails', 'checkbox', $usersendemails, false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('usereditaccount', 'checkbox', $usereditaccount, true, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('userhomeedit', 'checkbox', $userhomeedit, false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('allowexternalurl', 'checkbox', $allowexternalurl, false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('loginredirect', 'checkbox', $loginredirect, true, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('requirevalidation', 'checkbox', $requirevalidation, true, XARVAR_NOT_REQUIRED)) return;
                   
                    xarModSetVar('roles', 'searchbyemail', $searchbyemail); //search by email
                    xarModSetVar('roles', 'usersendemails', $usersendemails);
                    xarModSetVar('roles', 'displayrolelist', $displayrolelist); //display member list in Roles menu links
                    xarModSetVar('roles', 'usereditaccount', $usereditaccount); //allow users to edit account
                    xarModSetVar('roles', 'allowexternalurl', $allowexternalurl); //allow users to set external urls for home page
                    xarModSetVar('roles', 'loginredirect', $loginredirect); //search by email
                    xarModSetVar('roles', 'requirevalidation', $requirevalidation); //require revalidation if email changed
                    if (xarModGetVar('roles', 'setuserhome')==true) { //we only want to allow option of users editing home page if we are using homepages
                       $allowuserhomeedit = $userhomeedit ==true ? true:false;
                    }else {
                        $allowuserhomeedit=false;
                    }
                    xarModSetVar('roles', 'allowuserhomeedit', $allowuserhomeedit); //allow users to set their own homepage
                    if ($usereditaccount) {
                        //check and hook Roles to roles if not already hooked
                         if (!xarModIsHooked('roles', 'roles')) {
                         xarModAPIFunc('modules','admin','enablehooks',
                                 array('callerModName' => 'roles',
                                       'hookModName' => 'roles'));
                         }
                    } else {
                         //unhook roles from roles
                         if (xarModIsHooked('roles', 'roles')) {
                         xarModAPIFunc('modules','admin','disablehooks',
                                 array('callerModName' => 'roles',
                                       'hookModName' => 'roles'));
                         }
                   }
                    break;
            }

//            if (!xarVarFetch('allowinvisible', 'checkbox', $allowinvisible, false, XARVAR_NOT_REQUIRED)) return;
            // Update module variables
//            xarModSetVar('roles', 'allowinvisible', $allowinvisible);

            xarResponseRedirect(xarModURL('roles', 'admin', 'modifyconfig',array('tab' => $data['tab'])));
            // Return
            return true;
            break;

        case 'links':
            switch ($data['tab']) {
                case 'duvs':
                    $duvarray = array('setuserhome'=>'userhome',
                                      'setprimaryparent'=>'primaryparent',
                                      'setpasswordupdate'=>'passwordupdate',
                                      'setuserlastlogin' =>'userlastlogin',
                                      'settimezone'=>'timezone');
                    foreach ($duvarray as $duv=>$userduv) {
                        if (!xarVarFetch($duv, 'int', $$duv, null, XARVAR_DONT_SET)) return;
                        if (isset($$duv)) {
                            if ($$duv) {
                                xarModSetVar('roles',$duv,true);
                                if ($userduv =='primaryparent') { // let us set it to the default Role
                                    $defaultrole=xarModGetVar('roles','defaultgroup');
                                    xarModSetVar('roles','primaryparent', $defaultrole);
                                }else {
                                   xarModSetVar('roles',$userduv,'');
                                }
                            } else {
                                xarModSetVar('roles',$duv,false);
                            }
                        }
                    }
                    break;
                }
        break;
    }


    $data['authid'] = xarSecGenAuthKey();
    return $data;
}
?>
