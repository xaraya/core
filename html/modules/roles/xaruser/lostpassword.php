<?php
/**
 * Sends a new password to the user if they have forgotten theirs.
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * Sends a new password to the user if they have forgotten theirs.
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_user_lostpassword()
{
    // Security check
    if (!xarSecurityCheck('ViewRoles')) return;

    //If a user is already logged in, no reason to see this.
    //We are going to send them to their account.
    if (xarUserIsLoggedIn()) {
        xarResponseRedirect(xarModURL('roles',
                                      'user',
                                      'account'));
       return true;
    }

    xarTplSetPageTitle(xarVarPrepForDisplay(xarML('Lost Password')));

    if (!xarVarFetch('phase','str:1:100',$phase,'request',XARVAR_NOT_REQUIRED)) return;

    switch(strtolower($phase)) {

        case 'request':
        default:
            $authid = xarSecGenAuthKey();
            $data = xarTplModule('roles','user', 'requestpw', array('authid'    => $authid,
                                                                    'emaillabel' => xarML('E-Mail New Password')));

            break;

        case 'send':

            if (!xarVarFetch('uname','str:1:100',$uname,'',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('email','str:1:100',$email,'',XARVAR_NOT_REQUIRED)) return;

            // Confirm authorisation code.
            if (!xarSecConfirmAuthKey()) return;

            if ((empty($uname)) && (empty($email))) {
                throw new EmptyParameterException('username or email');
            }

            // check for user and grab uid if exists
            $user = xarModAPIFunc('roles',
                                  'user',
                                  'get',
                                   array('uname' => $uname,
                                         'email' => $email));

            if (empty($user)) {
                throw new BadParameterException(array($uname,$email),'The email addres "#(1)" or the username "#(2)" was not found.');
            }
            // Make new password
            $user['pass'] = xarModAPIFunc('roles','user','makepass');

            if (empty($user['pass'])) {
                throw new BadParameterException(null,'Problem generating new password, it was empty');
            }

            // We need to tell some hooks that we are coming from the lost password screen
            // and not the update the actual roles screen.  Right now, the keywords vanish
            // into thin air.  Bug 1960 and 3161
            xarVarSetCached('Hooks.all','noupdate',1);

            //Update user password
            // check for user and grab uid if exists
            if (!xarModAPIFunc('roles','admin','update',$user)) {
                throw new BadParameterException(null,'Problems updating the user information');
            }
              // Send Reminder Email
            if (!xarModAPIFunc('roles', 'admin','senduseremail', array('uid' => array($user['uid'] => '1'), 'mailtype' => 'reminder', 'pass' => $user['pass']))) return;

            // Let user know that they have an email on the way.   
            $data = xarTplModule('roles','user','requestpwconfirm');
          break;
    }
    return $data;
}
?>
