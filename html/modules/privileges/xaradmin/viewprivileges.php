<?php

/**
 * viewPrivileges - view the current privileges
 * Takes no parameters
 */
function privileges_admin_viewprivileges()
{
    $show = xarVarCleanFromInput('show');

// Clear Session Vars
    xarSessionDelVar('privileges_statusmsg');

// Security Check
    if(!xarSecurityCheck('ViewPrivileges')) return;

// call the Privileges class
    $privs = new xarPrivileges();

    //Load Template
    if(isset($show)) {$data['show'] = $show;}
    else {$data['show'] = 'assigned';}

    include_once 'modules/privileges/xartreerenderer.php';
    $renderer = new xarTreeRenderer();

    $data['authid'] = xarSecGenAuthKey();
    $data['trees'] = $renderer->drawtrees($data['show']);
    $data['refreshlabel'] = xarML('Refresh');
    return $data;
}


?>