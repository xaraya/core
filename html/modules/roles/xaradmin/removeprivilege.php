<?php
/**
 * File: $Id$
 *
 * Remove a privilege
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * removeprivilege - remove a privilege
 * prompts for confirmation
 * 
 * @author Marc Lutolf <marcinmilan@xaraya.com> 
 */
function roles_admin_removeprivilege()
{
    if (!xarVarFetch('privid', 'int:1:', $privid)) return;
    if (!xarVarFetch('roleid', 'int:1:', $roleid)) return;
    if (!xarVarFetch('confirmation', 'str:1:', $confirmation, '', XARVAR_NOT_REQUIRED)) return; 
    // Call the Roles class and get the role
    $roles = new xarRoles();
    $role = $roles->getRole($roleid); 
    // Call the Privileges class and get the privilege
    $privs = new xarPrivileges();
    $priv = $privs->getPrivilege($privid); 
    // some assignments can't be removed, for your own good
    if ((($roleid == 1) && ($privid == 1)) ||
            (($roleid == 2) && ($privid == 6)) ||
            (($roleid == 4) && ($privid == 2))) {
        $msg = xarML('This privilege cannot be removed');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION',
            new DefaultUserException($msg));
        return;
    } 
    // Security Check
    if (!xarSecurityCheck('EditRole')) return; 
    // some info for the template display
    $rolename = $role->getName();
    $privname = $priv->getName();

    if (empty($confirmation)) {
        // Load Template
        $data['authid'] = xarSecGenAuthKey();
        $data['roleid'] = $roleid;
        $data['privid'] = $privid;
        $data['ptype'] = $role->getType();
        $data['privname'] = $privname;
        $data['rolename'] = $rolename;
        $data['removelabel'] = xarML('Remove');
        return $data;
    } else {
        // Check for authorization code
        if (!xarSecConfirmAuthKey()) return; 
        // Try to remove the privilege and bail if an error was thrown
        if (!$role->removePrivilege($priv)) return;

        // call update hooks and let them know that the role has changed
        $pargs['module'] = 'roles';
        $pargs['itemid'] = $roleid;
        xarModCallHooks('item', 'update', $roleid, $pargs);

        // redirect to the next page
        xarResponseRedirect(xarModURL('roles', 'admin', 'showprivileges', array('uid' => $roleid)));
    } 
} 

?>
