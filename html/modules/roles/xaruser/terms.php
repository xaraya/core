<?php

/**
 * Shows the user terms if set as a modvar
 */
function roles_user_terms()
{

    // Security check
    if (!xarSecurityCheck('ViewRoles')) return;

    xarTplSetPageTitle(xarModGetVar('themes', 'SiteName').' :: '.
                       xarVarPrepForDisplay(xarML('Users'))
               .' :: '.xarVarPrepForDisplay(xarML('Terms of Usage')));

    return array();
}

?>