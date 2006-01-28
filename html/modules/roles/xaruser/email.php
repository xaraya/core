<?php
/**
 * Send email to a user
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * Send email to a user
 *
 * @author  John Cox
 * @access  public
 * @param   uid is the uid of the user being sent
 * @return  true on success or void on falure
 * @throws  XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION'
 * @todo    handle empty subject and/or message?
 */
function roles_user_email($args)
{
    // we can only send emails to other members if we are logged in
    if(!xarUserIsLoggedIn())
    {
        xarErrorSet(XAR_USER_EXCEPTION, 'NOT_LOGGED_IN', new DefaultUserException());
        return;
    }

    extract($args);

    if (!xarVarFetch('uid', 'id', $uid)) return;
    if (!xarVarFetch('phase', 'enum:modify:confirm', $phase, 'modify', XARVAR_NOT_REQUIRED)) return;

    // If this validation fails, then do NOT send an e-mail, but
    // re-present the form to the user with an error message. Don't redirect,
    // just ensure the state is pulled back the start ('modify').
    $valid_flag = true;
    $error_message = '';
    $valid_flag &= xarVarFetch('subject', 'html:restricted', $subject);
    $valid_flag &= xarVarFetch('message', 'html:restricted', $message);

    if (!$valid_flag) {
        // The input failed validation.

        // Ensure we don't sent the e-mail.
        $phase = 'modify';

        // Catch the error message.
        $error_message = xarErrorRender('text');

        // Clear the errors since we are handling it locally.
        xarErrorHandled();
    }

    // Security Check
    if (!xarSecurityCheck('ReadRole')) return;

    switch(strtolower($phase)) {
        case 'modify':
        default:
            // Get user information
            $data = xarModAPIFunc(
                'roles', 'user', 'get',
                array('uid' => $uid)
            );

            if ($data == false) return;

            $data['subject'] = $subject;
            $data['message'] = $message;
            $data['error_message'] = $error_message;

            $data['authid'] = xarSecGenAuthKey();

            xarTplSetPageTitle(xarML('Mail User'));
            break;

        case 'confirm':
            // Bug 3342: don't allow arbitrary sender and recipient name details to be passed in.
            //if (!xarVarFetch('fname','str:1:100',$fname)) return;
            //if (!xarVarFetch('femail','str:1:100',$femail)) return;
            //if (!xarVarFetch('name', 'str:1:100', $name)) return;

            // Confirm authorisation code.
            if (!xarSecConfirmAuthKey()) return;

            // Security Check
            if (!xarSecurityCheck('ReadRole')) return;

            // If the sender details have not been passed in to $args, then
            // fetch them from the current user now.
            if (!isset($fname) || !iseet($femail)) {
                // Get details of the sender.
                $fname = xarUserGetVar('name');
                $femail = xarUserGetVar('email');
            }

            list($message) = xarModCallHooks('item', 'transform', $uid, array($message));

            // Get user information
            $data = xarModAPIFunc('roles', 'user', 'get', array('uid' => $uid));

            if ($data == false) return;

            if (!xarModAPIFunc(
                'mail', 'admin', 'sendmail',
                array(
                    'info'     => $data['email'],
                    'name'     => $data['name'],
                    'subject'  => $subject,
                    'message'  => $message,
                    'from'     => $femail,
                    'fromname' => $fname
                )
            )) return;

            // lets update status and display updated configuration
            xarResponseRedirect(xarModURL('roles', 'user', 'view'));

            break;
    }

    return $data;
}

?>