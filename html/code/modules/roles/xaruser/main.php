<?php
/**
 * Default user function
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * the main user function
 * This function is the default function, and is called whenever the module is
 * initiated without defining arguments.  Function decides if user is logged in
 * and returns user to correct location.
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
*/
function roles_user_main()
{

// Security Check
    // Security Check
    //This is limiting all admin users the chance to get to the menu for the roles.
    /*
    if(xarSecurityCheck('EditRoles',0)) {

        if ((bool)xarModVars::get('modules', 'disableoverview') == false){
            return xarTplModule('roles','admin', 'main',array());
        } else {
            xarController::redirect(xarModURL('roles', 'admin', 'viewroles'));
        }
    }
    elseif(xarSecurityCheck('ViewRoles',0)) {
    */

    // Get the default authentication data - this supplies default auth module and corrected login and logout module
    $defaultauthdata=xarMod::apiFunc('roles','user','getdefaultauthdata');

    $loginmodule=$defaultauthdata['defaultloginmodname'];
    $authmodule=$defaultauthdata['defaultauthmodname'];

    if (xarUserIsLoggedIn()) {
        xarController::redirect(xarModURL('roles', 'user', 'account'));
    } else {
        xarController::redirect(xarModURL($loginmodule, 'user', 'showloginform'));
    }

   /*
    }
    else { return; }
    */
}

?>
