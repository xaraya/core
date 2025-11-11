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
use xarRoles;
use BadParameterException;

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
        if (!$this->sec()->checkAccess('EditRoles')) {
            return;
        }

        $data = [];
        // Get parameters
        $this->var()->check('status', $data['status'], 'int:0:', null);
        $this->var()->find('state', $data['state'], 'int:0:', 0);
        $this->var()->find('groupid', $data['groupid'], 'int:0:', 1);
        $this->var()->find('updatephase', $updatephase, 'str:1:', 'update');
        $this->var()->find('ids', $ids);

        $data['authid'] = $this->sec()->genAuthKey();
        // invalid fields (we'll check this below)
        // check if the username is empty
        //Note : We should not provide xarML here. (should be in the template for better translation)
        //Might be additionnal advice about the invalid var (but no xarML..)
        if (!isset($ids)) {
            $invalid = $this->ml('You must choose the users to change their state');
        }
        if (isset($invalid)) {
            // if so, return to the previous template
            return $this->ctl()->redirect($this->ctl()->getModuleURL(
                'roles',
                'admin',
                'showusers',
                ['authid'  => $data['authid'],
                    'state'   => $data['state'],
                    'invalid' => $invalid,
                    'id'     => $data['groupid']]
            ));
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
        if ((!$this->mod()->getVar('ask' . $mailtype . 'email')) || (count($idnotify) == 0)) {
            $this->ctl()->redirect($this->ctl()->getModuleURL(
                'roles',
                'admin',
                'showusers',
                ['id' => $data['groupid'], 'state' => $data['state']]
            ));
        } else {
            $this->ctl()->redirect($this->ctl()->getModuleURL(
                'roles',
                'admin',
                'asknotification',
                ['id' => $ids, 'mailtype' => $mailtype, 'groupid' => $data['groupid'], 'state' => $data['state']]
            ));
        }
        return true;
    }
}
