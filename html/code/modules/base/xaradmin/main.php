<?php
/**
 * Main entry point for the admin interface of this module
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 *
 * @author Marcel van der Boom
 */

/**
 * Main entry point for the admin interface of this module
 *
 * This function is the default function for the admin interface, and is called whenever the module is
 * initiated with only an admin type but no func parameter passed.  
 * The function displays the module's overview page, or redirects to another page if overviews are disabled.
 *
 * @author John Robeson
 * @author Greg Allan
 * 
 * @param void N/A
 * @return mixed Output display string or boolean true if redirected
 */
function base_admin_main()
{
    // Security
    if(!xarSecurityCheck('ViewBase')) return;

    $request = new xarRequest();
    $refererinfo = xarController::$request->getInfo(xarServer::getVar('HTTP_REFERER'));
    $module = xarController::$request->getModule();
    $samemodule = $module == $refererinfo[0];
    
    if (((bool)xarModVars::get('modules', 'disableoverview') == false) || $samemodule){
        return xarTpl::module('base','admin','overview');
    } else {
        xarController::redirect(xarModURL('base', 'admin', 'modifyconfig'));
        return true;
    }
}

?>
