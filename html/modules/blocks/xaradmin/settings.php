<?php

/**
 * List modules and current settings
 * @param several params from the associated form in template
 *
 */
function blocks_admin_settings()
{
    // Security Check
    if(!xarSecurityCheck('EditBlock')) return;

    if (!xarVarFetch('selstyle', 'str:1:', $selstyle, 'plain', XARVAR_NOT_REQUIRED)) return; 
    
    xarModSetVar('blocks', 'selstyle', $selstyle);
    
    xarResponseRedirect(xarModURL('blocks', 'admin', 'view_instances'));

    return true;
}

?>