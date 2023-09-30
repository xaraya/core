<?php
/**
 * Categories Module
 *
 * @package modules\categories
 * @subpackage categories
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/147.html
 *
 */
/**
 * The main administration function
 * 
 * This function redirects to the view categories function
 * @return bool|array<mixed>|void Returns true on success, false on failure
 */
function categories_admin_main()
{
    // Security check
    if(!xarSecurity::check('EditCategories')) return;

    $samemodule = xarController::isRefererSameModule();
    
    if (((bool)xarModVars::get('modules', 'disableoverview') == false) || $samemodule){
        return array();
    } else {
        xarController::redirect(xarController::URL('categories', 'admin', 'view'));
    }

    return true;
}
