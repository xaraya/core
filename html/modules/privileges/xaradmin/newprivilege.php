<?php

/**
 * newPrivilege - create a new privilege
 * Takes no parameters
 */
function privileges_admin_newprivilege()
{

    list($pid,
        $name,
        $realm,
        $module,
        $component,
        $instance,
        $level,
        $type,
        $show,
        $trees) = xarVarCleanFromInput('pid',
                                        'pname',
                                        'prealm',
                                        'pmodule',
                                        'pcomponent',
                                        'pinstance',
                                        'plevel',
                                        'ptype',
                                        'show',
                                        'trees');
// Clear Session Vars
    xarSessionDelVar('privileges_statusmsg');

// Security Check
    if(!xarSecurityCheck('AddPrivilege')) return;

// call the Privileges class
    $privs = new xarPrivileges();

// remove duplicate entries from the list of privileges
    $privileges = array();
    $names = array();
    $privileges[] = array('pid' => 0,
                            'name' => '');
    foreach($privs->getprivileges() as $temp){
        $nam = $temp['name'];
        if (!in_array($nam,$names)){
            $names[] = $nam;
            $privileges[] = $temp;
        }
    }

    //Load Template
    if(isset($name)) {$data['pid'] = $pid;}
    else {$data['pid'] = '';}

    if(isset($name)) {$data['pname'] = $name;}
    else {$data['pname'] = '';}

    if(isset($realm)) {$data['prealm'] = $realm;}
    else {$data['prealm'] = 'All';}

    if(isset($module)) {$data['pmodule'] = strtolower($module);}
    else {$data['pmodule'] = 'All';}

    if(isset($component)) {$data['pcomponent'] = $component;}
    else {$data['pcomponent'] = 'All';}

    if(isset($instance)) {$data['pinstance'] = $instance;}
    else {$data['pinstance'] = '';}

    if(isset($level)) {$data['plevel'] = $level;}
    else {$data['plevel'] = '';}

    if(isset($type)) {$data['ptype'] = $type;}
    else {$data['ptype'] = 'empty';}

    if(isset($pparentid)) {$data['pparentid'] = $pparentid;}
    else {$data['pparentid'] = '0';}

    if(isset($show)) {$data['show'] = $show;}
    else {$data['show'] = 'assigned';}

    $instances = $privs->getinstances($data['pmodule'],$data['pcomponent']);
// send to external wizard if necessary
    if (!empty($instances['external']) && $instances['external'] == "yes") {
//    xarResponseRedirect($instances['target'] . "&extpid=0&extname=$name&extrealm=$realm&extmodule=$module&extcomponent=$component&extlevel=$level");
//        return;
        $data['target'] = $instances['target'] . '&amp;extpid=0&amp;extname='.$name.'&amp;extrealm='.$realm.'&amp;extmodule='.$module.'&amp;extcomponent='.$component.'&amp;extlevel='.$level;
        $data['instances'] = array();
    } else {
        $data['instances'] = $instances;
    }

    include_once 'modules/privileges/xartreerenderer.php';
    $renderer = new xarTreeRenderer();

    $data['authid'] = xarSecGenAuthKey();
    $data['trees'] = $renderer->drawtrees($data['show']);
    $data['realms'] = $privs->getrealms();
    $data['modules'] = $privs->getmodules();
    $data['privileges'] = $privileges;
    $data['components'] = $privs->getcomponents($data['pmodule']);
    return $data;
}


?>