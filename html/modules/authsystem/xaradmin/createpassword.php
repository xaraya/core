<?php
/**
 * Create a new password for the user
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
 * createpassword - create a new password for the user
 */
function authsystem_admin_createpassword()
{
    // Security Check
    if (!xarSecurityCheck('EditAuthsystem')) return;

    // Get parameters
    if (!xarVarFetch('state',    'isset',  $state,    NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('groupuid', 'int:0:', $groupuid, 0,    XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('uid',      'isset',  $uid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)','parameters', 'admin', 'createpassword', 'roles');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',new SystemException($msg." -- ".$uid));
        return;
    }

    $pass = xarModAPIFunc('roles', 'user', 'makepass');
    if (empty($pass)) {
            $msg = xarML('Problem generating new password');
            xarErrorSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            return;
     }
     $roles          = new xarRoles();
     $role           = $roles->getRole($uid);
     $modifiedstatus = $role->setPass($pass);
     $modifiedrole   = $role->update();
     if (!$modifiedrole) {
        return;
     }
     if (!xarModGetVar('roles', 'askpasswordemail')) {
        xarResponseRedirect(xarModURL('roles', 'admin', 'showusers',
                      array('uid' => $data['groupuid'], 'state' => $data['state'])));
        return true;
    }
    else {

        xarSessionSetVar('tmppass',$pass);
        xarResponseRedirect(xarModURL('roles', 'admin', 'asknotification',
        array('uid' => array($uid => '1'), 'mailtype' => 'password', 'groupuid' => $groupuid, 'state' => $state)));
    }
}
?>