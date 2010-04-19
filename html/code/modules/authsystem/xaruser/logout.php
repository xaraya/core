<?php
/**
 * Log user out of system
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * log user out of system
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @return bool true on success of redirect
 */
function authsystem_user_logout()
{
    $redirect=xarServer::getBaseURL();

    // Get input parameters
    if (!xarVarFetch('redirecturl','str:1:254',$redirecturl,$redirect,XARVAR_NOT_REQUIRED)) return;
    
    $defaultauthdata=xarMod::apiFunc('roles','user','getdefaultauthdata');
    $defaultlogoutmodname=$defaultauthdata['defaultlogoutmodname'];
    $authmodule=$defaultauthdata['defaultauthmodname'];
    // Defaults
    //if (preg_match('/$authmodule}/',$redirecturl)) {
    if (strstr($redirecturl, $defaultlogoutmodname)) {
        $redirecturl = $redirect;
    }

    // Log user out
    if (!xarUserLogOut()) {
        throw new ForbiddenOperationException(array('authsystem', 'logout'),xarML('Problem Logging Out.  Module #(1) Function #(2)'));
    }
    xarResponse::redirect($redirecturl);
    return true;
}
?>