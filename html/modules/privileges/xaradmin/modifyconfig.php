<?php
/**
 * Modify configuration
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Privileges Module
 * @author Xaraya Team
 */
/**
 * modify configuration
 */
function privileges_admin_modifyconfig()
{
    // Security Check
    if (!xarSecurityCheck('AdminPrivilege')) return;
    if (!xarVarFetch('phase', 'str:1:100', $phase, 'modify', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('tab', 'str:1:100', $data['tab'], 'general', XARVAR_NOT_REQUIRED)) return;
    switch (strtolower($phase)) {
        case 'modify':
        default:
            $data['inheritdeny'] = xarModGetVar('privileges', 'inheritdeny');
            $data['authid'] = xarSecGenAuthKey();
            break;

        case 'update':
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) return;
            switch ($data['tab']) {
                case 'general':
                    if (!xarVarFetch('inheritdeny', 'bool', $data['inheritdeny'], false, XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('privileges', 'inheritdeny', $data['inheritdeny']);
                    if (!xarVarFetch('lastresort', 'bool', $data['lastresort'], false, XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('privileges', 'lastresort', $data['lastresort']);
                    if (!$data['lastresort']) xarModDelVar('privileges', 'lastresort',$data['lastresort']);
                    break;
                case 'realms':
                    if (!xarVarFetch('enablerealms', 'bool', $data['enablerealms'], false, XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('privileges', 'showrealms', $data['enablerealms']);
                    break;
                case 'lastresort':
                    if (!xarVarFetch('name', 'str', $name, '', XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('password', 'str', $password, '', XARVAR_NOT_REQUIRED)) return;
                    $secret = array(
                                'name' => MD5($name),
                                'password' => MD5($password)
                                );
                    xarModSetVar('privileges','lastresort',serialize($secret));
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