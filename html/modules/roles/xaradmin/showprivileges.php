<?php

/**
 * showprivileges - display the privileges of this role
 * 
 * @author Marc Lutolf <marcinmilan@xaraya.com> 
 */
function roles_admin_showprivileges()
{
    if (!xarVarFetch('uid', 'int:1:', $uid)) return; 
    // Security Check
    if (!xarSecurityCheck('EditRole')) return; 
    // Call the Roles class and get the role
    $roles = new xarRoles();
    $role = $roles->getRole($uid); 
    // Call the Privileges class
    $privileges = new xarPrivileges(); 
    // Get the inherited privileges
    $ancestors = $role->getAncestors();
    $inherited = array(); 
    // this assembles the irreducuble set of privileges
    // needs to be moved to a method of the Role class
    foreach ($ancestors as $ancestor) {
        $privs = $ancestor->getAssignedPrivileges();
        $allprivileges = array();
        foreach ($privs as $priv) {
            $allprivileges = $privileges->winnow(array($priv), $allprivileges);
            $allprivileges = $privileges->winnow($priv->getAncestors(), $allprivileges);
        } 
        $groupname = $ancestor->getName();
        $groupid = $ancestor->getID();
        foreach($allprivileges as $priv) {
            array_push($inherited, array('privid' => $priv->getID(),
                    'name' => $priv->getName(),
                    'realm' => $priv->getRealm(),
                    'module' => $priv->getModule(),
                    'component' => $priv->getComponent(),
                    'instance' => $priv->getInstance(),
                    'level' => $privileges->levels[$priv->getLevel()],
                    'groupid' => $groupid,
                    'groupname' => $groupname));
        } 
    } 
    // resort the array for display purposes
    $inherited = array_reverse($inherited); 
    // get the array of objects of the assigned set of privileges
    $curprivs = $role->getAssignedPrivileges();
    $directassigned = array();
    $curprivileges = array(); 
    // for each one winnow the  assigned privileges and then the inherited
    foreach ($curprivs as $priv) {
        $directassigned[] = $priv->getID();
        $curprivileges = $privileges->winnow(array($priv), $curprivileges);
        $curprivileges = $privileges->winnow($priv->getDescendants(), $curprivileges);
    } 
    // extract the info for display by the template
    $currentprivileges = array();
    foreach ($curprivileges as $priv) {
        if ($priv->getModule() == "empty") {
            $currentprivileges[] = array('privid' => $priv->getID(),
                'name' => $priv->getName(),
                'realm' => "",
                'module' => "",
                'component' => "",
                'instance' => "",
                'level' => "");
        } else {
            $currentprivileges[] = array('privid' => $priv->getID(),
                'name' => $priv->getName(),
                'realm' => $priv->getRealm(),
                'module' => $priv->getModule(),
                'component' => $priv->getComponent(),
                'instance' => $priv->getInstance(),
                'level' => $privileges->levels[$priv->getLevel()]);
        } 
    } 
    $currentprivileges = array_reverse($currentprivileges);

    // Load Template
    $data['pname'] = $role->getName();
    $data['ptype'] = $role->getType();
    $data['roleid'] = $uid;
    $data['inherited'] = $inherited;
    $data['privileges'] = $currentprivileges;
    $data['directassigned'] = $directassigned;
    $data['allprivileges'] = $role->getAllPrivileges();
    $data['authid'] = xarSecGenAuthKey();
    $data['groups'] = $roles->getgroups();
    $data['removeurl'] = xarModURL('roles',
        'admin',
        'removeprivilege',
        array('roleid' => $uid));
    $data['groupurl'] = xarModURL('roles',
        'admin',
        'showprivileges');
    $data['addlabel'] = xarML('Add');
    return $data; 
    // redirect to the next page
    xarResponseRedirect(xarModURL('roles', 'admin', 'newrole'));
} 

?>