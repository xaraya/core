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
 * viewroles - display the roles this privilege is assigned to
 * @return array<mixed>|string|void data for the template display
 */
function privileges_admin_viewroles()
{
    // Security
    if(!xarSecurity::check('EditRoles')) return;

    $data = array();

    if (!xarVar::fetch('id',  'isset', $id,          NULL,       xarVar::DONT_SET)) {return;}
    if (!xarVar::fetch('show', 'isset', $data['show'], 'assigned', xarVar::NOT_REQUIRED)) {return;}

    // Clear Session Vars
    xarSession::delVar('privileges_statusmsg');

    //Call the Privileges class and get the privilege
    sys::import('modules.privileges.class.privileges');
    $priv = xarPrivileges::getPrivilege($id);

    //Get the array of current roles this privilege is assigned to
    $curroles = array();
    foreach ($priv->getRoles() as $role) {
        array_push($curroles, array('roleid'=>$role->getID(),
                                    'name'=>$role->getName(),
                                    'itemtype'=>$role->getType(),
                                    'uname'=>$role->getUser(),
                                    'auth_module_id'=>$role->getAuthModule()));
    }

//Get the array of parents of this privilege
    $parents = array();
    foreach ($priv->getParents() as $parent) {
        $parents[] = array('parentid'=>$parent->getID(),
                                    'parentname'=>$parent->getName());
    }

    $data['pname'] = $priv->getName();
    $data['id'] = $id;
    $data['roles'] = $curroles;
    $data['removeurl'] = xarController::URL('privileges',
                             'admin',
                             'removerole',
                             array('id'=>$id));

    $data['parents'] = $parents;
    $data['groups'] = xarMod::apiFunc('roles','user','getallgroups');
    return $data;
}
