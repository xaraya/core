<?php

/**
 * modifyrole - modify role details
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_admin_modifyrole()
{
    list($uid,
         $name,
         $type,
         $uname,
         $email,
         $pass,
         $state) = xarVarCleanFromInput('uid',
                                       'pname',
                                       'ptype',
                                       'puname',
                                       'pemail',
                                       'ppass',
                                       'pstate');

    // Security Check
    if(!xarSecurityCheck('EditRole')) return;

    //Call the Roles class and get the role to modify
    $roles = new xarRoles();
    $role = $roles->getRole($uid);

    // get the array of parents of this role
    // need to display this in the template
    $parents = array();
    foreach ($role->getParents() as $parent) {
        $parents[] = array('parentid'=>$parent->getID(),
                                    'parentname'=>$parent->getName());
    }

    // remove duplicate entries from the list of groups
    // get the array of all roles, minus the current one
    // need to display this in the template
    $groups = array();
    $names = array();
    foreach($roles->getgroups() as $temp){
        $nam = $temp['name'];
        if (!in_array($nam,$names) && $temp['uid'] != $uid){
            $names[] = $nam;
            $groups[] = array('duid' => $temp['uid'],
                              'dname' => $temp['name']);
        }
    }

    // Load Template
    if(isset($name)) {$data['pname'] = $name;}
    else {$data['pname'] = $role->getName();}

    if(isset($type)) {$data['ptype'] = $type;}
    else {$data['ptype'] = $role->getType();}

    if(isset($uname)) {$data['puname'] = $uname;}
    else {$data['puname'] = $role->getUser();}

    if(isset($email)) {$data['pemail'] = $email;}
    else {$data['pemail'] = $role->getEmail();}

    if(isset($pstate)) {$data['pstate'] = $pstate;}
    else {$data['pstate'] = $role->getState();}

    $data['uid'] = $uid;
    $data['groups'] = $groups;
    $data['parents'] = $parents;
    $data['authid'] = xarSecGenAuthKey();
    $data['tree'] = $roles->drawtree($roles->maketree());
    return $data;
}

?>