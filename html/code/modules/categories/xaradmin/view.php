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
 * View admin categories
 * 
 * @return array|null Returns display data array on succes, null on failure
 */
function categories_admin_view()
{
    // Get parameters
    if(!xarVarFetch('activetab',    'isset', $activetab,    0, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('startnum',     'isset', $data['startnum'],    1, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('items_per_page',   'isset', $data['items_per_page'],    xarModVars::get('categories', 'items_per_page'), XARVAR_NOT_REQUIRED)) {return;}

    // Security check
    if(!xarSecurityCheck('ManageCategories')) return;

    $data['options'][] = array('id' => $activetab);

    return $data;
}

?>
