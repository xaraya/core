<?php
/**
 * Main entry point for the admin interface of this module
 *
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/42.html
 */

/**
 * Main entry point for the admin interface of this module
 *
 * This function is the default function for the admin interface, and is called whenever the module is
 * initiated with only an admin type but no func parameter passed.  
 * The function displays the module's overview page, or redirects to another page if overviews are disabled.
 *
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
 * 
 * @param void N/A
 * @return string|boolean If the page redirects true is returned, else a display string.
 */
function authsystem_admin_main()
{
    // Security
    if (!xarSecurityCheck('EditAuthsystem')) return;
   
    $refererinfo = xarController::$request->getInfo(xarServer::getVar('HTTP_REFERER'));
    $info = xarController::$request->getInfo();
    $samemodule = $info[0] == $refererinfo[0];
    
    if (((bool)xarModVars::get('modules', 'disableoverview') == false) || $samemodule){
        return xarTpl::module('authsystem','admin','overview');
    } else {
        xarController::redirect(xarModURL('authsystem', 'admin', 'modifyconfig'));
        return true;
    }
}
?>
