<?php

/**
 * deleteRole - delete a role
 * prompts for confirmation
 */
function roles_admin_deleterole()
{
    list($uid, $confirmation) = xarVarCleanFromInput('uid', 'confirmation');

    // certain roles can't be deleted, for your own good
    if ($uid <= xarModGetVar('roles','frozenroles')) {
        $msg = xarML('This role cannot be deleted');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new DefaultUserException($msg));
        return;
    }

    //Call the Roles class
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

    // Security Check
    if(!xarSecurityCheck('DeleteRole')) return;

    $name = $role->getName();

    if (empty($confirmation)) {

        //Load Template
        $data['authid'] = xarSecGenAuthKey();
        $data['uid'] = $uid;
        $data['ptype'] = $role->getType();
        $data['name'] = $name;
        return $data;

    }
    else {

    // Check for authorization code
        if (!xarSecConfirmAuthKey()) return;

    //Try to remove the role and bail if an error was thrown
    if (!$role->remove()) {return;}

    // redirect to the next page
    xarResponseRedirect(xarModURL('roles', 'admin', 'newrole'));
    }
}


?>