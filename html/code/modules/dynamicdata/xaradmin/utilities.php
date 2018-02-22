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
    if (!xarSecurityCheck('EditDynamicData')) return;
    
    extract($args);
    if(!xarVarFetch('q','str', $data['option'], 'query', XARVAR_NOT_REQUIRED)) {return;}
    xarTpl::setPageTitle(xarVarPrepForDisplay(xarML($data['option'])));
    xarController::redirect(xarModURL('dynamicdata', 'admin', 'import'));
    return true;
}
?>
