<?php

/**
 * the main user function
 * This function is the default function, and is called whenever the module is
 * initiated without defining arguments.  Function decides if user is logged in
 * and returns user to correct location.
 *
*/
function roles_user_main()
{

// Security Check
    if(!xarSecurityCheck('ViewRoles')) return;

    if (xarUserIsLoggedIn()) {
       xarResponseRedirect(xarModURL('roles',
                                     'user',
                                     'account'));
    } else {
        xarResponseRedirect(xarModURL('roles',
                                      'user',
                                      'register'));
    }

    return true;
}

?>