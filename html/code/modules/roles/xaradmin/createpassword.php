<?php
/**
 * Create a new password for the user
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * createpassword - create a new password for the user
 */
function roles_admin_createpassword()
{
    // Security Check
    if (!xarSecurityCheck('EditRoles')) return;
    // Get parameters
    if(!xarVarFetch('state', 'isset', $state, NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('groupid', 'int:0:', $groupid, 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('id', 'isset', $id)) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array('parameters', 'admin', 'createpassword', 'Roles');
        throw new BadParameterException($vars,$msg);
    }

    $pass = xarMod::apiFunc('roles','user','makepass');
    if (empty($pass)) throw new DataNotFoundException(array(),'Problem generating new password');
    $role = xarRoles::get($id);
    $modifiedstatus = $role->setPass($pass);
    if (!$role->updateItem()) return;

    if (!xarModVars::get('roles', 'askpasswordemail')) {
        xarResponse::redirect(xarModURL('roles', 'admin', 'showusers',
                      array('id' => $groupid, 'state' => $state)));
    } else {
        xarSession::setVar('tmppass',$pass);
        xarResponse::redirect(xarModURL('roles', 'admin', 'asknotification',
        array('id' => array($id => '1'), 'mailtype' => 'password', 'groupid' => $groupid, 'state' => $state)));
    }
    return true;
}
?>