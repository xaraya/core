<?php
/**
 * Main entry point for the user interface of this module
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * The main user interface function of this module.
 * This function is the default function, and is called whenever the module is
 * initiated without defining arguments. The function checks if user is logged in and redirects the user to his/her account, or displays the showloginform page of the current authentication module.
 * @return boolean true after redirection
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
*/
function roles_user_main()
{

    // Get the default authentication data - this supplies default auth module and corrected login and logout module
    $defaultauthdata=xarMod::apiFunc('roles','user','getdefaultauthdata');

    $loginmodule=$defaultauthdata['defaultloginmodname'];
    $authmodule=$defaultauthdata['defaultauthmodname'];

    if (xarUserIsLoggedIn()) {
        xarController::redirect(xarModURL('roles', 'user', 'account'));
    } else {
        xarController::redirect(xarModURL($loginmodule, 'user', 'showloginform'));
    }
    return true;
}

?>