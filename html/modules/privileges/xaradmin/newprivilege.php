<?php

/**
 * newPrivilege - create a new privilege
 * Takes no parameters
 */
function privileges_admin_newprivilege()
{
    $data = array();
    
    if (!xarVarFetch('pid',        'id',  $data['pid'],        '',         XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('pname',      'str', $data['pname'],      '',         XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('prealm',     'str', $data['prealm'],     'All',      XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('pmodule',    'str', $module,             NULL,       XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('pcomponent', 'str', $data['pcomponent'], 'All',      XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('pinstance',  'str', $data['pinstance'],  '',         XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('plevel',     'str', $data['plevel'],     '',         XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('ptype',      'str', $data['ptype'],      'empty',    XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('show',       'str', $data['show'],       'assigned', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('trees',      'str', $trees,              NULL,       XARVAR_NOT_REQUIRED)) {return;}

    if ($module !== NULL) {$data['pmodule'] = strtolower($module);}
    else {$data['pmodule'] = 'All';}

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
    if(isset($pparentid)) {$data['pparentid'] = $pparentid;}
    else {$data['pparentid'] = '0';}

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

    $data['authid'] = xarSecGenAuthKey();
    $data['realms'] = $privs->getrealms();
    $data['modules'] = $privs->getmodules();
    $data['privileges'] = $privileges;
    $data['components'] = $privs->getcomponents($data['pmodule']);
    return $data;
}


?>
