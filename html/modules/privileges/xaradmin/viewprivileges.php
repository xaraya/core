<?php

/**
 * viewPrivileges - view the current privileges
 * Takes no parameters
 */
function privileges_admin_viewprivileges()
{
    $data = array();
    
    if (!xarVarFetch('show', 'str', $data['show'], 'assigned', XARVAR_NOT_REQUIRED)) return;

    // Clear Session Vars
    xarSessionDelVar('privileges_statusmsg');

    // Security Check
    if(!xarSecurityCheck('ViewPrivileges')) return;

    // call the Privileges class
    $privs = new xarPrivileges();

    //Load Template
    include_once 'modules/privileges/xartreerenderer.php';
    $renderer = new xarTreeRenderer();

    $data['authid'] = xarSecGenAuthKey();
    $data['trees'] = $renderer->drawtrees($data['show']);
    $data['refreshlabel'] = xarML('Refresh');
    return $data;
}


?>
