<?php

/**
 * modifyprivilege - modify privilege details
 */
function privileges_admin_modifyprivilege()
{
    if(!xarVarFetch('pid',           'isset', $pid,           NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('pname',         'isset', $name,          NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('prealm',        'isset', $realm,         NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('pmodule',       'isset', $module,        NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('pcomponent',    'isset', $component,     NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('poldcomponent', 'isset', $oldcomponent,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('ptype',         'isset', $type,          NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('plevel',        'isset', $level,         NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('show',          'isset', $show,          NULL, XARVAR_NOT_REQUIRED)) {return;}


    if(!xarVarFetch('pid',           'str', $pid,          NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('pname',         'str', $name,         NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('prealm',        'str', $realm,        NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('pmodule',       'str', $module,       NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('pcomponent',    'str', $component,    NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('poldcomponent', 'str', $oldcomponent, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('ptype',         'str', $type,         NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('plevel',        'str', $level,        NULL, XARVAR_NOT_REQUIRED)) {return;}


    if(!xarVarFetch('pparentid',  'str', $pparentid,  NULL, XARVAR_NOT_REQUIRED)) {return;}


    $i = 0;
    $instance = array();
    while ($pinstance = xarVarCleanFromInput('pinstance'.$i)) {
        $i++;
        $instance[] = $pinstance;
    }

// Clear Session Vars
    xarSessionDelVar('privileges_statusmsg');

// Security Check
    if(!xarSecurityCheck('EditPrivilege')) return;

//Call the Privileges class and get the privilege to be modified
    $privs = new xarPrivileges();
    $priv = $privs->getPrivilege($pid);

//Get the array of parents of this privilege
    $parents = array();
    foreach ($priv->getParents() as $parent) {
        $parents[] = array('parentid'=>$parent->getID(),
                                    'parentname'=>$parent->getName());
    }

// remove duplicate entries from the list of privileges
//Get the array of all privileges, minus the current one
// need this for the dropdown display
    $privileges = array();
    $names = array();
    foreach($privs->getprivileges() as $temp){
        $nam = $temp['name'];
        if (!in_array($nam,$names) && $temp['pid'] != $pid){
            $names[] = $nam;
            $privileges[] = $temp;
        }
    }

// Load Template
    if(isset($pid)) {$data['ppid'] = $pid;}
    else {$data['ppid'] = $priv->getID();}

    if(isset($name)) {$data['pname'] = $name;}
    else {$data['pname'] = $priv->getName();}

    if(isset($realm)) {$data['prealm'] = $realm;}
    else {$data['prealm'] = $priv->getRealm();}

    if(isset($module)) {$data['pmodule'] = strtolower($module);}
    else {$data['pmodule'] = $priv->getModule();}

    if(isset($component)) {$data['pcomponent'] = $component;}
    else {$data['pcomponent'] = $priv->getComponent();}

    if(isset($level)) {$data['plevel'] = $level;}
    else {$data['plevel'] = $priv->getLevel();}

    $instances = $privs->getinstances($data['pmodule'],$data['pcomponent']);

    if(count($instance) >0 ) {$default = $instance;}
    else {
        $default = array();
        $inst = $priv->getInstance();
        if ($inst == "All") for($i=0;$i<count($instances);$i++) $default[] = "All";
        else $default = explode(':',$priv->getInstance());
    }

// send to external wizard if necessary
    if (!empty($instances['external']) && $instances['external'] == "yes") {
//    xarResponseRedirect($instances['target'] . "&extpid=$pid&extname=$name&extrealm=$realm&extmodule=$module&extcomponent=$component&extlevel=$level");
//        return;
        $data['target'] = $instances['target'] . '&amp;extpid='.$data['ppid'].'&amp;extname='.$data['pname'].'&amp;extrealm='.$data['prealm'].'&amp;extmodule='.$data['pmodule'].'&amp;extcomponent='.$data['pcomponent'].'&amp;extlevel='.$data['plevel'];
        $data['target'] .= '&amp;extinstance=' . urlencode(join(':',$default));
        $data['curinstance'] = join(':',$default);
        $data['instances'] = array();
    } else {
        for ($i=0;$i<count($instances);$i++) {
            if($component == ''|| ($component == $oldcomponent)) {
                $instances[$i]['default'] = $default[$i];}
            else {
                $instances[$i]['default'] = '';}
            }
        $data['instances'] = $instances;
    }

    if(isset($type)) {$data['ptype'] = $type;}
    else {$data['ptype'] = $priv->isEmpty() ? "empty" : "full";}

    if(isset($show)) {$data['show'] = $show;}
    else {$data['show'] = 'assigned';}

    include_once 'modules/privileges/xartreerenderer.php';
    $renderer = new xarTreeRenderer();

    $data['oldcomponent'] = $component;
    $data['authid'] = xarSecGenAuthKey();
    $data['parents'] = $parents;
    $data['privileges'] = $privileges;
    $data['realms'] = $privs->getrealms();
    $data['modules'] = $privs->getmodules();
    $data['components'] = $privs->getcomponents($data['pmodule']);
    $data['refreshlabel'] = xarML('Refresh');
    return $data;
}

?>
