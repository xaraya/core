<?php

/**
 * This is a standard function to modify the configuration parameters of the
 * module
 */
function themes_admin_modifyconfig()
{ 
    // Security Check
    if (!xarSecurityCheck('AdminTheme')) return; 
    // Generate a one-time authorisation code for this operation
    $data['authid'] = xarSecGenAuthKey(); 
    // everything else happens in Template for now
    // prepare labels and values for display by the template
    $data['title'] = xarVarPrepForDisplay(xarML('Configure Themes'));
    $data['configoverview'] = xarVarPrepForDisplay(xarML('Configure Overview'));

    $filter['Class'] = 2;
    $data['themes'] = xarModAPIFunc('themes',
        'admin',
        'getlist', $filter);
    $data['defaulttheme'] = xarVarPrepForDisplay(xarModGetVar('themes', 'default'));
    $data['defaultthemelabel'] = xarVarPrepForDisplay(xarML('Default Theme:'));
    $data['showhelplabel'] = xarVarPrepForDisplay(xarML('Show module "Help" in the menu:'));
    $data['showhelp'] = xarModGetVar('adminpanels', 'showhelp') ? 'checked' : '' ;
    $data['submitbutton'] = xarVarPrepForDisplay(xarML('Submit')); 

    // everything else happens in Template for now
    return $data;
} 

?>