<?php
/**
 * Displayprivilege - display privilege details
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
 *displayprivilege - display privilege details
 * @return array<mixed>|void data for the template display
 */
function privileges_admin_displayprivilege()
{
    // Security
    if(!xarSecurity::check('EditPrivileges')) return;

    if(!xarVar::fetch('id',           'isset', $id,        NULL, xarVar::DONT_SET)) {return;}
    if(!xarVar::fetch('pinstance',     'array', $instance,   array(), xarVar::NOT_REQUIRED)) {return;}

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

    $instances = xarMod::apiFunc('privileges','admin','getinstances',array('module' => $data['pmodule'],'component' => $data['pcomponent']));
    $numInstances = count($instances); // count the instances to use in later loops

    $default = array();
    $data['instance'] = $priv->getInstance();

    $data['ptype'] = $priv->isEmpty() ? "empty" : "full";
    $data['parents'] = $parents;
    return $data;
}
