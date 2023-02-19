<?php
/**
 * Utilities
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 */
 /*
 * @author John Cox <niceguyeddie@xaraya.com>
 */
function dynamicdata_admin_utilities(Array $args=array())
{
    // Security
    if (!xarSecurity::check('EditDynamicData')) return;
    
    extract($args);
    if(!xarVar::fetch('q','str', $data['option'], 'query', xarVar::NOT_REQUIRED)) {return;}
    xarTpl::setPageTitle(xarVar::prepForDisplay(xarML($data['option'])));
    xarController::redirect(xarController::URL('dynamicdata', 'admin', 'import'));
    return true;
}