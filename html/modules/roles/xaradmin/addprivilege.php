<?php

/**
 * addprivilege - assign a privilege to role
 * This is an action page
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_admin_addprivilege()
{
    // get parameters
    if (!xarVarFetch('privid', 'int:1:', $privid)) return;
    if (!xarVarFetch('roleid', 'int:1:', $roleid)) return;

    // Check for authorization code
    if (!xarSecConfirmAuthKey()) return;

    // Call the Roles class and get the role
    $roles = new xarRoles();
    $role = $roles->getRole($roleid);

    // Call the Privileges class and get the privilege
    $privs = new xarPrivileges();
    $priv = $privs->getPrivilege($privid);

    //Security Check
     if(!xarSecurityCheck('AssignPrivilege',0,'Privileges',$priv->getName()) return;

    // If this privilege is already assigned do nothing
    // Try to assign the privilege and bail if an error was thrown
    if (!$priv->isassigned($role)) {
        if (!$role->assignPrivilege($priv)) return;
    }
    // redirect to the next page
    xarResponseRedirect(xarModURL('roles',
            'admin',
            'showprivileges',
            array('uid' => $roleid)));
}
?>