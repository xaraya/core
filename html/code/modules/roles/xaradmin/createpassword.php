<?php
/**
 * Create a new password for the user
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
 * createpassword - create a new password for the user
 */
function roles_admin_createpassword()
{
    // Security
    if (!xarSecurity::check('EditRoles')) return;
    
    // Get parameters
    if(!xarVar::fetch('state', 'isset', $state, NULL, xarVar::DONT_SET)) return;
    if (!xarVar::fetch('groupid', 'int:0:', $groupid, 0, xarVar::NOT_REQUIRED)) return;
    if (!xarVar::fetch('id', 'isset', $id)) {
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
        xarController::redirect(xarController::URL('roles', 'admin', 'showusers',
                      array('id' => $groupid, 'state' => $state)));
    } else {
        xarSession::setVar('tmppass',$pass);
        xarController::redirect(xarController::URL('roles', 'admin', 'asknotification',
        array('id' => array($id => '1'), 'mailtype' => 'password', 'groupid' => $groupid, 'state' => $state)));
    }
    return true;
}
?>
