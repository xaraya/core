<?php
/**
 * File: $Id$
 *
 * Modify configuration
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Xaraya Team
 */
/**
 * modify configuration
 */
function roles_admin_modifyconfig()
{
    // Security Check
    if (!xarSecurityCheck('AdminRole')) return;
    if (!xarVarFetch('phase', 'str:1:100', $phase, 'modify', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    switch (strtolower($phase)) {
        case 'modify':
        default:
            // get a list of everyone with admin privileges
            // TODO: find a more elegant way to do this
            // first find the id of the admin privilege
            $roles = new xarRoles();
            $role = $roles->getRole(xarModGetVar('roles','admin'));
            $privs = $role->getInheritedPrivileges();
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
                    WHERE xar_permid   = " . $adminpriv;
            $result =& $dbconn->Execute($query);
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
            $data['updatelabel'] = xarML('Update Users Configuration');

            // Item type 0 is the default itemtype for 'user' roles.
            $hooks = xarModCallHooks('module', 'modifyconfig', 'roles',
                array('module' => 'roles', 'itemtype' => 0));

            if (empty($hooks)) {
                $hooks = array();
            }

            $data['hooks'] = $hooks;

            break;

        case 'update':
            if (!xarVarFetch('showterms', 'checkbox', $showterms, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('shorturls', 'checkbox', $shorturls, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('showprivacy', 'checkbox', $showprivacy, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('siteadmin', 'int:1', $siteadmin, xarModGetVar('roles','admin'), XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('chooseownpassword', 'checkbox', $chooseownpassword, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('allowregistration', 'checkbox', $allowregistration, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('explicitapproval', 'checkbox', $explicitapproval, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('sendwelcomeemail', 'checkbox', $sendwelcomeemail, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('sendnotice', 'checkbox', $sendnotice, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('requirevalidation', 'checkbox', $requirevalidation, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('allowinvisible', 'checkbox', $allowinvisible, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('showdynamic', 'checkbox', $showdynamic, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('minage', 'str:1:3:', $minage, '13', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
            if (!xarVarFetch('minpasslength', 'int:1', $minpasslength, 5, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('defaultgroup', 'str:1', $defaultgroup, 'Users', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
            if (!xarVarFetch('rolesperpage', 'str:1:4:', $rolesperpage, '20', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
            if (!xarVarFetch('disallowedemails', 'str:1', $disallowedemails, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
            if (!xarVarFetch('disallowednames', 'str:1', $disallowednames, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
            if (!xarVarFetch('disallowedips', 'str:1', $disallowedips, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) return;
            // Update module variables
            xarModSetVar('roles', 'showterms', $showterms);
            xarModSetVar('roles', 'showprivacy', $showprivacy);
            xarModSetVar('roles', 'admin', $siteadmin);
            xarModSetVar('roles', 'allowregistration', $allowregistration);
            xarModSetVar('roles', 'minage', $minage);
            xarModSetVar('roles', 'minpasslength', $minpasslength);
            xarModSetVar('roles', 'defaultgroup', $defaultgroup);
            xarModSetVar('roles', 'chooseownpassword', $chooseownpassword);
            xarModSetVar('roles', 'sendnotice', $sendnotice);
            xarModSetVar('roles', 'requirevalidation', $requirevalidation);
            xarModSetVar('roles', 'explicitapproval', $explicitapproval);
            xarModSetVar('roles', 'sendwelcomeemail', $sendwelcomeemail);
            xarModSetVar('roles', 'allowinvisible', $allowinvisible);
            xarModSetVar('roles', 'showdynamic', $showdynamic);
            xarModSetVar('roles', 'SupportShortURLs', $shorturls);
            xarModSetVar('roles', 'rolesperpage', $rolesperpage);

            $disallowednames = serialize($disallowednames);
            xarModSetVar('roles', 'disallowednames', $disallowednames);

            $disallowedemails = serialize($disallowedemails);
            xarModSetVar('roles', 'disallowedemails', $disallowedemails);

            $disallowedips = serialize($disallowedips);
            xarModSetVar('roles', 'disallowedips', $disallowedips);

            // Role type 'user' (itemtype 0).
            xarModCallHooks('module', 'updateconfig', 'roles',
                array('module' => 'roles', 'itemtype' => 0));

            xarResponseRedirect(xarModURL('roles', 'admin', 'modifyconfig'));
            // Return
            return true;
            break;
    }
    return $data;
}
?>