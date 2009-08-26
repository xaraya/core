<?php
/**
 * Modify the configuration parameters
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
 * @link http://xaraya.com/index.php/release/1.html
 * @author Andy Varganov <andyv@xaraya.com>
 */
/**
 * standard function to modify the configuration parameters
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  the data for template
 * @throws  XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION'
 * @todo    remove at some stage if not used. Created for the move of mod overview var
 *          and never in a release, but this var is not used now due to help system.
*/
function modules_admin_modifyconfig()
{
    if(!xarSecurityCheck('AdminModules')) return;
    if (!xarVarFetch('phase',        'str:1:100', $phase,       'modify', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if(!xarVarFetch('disableoverview','int', $data['disableoverview'], xarModVars::get('modules', 'disableoverview'), XARVAR_NOT_REQUIRED)) return;

    switch (strtolower($phase)) {
        case 'modify':
        default:
        break;

        case 'update':
        if (!xarSecConfirmAuthKey()) return;
        xarModVars::set('modules', 'disableoverview', $data['disableoverview']);
        break;
    }
    $data['authid'] = xarSecGenAuthKey();
    return $data;
}
?>
