<?php

/**
 * Shows the user login form when login block is not active
 */
function roles_user_showloginform()
{

    // Security check
    if (!xarSecurityCheck('ViewRoles')) return;
    $data['loginlabel'] = xarML('Log In');
    return $data;
}

?>