<?php
/**
 * Main administrative function
 *
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * the main administration function
 *
 */
function dynamicdata_admin_main()
{
    if(!xarSecurityCheck('EditDynamicData')) return;

    $refererinfo = xarController::$request->getInfo(xarServer::getVar('HTTP_REFERER'));
    $info = xarController::$request->getInfo();
    $samemodule = $info[0] == $refererinfo[0];
    
    if (((bool)xarModVars::get('modules', 'disableoverview') == false) || $samemodule){
        return xarTplModule('dynamicdata','admin','overview');
    } else {
        xarController::redirect(xarModURL('dynamicdata', 'admin', 'view'));
        return true;
    }
}

?>
