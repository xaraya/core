<?php
/**
 * Display the roles this privilege is assigned to
 *
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
 * viewroles - display the roles this privilege is assigned to
 */
function privileges_admin_viewroles()
{
    // Security Check
    if(!xarSecurityCheck('EditRole')) return;

    $data = array();

    if (!xarVarFetch('pid',  'isset', $pid,          NULL,       XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('show', 'isset', $data['show'], 'assigned', XARVAR_NOT_REQUIRED)) {return;}

    // Clear Session Vars
    xarSessionDelVar('privileges_statusmsg');

    //Call the Privileges class and get the privilege
    $privs = new xarPrivileges();
    $priv = $privs->getPrivilege($pid);

    //Get the array of current roles this privilege is assigned to
    $curroles = array();
    foreach ($priv->getRoles() as $role) {
        array_push($curroles, array('roleid'=>$role->getID(),
                                    'name'=>$role->getName(),
                                    'type'=>$role->getType(),
                                    'uname'=>$role->getUser(),
                                    'pass'=>$role->getPass(),
                                    'auth_module'=>$role->getAuthModule()));
    }

    // Load Template
    include_once 'modules/privileges/xartreerenderer.php';
    $renderer = new xarTreeRenderer();

//Get the array of parents of this privilege
    $parents = array();
    foreach ($priv->getParents() as $parent) {
        $parents[] = array('parentid'=>$parent->getID(),
                                    'parentname'=>$parent->getName());
    }

    $data['pname'] = $priv->getName();
    $data['pid'] = $pid;
    $data['roles'] = $curroles;
    //    $data['allgroups'] = $roles->getAllPrivileges();
    $data['removeurl'] = xarModURL('privileges',
                             'admin',
                             'removerole',
                             array('pid'=>$pid));
    $data['trees'] = $renderer->drawtrees($data['show']);
    $data['parents'] = $parents;
    $data['groups'] = xarModAPIFunc('roles','user','getallgroups');
    return $data;
}

?>
