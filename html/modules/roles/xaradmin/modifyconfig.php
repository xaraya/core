<?php
/**
 * Modify configuration
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
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
            $data['emails'] = unserialize(xarModGetVar('roles', 'disallowedemails'));
            $data['names'] = unserialize(xarModGetVar('roles', 'disallowednames'));
            $data['ips'] = unserialize(xarModGetVar('roles', 'disallowedips'));
            $data['authid'] = xarSecGenAuthKey();
            $data['updatelabel'] = xarML('Update Roles Configuration');
            $data['uselockout'] =  xarModGetVar('roles', 'uselockout') ? 'checked' : '';
            $data['lockouttime'] = xarModGetVar('roles', 'lockouttime')? xarModGetVar('roles', 'lockouttime'): 15; //minutes
            $data['lockouttries'] = xarModGetVar('roles', 'lockouttries') ? xarModGetVar('roles', 'lockouttries'): 3;
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
                    break;
            }

            $data['hooks'] = $hooks;

            break;

        case 'update':
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) return;
            switch ($data['tab']) {
                case 'general':
                    if (!xarVarFetch('rolesperpage', 'str:1:4:', $rolesperpage, '20', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                    if (!xarVarFetch('shorturls', 'checkbox', $shorturls, false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('showterms', 'checkbox', $showterms, false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('showprivacy', 'checkbox', $showprivacy, false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('siteadmin', 'int:1', $siteadmin, xarModGetVar('roles','admin'), XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('uselockout', 'checkbox', $uselockout, true, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('lockouttime', 'int:1:', $lockouttime, 15, XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                    if (!xarVarFetch('lockouttries', 'int:1:', $lockouttries, 3, XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;

                    xarModSetVar('roles', 'rolesperpage', $rolesperpage);
                    xarModSetVar('roles', 'SupportShortURLs', $shorturls);
                    xarModSetVar('roles', 'showterms', $showterms);
                    xarModSetVar('roles', 'showprivacy', $showprivacy);
                    xarModSetVar('roles', 'admin', $siteadmin);
                    xarModSetVar('roles', 'uselockout', $uselockout);
                    xarModSetVar('roles', 'lockouttime', $lockouttime);
                    xarModSetVar('roles', 'lockouttries', $lockouttries);
                    break;
                case 'registration':
                    if (!xarVarFetch('defaultgroup', 'str:1', $defaultgroup, 'Users', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                    if (!xarVarFetch('allowregistration', 'checkbox', $allowregistration, false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('chooseownpassword', 'checkbox', $chooseownpassword, false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('minage', 'str:1:3:', $minage, '13', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                    if (!xarVarFetch('sendnotice', 'checkbox', $sendnotice, false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('explicitapproval', 'checkbox', $explicitapproval, false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('requirevalidation', 'checkbox', $requirevalidation, false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('showdynamic', 'checkbox', $showdynamic, false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('sendwelcomeemail', 'checkbox', $sendwelcomeemail, false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('minpasslength', 'int:1', $minpasslength, 5, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('uniqueemail', 'checkbox', $uniqueemail, xarModGetVar('roles', 'uniqueemail'), XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('roles', 'chooseownpassword', $chooseownpassword);
                    xarModSetVar('roles', 'defaultgroup', $defaultgroup);
                    xarModSetVar('roles', 'allowregistration', $allowregistration);
                    xarModSetVar('roles', 'minage', $minage);
                    xarModSetVar('roles', 'sendnotice', $sendnotice);
                    xarModSetVar('roles', 'explicitapproval', $explicitapproval);
                    xarModSetVar('roles', 'requirevalidation', $requirevalidation);
                    xarModSetVar('roles', 'showdynamic', $showdynamic);
                    xarModSetVar('roles', 'sendwelcomeemail', $sendwelcomeemail);
                    xarModSetVar('roles', 'minpasslength', $minpasslength);
                    xarModSetVar('roles', 'uniqueemail', $uniqueemail);
                    break;
                case 'filtering':
                    if (!xarVarFetch('disallowednames', 'str:1', $disallowednames, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                    if (!xarVarFetch('disallowedemails', 'str:1', $disallowedemails, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                    if (!xarVarFetch('disallowedips', 'str:1', $disallowedips, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                    $disallowednames = serialize($disallowednames);
                    xarModSetVar('roles', 'disallowednames', $disallowednames);

                    $disallowedemails = serialize($disallowedemails);
                    xarModSetVar('roles', 'disallowedemails', $disallowedemails);

                    $disallowedips = serialize($disallowedips);
                    xarModSetVar('roles', 'disallowedips', $disallowedips);
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
                    if (!xarVarFetch('searchbyemail', 'checkbox', $searchbyemail, false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('usersendemails', 'checkbox', $usersendemails, false, XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('roles', 'searchbyemail', $searchbyemail);
                    xarModSetVar('roles', 'usersendemails', $usersendemails);
                    break;
            }

//            if (!xarVarFetch('allowinvisible', 'checkbox', $allowinvisible, false, XARVAR_NOT_REQUIRED)) return;
            // Update module variables
//            xarModSetVar('roles', 'allowinvisible', $allowinvisible);

            xarResponseRedirect(xarModURL('roles', 'admin', 'modifyconfig',array('tab' => $data['tab'])));
            // Return
            return true;
            break;
    }
    return $data;
}
?>
