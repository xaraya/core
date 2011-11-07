<?php
/**
 * Categories Module
 *
 * @package modules
 * @subpackage categories module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 */

/**
 * create item from xarMod::guiFunc('categories','admin','viewcat')
 */
function categories_admin_viewcats()
{
    // Get parameters
    if(!xarVarFetch('activetab',    'isset', $activetab,    0, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('startnum',     'isset', $data['startnum'],    1, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('items_per_page',   'isset', $data['items_per_page'],    xarModVars::get('categories', 'items_per_page'), XARVAR_NOT_REQUIRED)) {return;}

    // Security check
    if(!xarSecurityCheck('ManageCategories')) return;

    $data['options'][] = array('cid' => $activetab);

    if (!isset($useJSdisplay)) {
        $useJSdisplay = $data['useJSdisplay'] = xarModVars::get('categories','useJSdisplay');
    } else {
        $data['useJSdisplay'] = $useJSdisplay;
    }
    return xarTplModule('categories','admin','viewcats-render',$data);
}

?>
