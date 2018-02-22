<?php
/**
 * Create a new password for the user
 *
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/42.html
 */

/**
 * Function to create a password for a user
 * 
 * @param void N/A
 * @return boolean Returns true on success, false upon failure. 
 * @throws BadParameterException Thrown if not all parameters have been given in the GET/POST data
 */
function authsystem_admin_createpassword()
{
    // Security
    if (!xarSecurityCheck('EditAuthsystem')) return;
    
    // Get parameters
    if(!xarVarFetch('state', 'isset', $state, NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('groupid', 'int:0:', $groupid, 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('id', 'isset', $id)) {
        throw new BadParameterException(array('parameters','admin','createpassword','roles'), xarML('Invalid #(1) for #(2) function #(3)() in module #(4)'));
    }

    $pass = xarMod::apiFunc('roles',
                          'user',
                          'makepass');
     if (empty($pass)) {
            throw new BadParameterException(null,xarML('Problem generating new password'));
     }
     $role = xarRoles::get($id);
     $modifiedstatus = $role->setPass($pass);
     $modifiedrole = $role->updateItem();
     if (!$modifiedrole) {
        return;
     }
     if (!xarModVars::get('roles', 'askpasswordemail')) {
        xarController::redirect(xarModURL('roles', 'admin', 'showusers',
                      array('id' => $data['groupid'], 'state' => $data['state'])));
        return true;
    }
    else {

        xarSession::setVar('tmppass',$pass);
        xarController::redirect(xarModURL('roles', 'admin', 'asknotification',
        array('id' => array($id => '1'), 'mailtype' => 'password', 'groupid' => $groupid, 'state' => $state)));
    }
}
?>
