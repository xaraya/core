<?php
/**
 * Main admin function
 *
 * @package modules
 * @subpackage roles module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * 
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * the main administration function
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
