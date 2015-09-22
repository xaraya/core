<?php
/**
 * Log a user out from the system
 *
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/42.html
 */

/**
 * Log a user out of the system.
 * 
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * 
 * @param  void N/A
 * @return boolean Returns true if the user has been logged out successfullly. 
 * @throws ForbiddenOperationException Thrown if the user could not be logged out.
 */
function authsystem_user_logout()
{
    $redirect=xarServer::getBaseURL();

    // Get input parameters
    if (!xarVarFetch('redirecturl','str:1:254',$redirecturl,$redirect,XARVAR_NOT_REQUIRED)) return;
    
    $defaultauthdata = xarMod::apiFunc('roles','user','getdefaultauthdata');
    $defaultlogoutmodname = $defaultauthdata['defaultlogoutmodname'];
    $authmodule = $defaultauthdata['defaultauthmodname'];
    // Defaults
    //if (preg_match('/$authmodule}/',$redirecturl)) {
    if (strstr($redirecturl, $defaultlogoutmodname)) {
        $redirecturl = $redirect;
    }

    // Log user out
    if (!xarUserLogOut()) {
        throw new ForbiddenOperationException(array('authsystem', 'logout'),xarML('Problem Logging Out.  Module #(1) Function #(2)'));
    }
    xarController::redirect($redirecturl);
    return true;
}
?>
