<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\AdminGui;
use Xaraya\Modules\Roles\UserApi;
use Xaraya\Modules\Roles\AdminApi;
use BadParameterException;
use xarController;
use xarMod;
use xarModVars;
use xarRoles;
use xarSec;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles admin updatestate function
 * @extends MethodClass<AdminGui>
 */
class UpdatestateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Update users from roles_admin_showusers
     * @package modules\roles
     * @subpackage roles
     * @category Xaraya Web Applications Framework
     * @version 2.4.0
     * @copyright see the html/credits.html file in this release
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @link http://xaraya.info/index.php/release/27.html
     * @see AdminGui::updatestate()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!xarSecurity::check('EditRoles')) {
            return;
        }

        $data = [];
        // Get parameters
        $this->var()->check('status', $data['status'], 'int:0:', null);
        $this->var()->find('state', $data['state'], 'int:0:', 0);
        $this->var()->find('groupid', $data['groupid'], 'int:0:', 1);
        $this->var()->find('updatephase', $updatephase, 'str:1:', 'update');
        $this->var()->find('ids', $ids);

        $data['authid'] = xarSec::genAuthKey();
        // invalid fields (we'll check this below)
        // check if the username is empty
        //Note : We should not provide xarML here. (should be in the template for better translation)
        //Might be additionnal advice about the invalid var (but no xarML..)
        if (!isset($ids)) {
            $invalid = xarML('You must choose the users to change their state');
        }
        if (isset($invalid)) {
            // if so, return to the previous template
            return xarController::redirect(xarController::URL(
                'roles',
                'admin',
                'showusers',
                ['authid'  => $data['authid'],
                    'state'   => $data['state'],
                    'invalid' => $invalid,
                    'id'     => $data['groupid']]
            ), null, $this->getContext());
        }
        //Get the notice message
        switch ($data['status']) {
            case xarRoles::ROLES_STATE_INACTIVE :
                $mailtype = 'deactivation';
                break;
            case xarRoles::ROLES_STATE_NOTVALIDATED :
                $mailtype = 'validation';
                break;
            case xarRoles::ROLES_STATE_ACTIVE :
                $mailtype = 'welcome';
                break;
            case xarRoles::ROLES_STATE_PENDING :
                $mailtype = 'pending';
                break;
            default:
                $mailtype = 'blank';
                break;
        }

        // Why so late? ids check never reaches this.
        if ((!isset($ids)) || (!isset($data['status']))
                             || (!is_numeric($data['status']))
                             || ($data['status'] < 1)
                             || ($data['status'] > 4)) {

            $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
            $vars = ['parameters', 'admin', 'updatestate', 'Roles'];
            throw new BadParameterException($vars, $msg);
        }
        $idnotify = [];
        foreach ($ids as $id => $val) {
            //check if the user must be updated :
            $role = xarRoles::get($id);
            if ($role->getState() != $data['status']) {
                if ($data['status'] == xarRoles::ROLES_STATE_NOTVALIDATED) {
                    $valcode = $userapi->makepass();
                } else {
                    $valcode = null;
                }
                //Update the user
                if (!$adminapi->stateupdate(['id'     => $id,
                    'state'   => $data['status'],
                    'valcode' => $valcode])) {
                    return;
                }
                $idnotify[$id] = 1;
            }
        }
        $ids = $idnotify;
        // Success
        if ((!xarModVars::get('roles', 'ask' . $mailtype . 'email')) || (count($idnotify) == 0)) {
            xarController::redirect(xarController::URL(
                'roles',
                'admin',
                'showusers',
                ['id' => $data['groupid'], 'state' => $data['state']]
            ), null, $this->getContext());
        } else {
            xarController::redirect(xarController::URL(
                'roles',
                'admin',
                'asknotification',
                ['id' => $ids, 'mailtype' => $mailtype, 'groupid' => $data['groupid'], 'state' => $data['state']]
            ), null, $this->getContext());
        }
        return true;
    }
}
