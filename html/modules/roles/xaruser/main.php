<?php
/**
 * Default user function
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * the main user function
 * This function is the default function, and is called whenever the module is
 * initiated without defining arguments.  Function decides if user is logged in
 * and returns user to correct location.
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
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
            xarResponseRedirect(xarModURL(xarModGetNameFromID(xarModGetVar('roles','defaultauthmodule')),
                                          'user',
                                          'showloginform'));
        } else {
            xarResponseRedirect(xarModURL(xarModGetNameFromID(xarModGetVar('roles','defaultauthmodule')),
                                          'user',
                                          'register'));

        }
   /*
    }
    else { return; }
    */
}

?>
