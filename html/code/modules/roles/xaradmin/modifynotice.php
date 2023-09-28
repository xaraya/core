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
 * @return array<mixed>|string|bool|void data for the template display
 */
function roles_admin_modifynotice()
{
    // Security
    if (!xarSecurity::check('AdminRoles')) return;
    
    if (!xarVar::fetch('phase', 'str:1:100', $phase, 'modify', xarVar::NOT_REQUIRED)) return;
    $hooks = array();
    switch (strtolower($phase)) {
        case 'modify':
        default:
            $ips = xarModVars::get('roles','disallowedips');
            $data['ips'] = empty($ips) ? '' : unserialize($ips);
            $data['authid'] = xarSec::genAuthKey();
            $data['updatelabel'] = xarML('Update Notification Configuration');

            $hooks = xarModHooks::call('module', 'modifyconfig', 'roles',
                array('module' => 'roles'));
            $data['hooks'] = $hooks;

            break;

        case 'update':
            if (!xarVar::fetch('askwelcomeemail', 'checkbox', $askwelcomeemail, false, xarVar::NOT_REQUIRED)) return;
            if (!xarVar::fetch('askdeactivationemail', 'checkbox', $askdeactivationemail, false, xarVar::NOT_REQUIRED)) return;
            if (!xarVar::fetch('askvalidationemail', 'checkbox', $askvalidationemail, false, xarVar::NOT_REQUIRED)) return;
            if (!xarVar::fetch('askpendingemail', 'checkbox', $askpendingemail, false, xarVar::NOT_REQUIRED)) return;
            if (!xarVar::fetch('askpasswordemail', 'checkbox', $askpasswordemail, false, xarVar::NOT_REQUIRED)) return;
            // Confirm authorisation code
            if (!xarSec::confirmAuthKey()) {
                return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
            }        
            // Update module variables
            xarModVars::set('roles', 'askwelcomeemail', $askwelcomeemail);
            xarModVars::set('roles', 'askdeactivationemail', $askdeactivationemail);
            xarModVars::set('roles', 'askvalidationemail', $askvalidationemail);
            xarModVars::set('roles', 'askpendingemail', $askpendingemail);
            xarModVars::set('roles', 'askpasswordemail', $askpasswordemail);

            xarModHooks::call('module', 'updateconfig', 'roles',
                array('module' => 'roles'));

            xarController::redirect(xarController::URL('roles', 'admin', 'modifynotice'));
            // Return
            return true;
    }
    return $data;
}
