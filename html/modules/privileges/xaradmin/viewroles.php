<?php
/**
 * File: $Id:
 * 
 * Display the roles this privilege is assigned to
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
 * viewroles - display the roles this privilege is assigned to
 */
function privileges_admin_viewroles()
{
    $data = array();

    if (!xarVarFetch('pid',  'isset', $pid,          NULL,       XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('show', 'isset', $data['show'], 'assigned', XARVAR_NOT_REQUIRED)) {return;}

    // Clear Session Vars
    xarSessionDelVar('privileges_statusmsg');

    // Security Check
    if(!xarSecurityCheck('ViewRoles')) return;

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

    $data['pname'] = $priv->getName();
    $data['pid'] = $pid;
    $data['roles'] = $curroles;
    //    $data['allgroups'] = $roles->getAllPrivileges();
    $data['authid'] = xarSecGenAuthKey();
    $data['removeurl'] = xarModURL('privileges',
                             'admin',
                             'removerole',
                             array('pid'=>$pid));
    $data['trees'] = $renderer->drawtrees($data['show']);
    return $data;

    xarSessionSetVar('privileges_statusmsg', xarML('Privilege Modified',
                    'privileges'));

// redirect to the next page
    xarResponseRedirect(xarModURL('privileges', 'admin', 'viewroles'));
}

?>
