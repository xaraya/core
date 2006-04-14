<?php
/**
 * Log user out of system
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * log user out of system
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 */
function authsystem_user_logout()
{
    $redirect=xarServerGetBaseURL();

    // Get input parameters
    if (!xarVarFetch('redirecturl','str:1:254',$redirecturl,$redirect,XARVAR_NOT_REQUIRED)) return;
   
    $defaultauthmodule=(int)xarModGetVar('roles','defaultauthmodule');
    $authmodule=xarModGetNameFromID($defaultauthmodule);
    if (!file_exists('modules/'.$authmodule.'/xaruser/logout.php')) {
            $authmodule='authsystem'; // incase the authmodule doesn't provide a login
    }
    // Defaults
    //if (preg_match('/$authmodule}/',$redirecturl)) {
    if (strstr($redirecturl,$authmodule)) {
        $redirecturl = $redirect;
    }

    // Log user out
    if (!xarUserLogOut()) {
        $msg = xarML('Problem Logging Out.  Module #(1) Function #(2)', 'authsystem', 'logout');
        xarErrorSet(XAR_USER_EXCEPTION, 'LOGIN_ERROR', new DefaultUserException($msg));
        return;
    }
    xarResponseRedirect($redirecturl);
    return true;
}
?>