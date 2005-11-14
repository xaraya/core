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
    if (!xarVarFetch('tab', 'str:1:100', $data['tab'], 'general', XARVAR_NOT_REQUIRED)) return;
	if (!xarVarFetch('tester', 'int', $data['tester'], xarModGetVar('privileges', 'tester'), XARVAR_NOT_REQUIRED)) return;
    switch (strtolower($phase)) {
        case 'modify':
        default:
            $data['authid'] = xarSecGenAuthKey();
            break;

        case 'update':
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) return;
            switch ($data['tab']) {
                case 'general':
                    if (!xarVarFetch('inheritdeny', 'checkbox', $inheritdeny, false, XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('privileges', 'inheritdeny', $inheritdeny);
                    if (!xarVarFetch('lastresort', 'checkbox', $lastresort, false, XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('privileges', 'lastresort', $lastresort);
                    if (!$lastresort) xarModDelVar('privileges', 'lastresort',$lastresort);
                    if (!xarVarFetch('exceptionredirect', 'checkbox', $data['exceptionredirect'], false, XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('privileges', 'exceptionredirect', $data['exceptionredirect']);
                    break;
                case 'realms':
                    if (!xarVarFetch('enablerealms', 'bool', $data['enablerealms'], false, XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('privileges', 'showrealms', $data['enablerealms']);
                    if (!xarVarFetch('realmvalue', 'str', $realmvalue, 'none', XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('privileges', 'realmvalue', $realmvalue);
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
                case 'testing':
                    xarModSetVar('privileges', 'tester', $data['tester']);
                    if (!xarVarFetch('test', 'checkbox', $test, false, XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('privileges', 'test', $test);
                    if (!xarVarFetch('testdeny', 'checkbox', $testdeny, false, XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('privileges', 'testdeny', $testdeny);
                    if (!xarVarFetch('testmask', 'checkbox', $testmask, false, XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('privileges', 'testmask', $testmask);
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
