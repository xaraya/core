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
    // Security Check
    if(xarSecurityCheck('EditRole',0)) {

        if (xarModGetVar('adminpanels', 'overview') == 0){
            return xarTplModule('roles','admin', 'main',array());
        } else {
            xarResponseRedirect(xarModURL('roles', 'admin', 'viewroles'));
        }
    }
    elseif(xarSecurityCheck('ViewRoles',0)) {

        if (xarUserIsLoggedIn()) {
           xarResponseRedirect(xarModURL('roles',
                                         'user',
                                         'account'));
        } else {
            xarResponseRedirect(xarModURL('roles',
                                          'user',
                                          'register'));
        }
    }
    else { return; }
}

?>