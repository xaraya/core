<?php

/**
 * addprivilege - assign a privilege to role
 * This is an action page
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_admin_addprivilege()
{

    list($privid,
        $roleid) = xarVarCleanFromInput('privid',
                                        'roleid');
    // Check for authorization code
    if (!xarSecConfirmAuthKey()) return;

    //Call the Roles class and get the role
    $roles = new xarRoles();
    $role = $roles->getRole($roleid);

    //Call the Privileges class and get the privilege
    $privs = new xarPrivileges();
    $priv = $privs->getPrivilege($privid);

    //If this privilege is already assigned do nothing
    //Try to assign the privilege and bail if an error was thrown
    if (!$priv->isassigned($role)) {
        if (!$role->assignPrivilege($priv)) return;
    }

    // redirect to the next page
    xarResponseRedirect(xarModURL('roles',
                             'admin',
                             'showprivileges',
                             array('uid'=>$roleid)));
}

?>