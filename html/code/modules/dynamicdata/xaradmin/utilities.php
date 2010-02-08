<?php
/**
 * Utilities
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 */
 /*
 * @author John Cox <niceguyeddie@xaraya.com>
 */
function dynamicdata_admin_utilities($args)
{
    // Security check
    if (!xarSecurityCheck('EditDynamicData')) return;
    extract($args);
    if(!xarVarFetch('q','str', $data['option'], 'query', XARVAR_NOT_REQUIRED)) {return;}
    xarTplSetPageTitle(xarVarPrepForDisplay(xarML($data['option'])));
    xarController::redirect(xarModURL('dynamicdata', 'util', 'import'));
    return true;
}
?>
