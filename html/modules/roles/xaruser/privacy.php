<?php

/**
 * Shows the privacy policy if set as a modvar
 */
function roles_user_privacy()
{

    // Security check
    if (!xarSecurityCheck('ViewRoles')) return;

    xarTplSetPageTitle(xarModGetVar('themes', 'SiteName').' :: '.
                       xarVarPrepForDisplay(xarML('Users'))
               .' :: '.xarVarPrepForDisplay(xarML('Privacy Statement')));

    return array();
}

?>