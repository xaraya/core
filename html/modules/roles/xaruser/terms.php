<?php

/**
 * Shows the user terms if set as a modvar
 */
function roles_user_terms()
{

    // Security check
    if (!xarSecurityCheck('ViewRoles')) return;

    xarTplSetPageTitle(xarVarPrepForDisplay(xarML('Terms of Usage')));

    return array();
}

?>