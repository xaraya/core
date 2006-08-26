<?php
/**
 * Utilities
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
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
    if (empty($data['option']) || $data['option'] == 'query') {
        xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'query'));
    } else {
        xarResponseRedirect(xarModURL('dynamicdata', 'util', $data['option']));
    }

    //return $data;
}
?>
