<?php
/**
 * @package modules\privileges
 * @subpackage privileges
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/1098.html
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * modifyprivilege - modify privilege details
 * @return array data for the template display
 */
function privileges_admin_modifyprivilege()
{
    // Security
    if(!xarSecurityCheck('EditPrivileges')) return;

    if(!xarVarFetch('id',            'isset', $id,           NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pname',         'isset', $name,         NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('prealm',        'isset', $realm,        NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pmodule',       'isset', $pmodule,      NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('pcomponent',    'isset', $component,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('poldcomponent', 'isset', $oldcomponent, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('ptype',         'isset', $type,         NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('plevel',        'isset', $level,        NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pinstance',     'array', $instance,     array(), XARVAR_NOT_REQUIRED)) {return;}

    if(!xarVarFetch('pparentid',     'isset', $pparentid,    NULL, XARVAR_DONT_SET)) {return;}

// Clear Session Vars
    xarSessionDelVar('privileges_statusmsg');

//Call the Privileges class and get the privilege to be modified
    sys::import('modules.privileges.class.privileges');
    $priv = xarPrivileges::getPrivilege($id);
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
    foreach(xarPrivileges::getprivileges() as $temp){
        $nam = $temp['name'];
        if (!in_array($nam,$names) && $temp['id'] != $id){
            $names[] = $nam;
            $privileges[] = $temp;
        }
    }

// Load Template
    if(isset($id)) {$data['ppid'] = $id;}
    else {$data['ppid'] = $priv->getID();}

    if(empty($name)) $name = $priv->getName();
    $data['pname'] = $name;

    // Security Check
    $data['frozen'] = !xarSecurityCheck('EditPrivileges',0,'Privileges',$name);

    if(isset($realm)) {$data['prealm'] = $realm;}
    else {$data['prealm'] = $priv->getRealm();}

    if(isset($pmodule)) {$data['pmodule'] = $pmodule;}
    else {$data['pmodule'] = $priv->getModule();}
    if (empty($data['pmodule'])) $data['pmodule'] ="empty";

    if(isset($component)) {$data['pcomponent'] = $component;}
    else {$data['pcomponent'] = $priv->getComponent();}

    if(isset($level)) {$data['plevel'] = $level;}
    else {$data['plevel'] = $priv->getLevel();}

    $instances = xarMod::apiFunc('privileges','admin','getinstances',array('module' => $data['pmodule'],'component' => $data['pcomponent']));
    $numInstances = count($instances); // count the instances to use in later loops

    if(count($instance) > 0) {$default = $instance;}
    else {
        $default = array();
        $inst = $priv->getInstance();
        if ($inst == "All") for($i=0; $i < $numInstances; $i++) $default[] = "All";
        else $default = explode(':',$priv->getInstance());
    }
// send to external wizard if necessary
    if (!empty($instances['external']) && $instances['external'] == "yes") {
        $data['target'] = $instances['target'] . '&amp;extpid='.$data['ppid'].'&amp;extname='.$data['pname'].'&amp;extrealm='.$data['prealm'].'&amp;extmodule='.$data['pmodule'].'&amp;extcomponent='.$data['pcomponent'].'&amp;extlevel='.$data['plevel'];
        $data['target'] .= '&amp;extinstance=' . urlencode(join(':',$default));
        $data['curinstance'] = join(':',$default);
        $data['instances'] = array();
    } else {
        for ($i=0; $i < $numInstances; $i++) {
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

    $accesslevels = SecurityLevel::$displayMap;
    unset($accesslevels[-1]);
    $data['levels'] = array();
    foreach ($accesslevels as $key => $value) $data['levels'][] = array('id' => $key, 'name' => $value);
    
    $data['oldcomponent'] = $component;
    $data['authid'] = xarSecGenAuthKey();
    $data['parents'] = $parents;
    $data['privileges'] = $privileges;
    $data['realms'] = xarPrivileges::getrealms();;
    $data['components'] = xarMod::apiFunc('privileges','admin','getcomponents',array('modid' => xarMod::getRegID($data['pmodule'])));
    $data['refreshlabel'] = xarML('Refresh');
    return $data;
}

?>
