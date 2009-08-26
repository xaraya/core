<?php
/**
 * Modify configuration
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * modify configuration
 */
function authsystem_admin_modifyconfig()
{
    // Security Check
    if (!xarSecurityCheck('AdminAuthsystem')) return;
    if (!xarVarFetch('phase',        'str:1:100', $phase,       'modify', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('shorturls',    'checkbox',  $data['shorturls'],   xarModVars::get('authsystem',  'SupportShortURLs'),    XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('uselockout',   'checkbox',  $data['uselockout'],  xarModVars::get('authsystem', 'uselockout'),     XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('lockouttime',  'int:1:',    $data['lockouttime'], xarModVars::get('authsystem', 'lockouttime'),       XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('lockouttries', 'int:1:',    $data['lockouttries'], xarModVars::get('authsystem', 'lockouttries'),       XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;

    switch (strtolower($phase)) {
        case 'modify':
        default:
            break;

        case 'update':
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) return;
            xarModVars::set('authsystem', 'SupportShortURLs', $data['shorturls']);
            xarModVars::set('authsystem', 'uselockout', $data['uselockout']);
            xarModVars::set('authsystem', 'lockouttime', $data['lockouttime']);
            xarModVars::set('authsystem', 'lockouttries', $data['lockouttries']);
            break;
    }
    $data['authid'] = xarSecGenAuthKey();
    return $data;
}
?>