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
 * View admin categories
 * 
 * @return array|null Returns display data array on succes, null on failure
 */
function categories_admin_view()
{
    // Get parameters
    if(!xarVar::fetch('activetab',      'isset', $activetab             , 0, xarVar::NOT_REQUIRED)) {return;}
    if(!xarVar::fetch('startnum',       'isset', $data['startnum']      , 1, xarVar::NOT_REQUIRED)) {return;}
    if(!xarVar::fetch('items_per_page', 'isset', $data['items_per_page'], xarModVars::get('categories', 'items_per_page'), xarVar::NOT_REQUIRED)) {return;}

    // Set a fallback value in case the modvar is empty
    if (empty($data['items_per_page'])) $data['items_per_page'] = 20;
    
    // Security check
    if(!xarSecurity::check('ManageCategories')) return;

    $data['options'][] = array('id' => $activetab);

    return $data;
}

?>