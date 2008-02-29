<?php
/**
 * Displayprivilege - display privilege details
 * @package core modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 * @link http://xaraya.com/index.php/release/1098.html
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 *displayprivilege - display privilege details
 */
function privileges_admin_displayprivilege()
{
// Security Check
    if(!xarSecurityCheck('EditPrivilege')) return;

    if(!xarVarFetch('id',           'isset', $id,        NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pinstance',     'array', $instance,   array(), XARVAR_NOT_REQUIRED)) {return;}

//Call the Privileges class and get the privilege to be modified
    sys::import('modules.privileges.class.privileges');
    $priv = xarPrivileges::getPrivilege($id);

//Get the array of parents of this privilege
    $parents = array();
    foreach ($priv->getParents() as $parent) {
        $parents[] = array('parentid'=>$parent->getID(),
                                    'parentname'=>$parent->getName());
    }

// Load Template
    if(isset($id)) {$data['ppid'] = $id;}
    else {$data['ppid'] = $priv->getID();}

    $data['priv'] = $priv;
    $data['pname'] = $priv->getName();
    $data['prealm'] = $priv->getRealm();
    $data['pmodule'] = $priv->getModule();
    $data['pcomponent'] = $priv->getComponent();
    $data['plevel'] = $priv->getLevel();

    $instances = xarPrivileges::getinstances($data['pmodule'],$data['pcomponent']);
    $numInstances = count($instances); // count the instances to use in later loops

    $default = array();
    $data['instance'] = $priv->getInstance();

    $data['ptype'] = $priv->isEmpty() ? "empty" : "full";
    $data['parents'] = $parents;
    return $data;
}

?>
