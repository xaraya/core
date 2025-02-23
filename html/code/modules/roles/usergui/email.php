<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\UserGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\UserGui;
use Xaraya\Modules\Roles\UserApi;
use ForbiddenOperationException;
use ValidationExceptions;
use xarController;
use xarMod;
use xarModHooks;
use xarSec;
use xarSecurity;
use xarTpl;
use xarUser;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles user email function
 * @extends MethodClass<UserGui>
 */
class EmailMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Send email to a user
     * @author John Cox
     * @access public
     * @param array<string,mixed> $args id is the id of the user being sent
     * @return mixed data array for the template display or output display string if invalid data submitted
     * @throws \ForbiddenOperationException
     * @todo handle empty subject and/or message?
     * @see UserGui::email()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // we can only send emails to other members if we are logged in
        if (!xarUser::isLoggedIn()) {
            throw new ForbiddenOperationException(null, 'You are not logged in, sending emails is not allowed', $this->getContext());
        }

        extract($args);

        xarVar::fetch('id', 'int:1:', $id, 0, xarVar::NOT_REQUIRED);
        if (empty($id)) {
            return xarController::notFound(null, $this->getContext());
        }

        xarVar::fetch('phase', 'enum:modify:confirm', $phase, 'modify', xarVar::NOT_REQUIRED);

        // If this validation fails, then do NOT send an e-mail, but
        // re-present the form to the user with an error message. Don't redirect,
        // just ensure the state is pulled back the start ('modify').
        $valid_flag = true;
        $error_message = '';
        // WATCH OUT: &= is not the same as =&
        try {
            xarVar::fetch('subject', 'html:restricted', $subject);
            xarVar::fetch('message', 'html:restricted', $message);
        } catch (ValidationExceptions $e) {
            // Ensure we don't sent the e-mail.
            $phase = 'modify';
            // Catch the error message.
            $error_message = $e->getMessage();
        }

        // Security Check
        if (!xarSecurity::check('ReadRoles')) {
            return;
        }

        switch (strtolower($phase)) {
            case 'modify':
            default:
                // Get user information
                $data = $userapi->get(
                    ['id' => $id]
                );

                if ($data == false) {
                    return;
                }

                $data['subject'] = $subject;
                $data['message'] = $message;
                $data['error_message'] = $error_message;

                $data['authid'] = xarSec::genAuthKey();

                xarTpl::setPageTitle(xarML('Mail User'));
                break;

            case 'confirm':
                // Bug 3342: don't allow arbitrary sender and recipient name details to be passed in.
                //xarVar::fetch('fname','str:1:100',$fname);
                //xarVar::fetch('femail','str:1:100',$femail);
                //xarVar::fetch('name', 'str:1:100', $name);

                // Confirm authorisation code.
                if (!xarSec::confirmAuthKey()) {
                    return xarController::badRequest('bad_author', $this->getContext());
                }

                // Security Check
                if (!xarSecurity::check('ReadRoles')) {
                    return;
                }

                // If the sender details have not been passed in to $args, then
                // fetch them from the current user now.
                if (!isset($fname) || !isset($femail)) {
                    // Get details of the sender.
                    $fname = xarUser::getVar('name');
                    $femail = xarUser::getVar('email');
                }

                [$message] = xarModHooks::call('item', 'transform', $id, [$message]);

                // Get user information
                $data = $userapi->get(['id' => $id]);

                if ($data == false) {
                    return;
                }

                if (!xarMod::apiFunc(
                    'mail',
                    'admin',
                    'sendmail',
                    [
                        'info'     => $data['email'],
                        'name'     => $data['name'],
                        'subject'  => $subject,
                        'message'  => $message,
                        'from'     => $femail,
                        'fromname' => $fname,
                    ]
                )) {
                    return;
                }

                // lets update status and display updated configuration
                xarController::redirect(xarController::URL('roles', 'user', 'viewlist'), null, $this->getContext());

                break;
        }

        return $data;
    }
}
