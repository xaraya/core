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
    //This is limiting all admin users the chance to get to the menu for the roles.
    /*
    if(xarSecurityCheck('EditRole',0)) {

        if (xarModGetVar('adminpanels', 'overview') == 0){
            return xarTplModule('roles','admin', 'main',array());
        } else {
            xarResponseRedirect(xarModURL('roles', 'admin', 'viewroles'));
        }
    }
    elseif(xarSecurityCheck('ViewRoles',0)) {
    */    
    $allowregistration = xarModGetVar('roles', 'allowregistration');

        if (xarUserIsLoggedIn()) {
           xarResponseRedirect(xarModURL('roles',
                                         'user',
                                         'account'));
        } elseif ($allowregistration != true) {
            xarResponseRedirect(xarModURL('roles',
                                          'user',
                                          'showloginform'));
        } else {
            xarResponseRedirect(xarModURL('roles',
                                          'user',
                                          'register'));

        }
   /*
    }
    else { return; }
    */
}

?>