<?php

/**
 * viewRoles - view the current groups
 * Takes no parameters
 */
function roles_admin_viewroles()
{
// Clear Session Vars
    xarSessionDelVar('roles_statusmsg');

// Security Check
    if(!xarSecurityCheck('ViewRoles')) return;

// Call the Roles class
// should be static, but apparently not doable in php?
    $roles = new xarRoles();

    include_once 'modules/roles/xartreerenderer.php';
    $renderer = new xarTreeRenderer();

// Load Template
    $data['authid'] = xarSecGenAuthKey();
    $data['tree'] = $renderer->drawtree($renderer->maketree());
    return $data;
}

?>