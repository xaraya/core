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
    if (!xarVarFetch('phase',        'str:1:100', $phase,       'modify', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('tab', 'str:1:100', $data['tab'], 'general', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemsperpage', 'int', $data['itemsperpage'], xarModVars::get('blocks', 'itemsperpage'), XARVAR_NOT_REQUIRED)) return;

    switch (strtolower($phase)) {
        case 'modify':
        default:
        break;

        case 'update':
        if (!xarSecConfirmAuthKey()) return;
        xarModVars::set('blocks', 'itemsperpage',$data['itemsperpage']);
        break;
    }
    $data['authid'] = xarSecGenAuthKey();
    return $data;
}
?>