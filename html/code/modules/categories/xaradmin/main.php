<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/147.html
 *
 */
/**
 * The main administration function
 * 
 * This function redirects to the view categories function
 * @return bool Returns true on success, false on failure
 */
function categories_admin_main()
{
    // Security check
    if(!xarSecurityCheck('ViewCategories')) return;

    $refererinfo = xarController::$request->getInfo(xarServer::getVar('HTTP_REFERER'));
    $info = xarController::$request->getInfo();
    $samemodule = $info[0] == $refererinfo[0];
    
    if (((bool)xarModVars::get('modules', 'disableoverview') == false) || $samemodule){
        return array();
    } else {
        xarController::redirect(xarModURL('categories', 'admin', 'view'));
    }

    return true;
}

?>