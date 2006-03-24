<?php
/**
 * Modify blocks configuration
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 * @author John Robeson
 * @author Greg Allan
 */
/**
 * Modify blocks configuration
 *
 * @return array of template values
 */
function blocks_admin_modifyconfig()
{
    // Security Check
    if(!xarSecurityCheck('AdminBlock')) return;
    if (!xarVarFetch('update', 'isset', $update, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('tab', 'str:1:100', $data['tab'], 'general', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemsperpage', 'int', $data['itemsperpage'], xarModGetVar('blocks', 'itemsperpage'), XARVAR_NOT_REQUIRED)) return;

    if($update) {
        if (!xarSecConfirmAuthKey()) return;
        xarModSetVar('blocks', 'itemsperpage',$data['itemsperpage']);
    }
    $data['authid'] = xarSecGenAuthKey();
    return $data;
}
?>