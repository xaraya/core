<?php

/**
 * newRole - create a new role
 * Takes no parameters
 * @author Marc Lutolf
 */
function roles_admin_newrole()
{
    list($name,
         $type,
         $uname,
         $email,
         $pass,
         $pparentid,
         $state) = xarVarCleanFromInput('pname',
                                       'ptype',
                                       'puname',
                                       'pemail',
                                       'ppass1',
                                       'pparentid',
                                       'pstate');
    // Security Check
    if(!xarSecurityCheck('AddRole')) return;

    //Call the Roles class
    // should be static, but apparently not doable in php?
    $roles = new xarRoles();


    $groups = array();
    $names = array();
    foreach($roles->getgroups() as $temp){
        $nam = $temp['name'];
        if (!in_array($nam,$names)){
            $names[] = $nam;
             $groups[] = $temp;
        }
    }

    //Load Template
    $item = array();
    $item['module'] = 'roles';
    $hooks = xarModCallHooks('item','new','',$item);
    if (empty($hooks) || !is_string($hooks)) {
        $data['hooks'] = '';
    } else {
        $data['hooks'] = $hooks;
    }

    if(isset($name)) {$data['pname'] = $name;}
    else {$data['pname'] = '';}

    if(isset($type)) {$data['ptype'] = $type;}
    else {$data['ptype'] = 1;}

    if(isset($uname)) {$data['puname'] = $uname;}
    else {$data['puname'] = '';}

    if(isset($email)) {$data['pemail'] = $email;}
    else {$data['pemail'] = '';}

    if(isset($pass)) {$data['ppass1'] = $pass;}
    else {$data['ppass1'] = '';}

    if(isset($state)) {$data['pstate'] = $state;}
    else {$data['pstate'] = 1;}

    if(isset($pparentid)) {$data['pparentid'] = $pparentid;}
    else {$data['pparentid'] = 1;}

    include_once 'modules/roles/xartreerenderer.php';
    $renderer = new xarTreeRenderer();

    $data['authid'] = xarSecGenAuthKey();
    $data['tree'] = $renderer->drawtree($renderer->maketree());
    $data['groups'] = $groups;
    return $data;
}

?>