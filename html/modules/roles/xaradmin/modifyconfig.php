<?php

/**
 * modify configuration
 */
function roles_admin_modifyconfig()
{
    // Security Check
    if (!xarSecurityCheck('AdminRole')) return;

    if (!xarVarFetch('phase', 'str:1:100', $phase, 'modify', XARVAR_NOT_REQUIRED)) return;

    switch (strtolower($phase)) {
        case 'modify':
        default: 
            // create the dropdown of groups for the template display
            // call the Roles class
            $roles = new xarRoles(); 
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
            $data['defaultgroup'] = xarModGetVar('roles', 'defaultgroup');
            $data['groups'] = $groups;
            $data['emails'] = unserialize(xarModGetVar('roles', 'disallowedemails'));
            $data['names'] = unserialize(xarModGetVar('roles', 'disallowednames'));
            $data['ips'] = unserialize(xarModGetVar('roles', 'disallowedips'));
            $data['authid'] = xarSecGenAuthKey();
            $data['updatelabel'] = xarML('Update Users Configuration');

            $hooks = xarModCallHooks('module', 'modifyconfig', 'roles',
                array('module' => 'roles'));
            if (empty($hooks) || !is_string($hooks)) {
                $data['hooks'] = '';
            } else {
                $data['hooks'] = $hooks;
            } 

            break;

        case 'update':
            if (!xarVarFetch('showterms', 'checkbox', $showterms, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('shorturls', 'checkbox', $shorturls, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('showprivacy', 'checkbox', $showprivacy, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('chooseownpassword', 'checkbox', $chooseownpassword, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('explicitapproval', 'checkbox', $explicitapproval, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('sendwelcomeemail', 'checkbox', $sendwelcomeemail, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('sendnotice', 'checkbox', $sendnotice, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('allowinvisible', 'checkbox', $allowinvisible, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('minage', 'str:1:3:', $minage, '13', XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('defaultgroup', 'str:1', $defaultgroup, 'Users', XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('rolesperpage', 'str:1:4:', $rolesperpage, '20', XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('disallowedemails', 'str:1', $disallowedemails, '', XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('disallowednames', 'str:1', $disallowednames, '', XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('disallowedips', 'str:1', $disallowedips, '', XARVAR_NOT_REQUIRED)) return; 
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) return; 
            // Update module variables
            xarModSetVar('roles', 'showterms', $showterms);
            xarModSetVar('roles', 'showprivacy', $showprivacy);
            xarModSetVar('roles', 'minage', $minage);
            xarModSetVar('roles', 'defaultgroup', $defaultgroup);
            xarModSetVar('roles', 'chooseownpassword', $chooseownpassword);
            xarModSetVar('roles', 'sendnotice', $sendnotice);
            xarModSetVar('roles', 'explicitapproval', $explicitapproval);
            xarModSetVar('roles', 'sendwelcomeemail', $sendwelcomeemail);
            xarModSetVar('roles', 'allowinvisible', $allowinvisible);
            xarModSetVar('roles', 'SupportShortURLs', $shorturls);
            xarModSetVar('roles', 'rolesperpage', $rolesperpage);

            $disallowednames = serialize($disallowednames);
            xarModSetVar('roles', 'disallowednames', $disallowednames);

            $disallowedemails = serialize($disallowedemails);
            xarModSetVar('roles', 'disallowedemails', $disallowedemails);

            $disallowedips = serialize($disallowedips);
            xarModSetVar('roles', 'disallowedips', $disallowedips);

            xarModCallHooks('module', 'updateconfig', 'roles',
                array('module' => 'roles'));

            xarResponseRedirect(xarModURL('roles', 'admin', 'modifyconfig')); 
            // Return
            return true;

            break;
    } 

    return $data;
} 

?>