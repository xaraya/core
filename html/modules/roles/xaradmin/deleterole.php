<?php

/**
 * deleteRole - delete a role
 * prompts for confirmation
 */
function roles_admin_deleterole()
{
    // get parameters
    if (!xarVarFetch('uid', 'int:1:', $uid)) return;
    if (!xarVarFetch('confirmation', 'str:1:', $confirmation, '', XARVAR_NOT_REQUIRED)) return;

    // Call the Roles class
    $roles = new xarRoles();
    // get the role to be deleted
    $role = $roles->getRole($uid);
    $type = $role->isUser() ? 0 : 1;
    // The user API function is called.
    $data = xarModAPIFunc('roles',
        'user',
        'get',
        array('uid' => $uid, 'type' => $type));

    if ($data == false) return;

    $name = $role->getName();

// Security Check
    if(!xarSecurityCheck('DeleteRole',0,'Roles',$name)) return;

    if (empty($confirmation)) {
        // Load Template
        $data['authid'] = xarSecGenAuthKey();
        $data['uid'] = $uid;
        $data['ptype'] = $role->getType();
        $data['deletelabel'] = xarML('Delete');
        $data['name'] = $name;
        return $data;
    } else {
        // Check for authorization code
        if (!xarSecConfirmAuthKey()) return;
        // Check to make sure the user is not active on the site.
        $check = xarModAPIFunc('roles',
                              'user',
                              'getactive',
                              array('uid' => $uid));

        if (empty($check)) {
            // Try to remove the role and bail if an error was thrown
            if (!$role->remove()) return;
        } else {
            $msg = xarML('That user has a current active session', 'roles');
            xarExceptionSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            return;
        }

        // redirect to the next page
        xarResponseRedirect(xarModURL('roles', 'admin', 'viewroles'));
    }
}

?>