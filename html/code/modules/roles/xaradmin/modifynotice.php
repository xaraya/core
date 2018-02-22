<?php
/**
 * Modify configuration
 *
 * @package modules\roles
 * @subpackage roles
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/27.html
 */
/**
 * modify configuration
 * @return array data for the template display
 */
function roles_admin_modifynotice()
{
    // Security
    if (!xarSecurityCheck('AdminRoles')) return;
    
    if (!xarVarFetch('phase', 'str:1:100', $phase, 'modify', XARVAR_NOT_REQUIRED)) return;
    $hooks = array();
    switch (strtolower($phase)) {
        case 'modify':
        default:
            $ips = xarModVars::get('roles','disallowedips');
            $data['ips'] = empty($ips) ? '' : unserialize($ips);
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
            if (!xarSecConfirmAuthKey()) {
                return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
            }        
            // Update module variables
            xarModVars::set('roles', 'askwelcomeemail', $askwelcomeemail);
            xarModVars::set('roles', 'askdeactivationemail', $askdeactivationemail);
            xarModVars::set('roles', 'askvalidationemail', $askvalidationemail);
            xarModVars::set('roles', 'askpendingemail', $askpendingemail);
            xarModVars::set('roles', 'askpasswordemail', $askpasswordemail);

            xarModCallHooks('module', 'updateconfig', 'roles',
                array('module' => 'roles'));

            xarController::redirect(xarModURL('roles', 'admin', 'modifynotice'));
            // Return
            return true;

            break;
    }
    return $data;
}
?>
