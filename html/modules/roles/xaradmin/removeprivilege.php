<?php

/**
 * removeprivilege - remove a privilege
 * prompts for confirmation
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_admin_removeprivilege()
{
    list($privid,
        $roleid,
        $confirmation) = xarVarCleanFromInput('privid',
                                            'roleid',
                                            'confirmation');

    //Call the Roles class and get the role
    $roles = new xarRoles();
    $role = $roles->getRole($roleid);

    //Call the Privileges class and get the privilege
    $privs = new xarPrivileges();
    $priv = $privs->getPrivilege($privid);


    // some assignments can't be removed, for your own good
    if ((($roleid == 1) && ($privid == 1)) ||
        (($roleid == 2) && ($privid == 6)) ||
        (($roleid == 4) && ($privid == 2)))
        {
        $msg = xarML('This privilege cannot be removed');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION',
                       new DefaultUserException($msg));
        return;
    }

    // Security Check
    if(!xarSecurityCheck('EditRole')) return;

    // some info for the template display
    $rolename = $role->getName();
    $privname = $priv->getName();

    if (empty($confirmation)) {

        //Load Template
        $data['authid'] = xarSecGenAuthKey();
        $data['roleid'] = $roleid;
        $data['privid'] = $privid;
        $data['ptype'] = $role->getType();
        $data['privname'] = $privname;
        $data['rolename'] = $rolename;
        return $data;

    } else {

        // Check for authorization code
        if (!xarSecConfirmAuthKey()) return;

        //Try to remove the privilege and bail if an error was thrown
        if (!$role->removePrivilege($priv)) return;

        // redirect to the next page
        xarResponseRedirect(xarModURL('roles', 'admin', 'showprivileges', array('uid'=>$roleid)));
    }

}

?>
