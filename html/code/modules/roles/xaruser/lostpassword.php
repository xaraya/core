<?php
/**
 * Sends a new password to the user if they have forgotten theirs.
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * Sends a new password to the user if they have forgotten theirs.
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @return string output display string
 */
function roles_user_lostpassword()
{
    // Security check
    if (!xarSecurityCheck('ViewRoles')) return;

    //If a user is already logged in, no reason to see this.
    //We are going to send them to their account.
    if (xarUserIsLoggedIn()) {
        xarController::redirect(xarModURL('roles', 'user', 'account'));
        return true;
    }

    xarTpl::setPageTitle(xarVarPrepForDisplay(xarML('Lost Password')));

    if (!xarVarFetch('phase','str:1:100',$phase,'request',XARVAR_NOT_REQUIRED)) return;

    switch(strtolower($phase)) {

        case 'request':
        default:
            $data = array('showmessage' => 0);
            break;

        case 'send':

            if (!xarVarFetch('uname','str:1:100',$uname,'',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('email','str:1:100',$email,'',XARVAR_NOT_REQUIRED)) return;

            // Confirm authorisation code.
            if (!xarSecConfirmAuthKey()) {
                return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
            }        

            $data['showmessage'] = 0;    
            if ((empty($uname)) && (empty($email))) {
                $data['showmessage'] = 1;
                break;
            }

            // check for user and grab id if exists
            $user = xarMod::apiFunc('roles','user','get',
                                   array('uname' => $uname,
                                         'email' => $email));

            if (empty($user)) {
                $data['showmessage'] = 2;
                break;
            }
            // Make new password
            $user['pass'] = xarMod::apiFunc('roles','user','makepass');

            if (empty($user['pass'])) {
                throw new DataNotFoundException(array(),'Problem generating new password');
            }

            // We need to tell some hooks that we are coming from the lost password screen
            // and not the update the actual roles screen.  Right now, the keywords vanish
            // into thin air.  Bug 1960 and 3161
            xarVarSetCached('Hooks.all','noupdate',1);

            //Update user password
            $role = xarRoles::get($user['id']);
            $modifiedstatus = $role->setPass($user['pass']);
            if (!$role->updateItem()) return;

              // Send Reminder Email
            if (!xarMod::apiFunc('roles', 'admin','senduseremail', array('id' => array($user['id'] => '1'), 'mailtype' => 'reminder', 'pass' => $user['pass']))) return;

            // Let user know that they have an email on the way.
            $data = xarTpl::module('roles','user','requestpwconfirm', $data);
          break;
    }
    return $data;
}
?>
