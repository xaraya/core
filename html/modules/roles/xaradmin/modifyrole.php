<?php

/**
 * modifyrole - modify role details
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_admin_modifyrole()
{
    if (!xarVarFetch('uid', 'int:1:', $uid)) return;
    if (!xarVarFetch('pname', 'str:1:', $name, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('ptype', 'str:1', $type, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('puname', 'str:1:35:', $uname, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pemail', 'str:1:', $email, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('ppass', 'str:1:', $pass, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('state', 'str:1:', $state, '', XARVAR_NOT_REQUIRED)) return;
    // Call the Roles class and get the role to modify
    $roles = new xarRoles();
    $role = $roles->getRole($uid);
    // get the array of parents of this role
    // need to display this in the template
    $parents = array();
    foreach ($role->getParents() as $parent) {
        $parents[] = array('parentid' => $parent->getID(),
            'parentname' => $parent->getName());
    }
    // remove duplicate entries from the list of groups
    // get the array of all roles, minus the current one
    // need to display this in the template
    $groups = array();
    $names = array();
    foreach($roles->getgroups() as $temp) {
        $nam = $temp['name'];
        if (!in_array($nam, $names) && $temp['uid'] != $uid) {
            $names[] = $nam;
            $groups[] = array('duid' => $temp['uid'],
                'dname' => $temp['name']);
        }
    }
    // Load Template
    if (empty($name)) $name = $role->getName();
    $data['pname'] = $name;

    // Security Check
    $data['frozen'] = !xarSecurityCheck('EditRole',0,'Roles',$name);

    if (!empty($type)) {
        $data['ptype'] = $type;
    } else {
        $data['ptype'] = $role->getType();
    }

    if (!empty($uname)) {
        $data['puname'] = $uname;
    } else {
        $data['puname'] = $role->getUser();
    }

    if (!empty($email)) {
        $data['pemail'] = $email;
    } else {
        $data['pemail'] = $role->getEmail();
    }

    if (!empty($pstate)) {
        $data['pstate'] = $pstate;
    } else {
        $data['pstate'] = $role->getState();
    }

    // call item modify hooks (for DD etc.)
    $item = $data;
    $item['module']= 'roles';
    $item['itemtype'] = $data['ptype']; // we might have something separate for groups later on
    $item['itemid']= $uid;
    $data['hooks'] = xarModCallHooks('item', 'modify', $uid, $item);

    $data['uid'] = $uid;
    $data['groups'] = $groups;
    $data['parents'] = $parents;
    $data['updatelabel'] = xarML('Update');
    $data['addlabel'] = xarML('Add');
    $data['authid'] = xarSecGenAuthKey();
    return $data;
}

?>
