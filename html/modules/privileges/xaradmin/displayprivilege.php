<?php
/**
 *
 * Display privilege details
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 *displayprivilege - display privilege details
 */
function privileges_admin_displayprivilege()
{

    if(!xarVarFetch('pid',           'isset', $pid,          NULL, XARVAR_DONT_SET)) {return;}

    $i = 0;
    $instance = array();
    while ($pinstance = xarVarCleanFromInput('pinstance'.$i)) {
        $i++;
        $instance[] = $pinstance;
    }

// Security Check
    if(!xarSecurityCheck('ViewPrivileges')) return;

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

    $data['pname'] = $priv->getName();
    $data['prealm'] = $priv->getRealm();
    $data['pmodule'] = $priv->getModule();
    $data['pcomponent'] = $priv->getComponent();
    $data['plevel'] = $priv->getLevel();

    $instances = $privs->getinstances($data['pmodule'],$data['pcomponent']);
    $numInstances = count($instances); // count the instances to use in later loops

    $default = array();
    $data['instance'] = $priv->getInstance();

    $data['ptype'] = $priv->isEmpty() ? "empty" : "full";
    $data['parents'] = $parents;
    return $data;
}

?>