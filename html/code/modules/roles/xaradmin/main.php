<?php
/**
 * Main entry point for the admin interface of this module
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * The main admin interface function of this module.
 * This function is the default function for the admin interface, and is called whenever the module is
 * initiated with only an admin type but no func parameter passed.  
 * The function displays the module's overview page, or redirects to the showusers page if overviews are disabled.
 * @return mixed output display string or boolean true if redirected
 */
function roles_admin_main()
{
    if (!xarSecurityCheck('EditRoles')) return;

    $refererinfo = xarController::$request->getInfo(xarServer::getVar('HTTP_REFERER'));
    $info = xarController::$request->getInfo();
    $samemodule = $info[0] == $refererinfo[0];
    
    if (((bool)xarModVars::get('modules', 'disableoverview') == false) || $samemodule){
        return xarTplModule('roles','admin','overview');
    } else {
        xarController::redirect(xarModURL('roles', 'admin', 'showusers'));
        return true;
    }
}
?>