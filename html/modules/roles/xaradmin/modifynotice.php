<?php
/**
 * File: $Id$
 *
 * Modify configuration
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Xaraya Team
 */
/**
 * modify configuration
 */
function roles_admin_modifynotice()
{
    // Security Check
    if (!xarSecurityCheck('AdminRole')) return;
    if (!xarVarFetch('phase', 'str:1:100', $phase, 'modify', XARVAR_NOT_REQUIRED)) return;
    $hooks = array();
    switch (strtolower($phase)) {
        case 'modify':
        default: 
            $data['ips'] = unserialize(xarModGetVar('roles', 'disallowedips'));
            $data['authid'] = xarSecGenAuthKey();
            $data['updatelabel'] = xarML('Update Notification Configuration');

            $hooks = xarModCallHooks('module', 'modifyconfig', 'roles',
                array('module' => 'roles'));
            $data['hooks'] = $hooks;
            
            break;

        case 'update':
            if (!xarVarFetch('askwelcomeemail', 'checkbox', $askwelcomeemail, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('askdeactivationemail', 'checkbox', $askdeactivationemail, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('askvalidationemail', 'checkbox', $askvalidationemail, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('askpendingemail', 'checkbox', $askpendingemail, false, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('askpasswordemail', 'checkbox', $askpasswordemail, false, XARVAR_NOT_REQUIRED)) return;
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) return; 
            // Update module variables
            xarModSetVar('roles', 'askwelcomeemail', $askwelcomeemail);
            xarModSetVar('roles', 'askdeactivationemail', $askdeactivationemail);
            xarModSetVar('roles', 'askvalidationemail', $askvalidationemail);
            xarModSetVar('roles', 'askpendingemail', $askpendingemail);
            xarModSetVar('roles', 'askpasswordemail', $askpasswordemail);
            
            xarModCallHooks('module', 'updateconfig', 'roles',
                array('module' => 'roles'));

            xarResponseRedirect(xarModURL('roles', 'admin', 'modifynotice')); 
            // Return
            return true;

            break;
    } 
    return $data;
} 
?>