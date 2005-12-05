<?php
/**
 * Modify configuration of this module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * modify configuration
 */
function privileges_admin_modifyconfig()
{
    // Security Check
    if (!xarSecurityCheck('AdminPrivilege')) return;
    if (!xarVarFetch('phase', 'str:1:100', $phase, 'modify', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('tab', 'str:1:100', $data['tab'], 'realms', XARVAR_NOT_REQUIRED)) return;
    switch (strtolower($phase)) {
        case 'modify':
        default:
            $data['showrealms'] = xarModGetVar('privileges', 'showrealms');
            $data['authid'] = xarSecGenAuthKey();
            $data['updatelabel'] = xarML('Update Privileges Configuration');
            break;

        case 'update':
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) return;
            switch ($data['tab']) {
                case 'realms':
                    if (!xarVarFetch('enablerealms', 'str:1:100', $data['enablerealms'], 0, XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('privileges', 'showrealms', $data['enablerealms']);
                    break;
            }

            xarResponseRedirect(xarModURL('privileges', 'admin', 'modifyconfig',array('tab' => $data['tab'])));
            // Return
            return true;
            break;
    }
    return $data;
}
?>
