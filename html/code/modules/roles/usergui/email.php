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
        if (!$this->user()->isLoggedIn()) {
            throw new ForbiddenOperationException(null, 'You are not logged in, sending emails is not allowed', $this->getContext());
        }

        extract($args);

        $this->var()->find('id', $id, 'int:1:', 0);
        if (empty($id)) {
            return $this->ctl()->notFound();
        }

        $this->var()->find('phase', $phase, 'enum:modify:confirm', 'modify');

        // If this validation fails, then do NOT send an e-mail, but
        // re-present the form to the user with an error message. Don't redirect,
        // just ensure the state is pulled back the start ('modify').
        $valid_flag = true;
        $error_message = '';
        // WATCH OUT: &= is not the same as =&
        try {
            $this->var()->get('subject', $subject, 'html:restricted');
            $this->var()->get('message', $message, 'html:restricted');
        } catch (ValidationExceptions $e) {
            // Ensure we don't sent the e-mail.
            $phase = 'modify';
            // Catch the error message.
            $error_message = $e->getMessage();
        }

        // Security Check
        if (!$this->sec()->checkAccess('ReadRoles')) {
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

                $data['authid'] = $this->sec()->genAuthKey();

                $this->tpl()->setPageTitle($this->ml('Mail User'));
                break;

            case 'confirm':
                // Bug 3342: don't allow arbitrary sender and recipient name details to be passed in.
                //$this->var()->find('fname', $fname, 'str:1:100');
                //$this->var()->find('femail', $femail, 'str:1:100');
                //$this->var()->find('name', $name, 'str:1:100');

                // Confirm authorisation code.
                if (!$this->sec()->confirmAuthKey()) {
                    return $this->ctl()->badRequest('bad_author');
                }

                // Security Check
                if (!$this->sec()->checkAccess('ReadRoles')) {
                    return;
                }

                // If the sender details have not been passed in to $args, then
                // fetch them from the current user now.
                if (!isset($fname) || !isset($femail)) {
                    // Get details of the sender.
                    $fname = $this->user()->getName();
                    $femail = $this->user()->getEmail();
                }

                [$message] = $this->mod()->callHooks('item', 'transform', $id, [$message]);

                // Get user information
                $data = $userapi->get(['id' => $id]);

                if ($data == false) {
                    return;
                }

                if (!$this->mod()->apiFunc(
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
                $this->ctl()->redirect($this->ctl()->getModuleURL('roles', 'user', 'viewlist'));

                return true;
        }

        return $data;
    }
}
