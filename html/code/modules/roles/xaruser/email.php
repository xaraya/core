<?php
/**
 * Send email to a user
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
 * Send email to a user
 *
 * @author  John Cox
 * @access  public
 * @param   id is the id of the user being sent
 * @return mixed data array for the template display or output display string if invalid data submitted
 * @throws  XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION'
 * @todo    handle empty subject and/or message?
 */
function roles_user_email(Array $args=array())
{
    // we can only send emails to other members if we are logged in
    if(!xarUserIsLoggedIn())
    {
        throw new ForbiddenOperationException(null,'You are not logged in, sending emails is not allowed');
    }

    extract($args);

    if (!xarVarFetch('id', 'int:1:', $id, 0, XARVAR_NOT_REQUIRED)) return;
    if (empty($id)) return xarResponse::notFound();

    if (!xarVarFetch('phase', 'enum:modify:confirm', $phase, 'modify', XARVAR_NOT_REQUIRED)) return;

    // If this validation fails, then do NOT send an e-mail, but
    // re-present the form to the user with an error message. Don't redirect,
    // just ensure the state is pulled back the start ('modify').
    $valid_flag = true;
    $error_message = '';
    // WATCH OUT: &= is not the same as =&
    try {
        xarVarFetch('subject', 'html:restricted', $subject);
        xarVarFetch('message', 'html:restricted', $message);
    } catch (ValidationExceptions $e) {
        // Ensure we don't sent the e-mail.
        $phase = 'modify';
        // Catch the error message.
        $error_message = $e->getMessage();
    }

    // Security Check
    if (!xarSecurityCheck('ReadRoles')) return;

    switch(strtolower($phase)) {
        case 'modify':
        default:
            // Get user information
            $data = xarMod::apiFunc(
                'roles', 'user', 'get',
                array('id' => $id)
            );

            if ($data == false) return;

            $data['subject'] = $subject;
            $data['message'] = $message;
            $data['error_message'] = $error_message;

            $data['authid'] = xarSecGenAuthKey();

            xarTpl::setPageTitle(xarML('Mail User'));
            break;

        case 'confirm':
            // Bug 3342: don't allow arbitrary sender and recipient name details to be passed in.
            //if (!xarVarFetch('fname','str:1:100',$fname)) return;
            //if (!xarVarFetch('femail','str:1:100',$femail)) return;
            //if (!xarVarFetch('name', 'str:1:100', $name)) return;

            // Confirm authorisation code.
            if (!xarSecConfirmAuthKey()) {
                return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
            }        

            // Security Check
            if (!xarSecurityCheck('ReadRoles')) return;

            // If the sender details have not been passed in to $args, then
            // fetch them from the current user now.
            if (!isset($fname) || !iseet($femail)) {
                // Get details of the sender.
                $fname = xarUserGetVar('name');
                $femail = xarUserGetVar('email');
            }

            list($message) = xarModCallHooks('item', 'transform', $id, array($message));

            // Get user information
            $data = xarMod::apiFunc('roles', 'user', 'get', array('id' => $id));

            if ($data == false) return;

            if (!xarMod::apiFunc(
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
            xarController::redirect(xarModURL('roles', 'user', 'viewlist'));

            break;
    }

    return $data;
}

?>
