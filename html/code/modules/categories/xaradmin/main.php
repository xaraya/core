<?php
/**
 * Categories module
 *
 * @package modules
 * @copyright (C) copyright-placeholder
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Categories Module
 * @link http://xaraya.com/index.php/release/147.html
 * @author Categories module development team
 */
/**
 * the main administration function
 * This function redirects to the view categories function
 * @return bool true on success
 */
function categories_admin_main()
{

    // Security check
    if(!xarSecurityCheck('ViewCategories')) return;

    $refererinfo = xarRequest::getInfo(xarServer::getVar('HTTP_REFERER'));
    $info = xarRequest::getInfo();
    $samemodule = $info[0] == $refererinfo[0];
    
    if (((bool)xarModVars::get('modules', 'disableoverview') == false) || $samemodule){
        return array();
    } else {
        xarResponse::Redirect(xarModURL('categories', 'admin', 'viewcats'));
    }

    return true;
}

?>