<?php

/**
 * modify configuration
 */
function roles_admin_modifyconfig()
{
// Security Check
    if(!xarSecurityCheck('AdminRole')) return;

    $phase = xarVarCleanFromInput('phase');

    if (empty($phase)){
        $phase = 'modify';
    }

    switch(strtolower($phase)) {

        case 'modify':
        default:

            // create the dropdown of groups for the template display
            // call the Roles class
            $roles = new xarRoles();

            // get the array of all groups
            // remove duplicate entries from the list of groups
            $groups = array();
            $names = array();
            foreach($roles->getgroups() as $temp){
                $nam = $temp['name'];
                if (!in_array($nam,$names)){
                    array_push($names,$nam);
                    array_push($groups,$temp);
                }
            }

            $checkip = xarModGetVar('roles', 'disallowedips');
            if (empty($checkip)){
                $ip = serialize('10.0.0.1');
                xarModSetVar('roles', 'disallowedips', $ip);
            }
            $data['defaultgroup'] = xarModGetVar('roles', 'defaultgroup');
            $data['groups'] = $groups;
            $data['emails'] = unserialize(xarModGetVar('roles', 'disallowedemails'));
            $data['names'] = unserialize(xarModGetVar('roles', 'disallowednames'));
            $data['ips'] = unserialize(xarModGetVar('roles', 'disallowedips'));
            $data['authid'] = xarSecGenAuthKey();

            $hooks = xarModCallHooks('module', 'modifyconfig', 'roles',
                                    array('module' => 'roles'));
            if (empty($hooks) || !is_string($hooks)) {
                $data['hooks'] = '';
            } else {
                $data['hooks'] = $hooks;
            }

            break;

        case 'update':
            list($showterms,
                 $showprivacy,
                 $shorturls,
                 $minage,
                 $defaultgroup,
                 $chooseownpassword,
                 $sendnotice,
                 $explicitapproval,
                 $sendwelcomeemail,
                 $allowinvisible,
                 $rolesperpage,
                 $disallowedips,
                 $disallowednames,
                 $disallowedemails) = xarVarCleanFromInput('showterms',
                                                           'showprivacy',
                                                           'shorturls',
                                                           'minage',
                                                           'defaultgroup',
                                                           'chooseownpassword',
                                                           'sendnotice',
                                                           'explicitapproval',
                                                           'sendwelcomeemail',
                                                           'allowinvisible',
                                                           'rolesperpage',
                                                           'disallowedips',
                                                           'disallowednames',
                                                           'disallowedemails');

            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) return;

            // Update module variables
            if (!isset($showterms)) {
                $showterms = 0;
            }
            xarModSetVar('roles', 'showterms', $showterms);

            if (!isset($showprivacy)) {
                $showprivacy = 0;
            }
            xarModSetVar('roles', 'showprivacy', $showprivacy);

            if (!isset($minage)) {
                $minage = 13;
            }
            xarModSetVar('roles', 'minage', $minage);

            if (!isset($defaultgroup)) {
                $defaultgroup = 'Users';
            }
            xarModSetVar('roles', 'defaultgroup', $defaultgroup);

            if (!isset($chooseownpassword)) {
                $chooseownpassword = 0;
            }
            xarModSetVar('roles', 'chooseownpassword', $chooseownpassword);

            if (!isset($sendconfirmationemail)) {
                $sendconfirmationemail = 0;
            }
            xarModSetVar('roles', 'sendnotice', $sendnotice);

            if (!isset($explicitapproval)) {
                $explicitapproval = 0;
            }
            xarModSetVar('roles', 'explicitapproval', $explicitapproval);

            if (!isset($sendwelcomeemail)) {
                $sendwelcomeemail = 0;
            }
            xarModSetVar('roles', 'sendwelcomeemail', $sendwelcomeemail);

            if (!isset($allowinvisible)) {
                $allowinvisible = 0;
            }
            xarModSetVar('roles', 'allowinvisible', $allowinvisible);

            if (empty($rolesperpage)) {
                $rolesperpage = 20;
            }

            if (!isset($shorturls)) {
                $shorturls = 0;
            }
            xarModSetVar('roles', 'SupportShortURLs', $shorturls);

            xarModSetVar('roles', 'rolesperpage', $rolesperpage);

            $disallowednames = serialize($disallowednames);
            xarModSetVar('roles', 'disallowednames', $disallowednames);

            $disallowedemails = serialize($disallowedemails);
            xarModSetVar('roles', 'disallowedemails', $disallowedemails);

            $disallowedips = serialize($disallowedips);
            xarModSetVar('roles', 'disallowedips', $disallowedips);

            xarModCallHooks('module','updateconfig','roles',
                           array('module' => 'roles'));

            xarResponseRedirect(xarModURL('roles', 'admin', 'modifyconfig'));

            // Return
            return true;

            break;
    }

    return $data;
}

?>