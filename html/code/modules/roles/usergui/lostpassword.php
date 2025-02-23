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
use Xaraya\Modules\Roles\AdminApi;
use DataNotFoundException;
use xarController;
use xarMod;
use xarRoles;
use xarSec;
use xarSecurity;
use xarTpl;
use xarUser;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles user lostpassword function
 * @extends MethodClass<UserGui>
 */
class LostpasswordMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Sends a new password to the user if they have forgotten theirs.
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return string|void output display string
     * @see UserGui::lostpassword()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security check
        if (!xarSecurity::check('ViewRoles')) {
            return;
        }

        //If a user is already logged in, no reason to see this.
        //We are going to send them to their account.
        if (xarUser::isLoggedIn()) {
            xarController::redirect(xarController::URL('roles', 'user', 'account'), null, $this->getContext());
            return true;
        }

        xarTpl::setPageTitle(xarVar::prepForDisplay(xarML('Lost Password')));

        xarVar::fetch('phase', 'str:1:100', $phase, 'request', xarVar::NOT_REQUIRED);

        switch (strtolower($phase)) {

            case 'request':
            default:
                $data = ['showmessage' => 0];
                break;

            case 'send':

                xarVar::fetch('uname', 'str:1:100', $uname, '', xarVar::NOT_REQUIRED);
                xarVar::fetch('email', 'str:1:100', $email, '', xarVar::NOT_REQUIRED);

                // Confirm authorisation code.
                if (!xarSec::confirmAuthKey()) {
                    return xarController::badRequest('bad_author', $this->getContext());
                }

                $data['showmessage'] = 0;
                if ((empty($uname)) && (empty($email))) {
                    $data['showmessage'] = 1;
                    break;
                }

                // check for user and grab id if exists
                $user = $userapi->get(['uname' => $uname,
                    'email' => $email]);

                if (empty($user)) {
                    $data['showmessage'] = 2;
                    break;
                }
                // Make new password
                $user['pass'] = $userapi->makepass();

                if (empty($user['pass'])) {
                    throw new DataNotFoundException([], 'Problem generating new password');
                }

                // We need to tell some hooks that we are coming from the lost password screen
                // and not the update the actual roles screen.  Right now, the keywords vanish
                // into thin air.  Bug 1960 and 3161
                xarVar::setCached('Hooks.all', 'noupdate', 1);

                //Update user password
                $role = xarRoles::get($user['id']);
                $modifiedstatus = $role->setPass($user['pass']);
                if (!$role->updateItem()) {
                    return;
                }

                // Send Reminder Email
                if (!$adminapi->senduseremail(['id' => [$user['id'] => '1'], 'mailtype' => 'reminder', 'pass' => $user['pass']])) {
                    return;
                }

                // Let user know that they have an email on the way.
                $data['context'] ??= $this->getContext();
                $data = xarTpl::module('roles', 'user', 'requestpwconfirm', $data);
                break;
        }
        return $data;
    }
}
