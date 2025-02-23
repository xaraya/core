<?php

/**
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Authsystem\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Authsystem\AdminGui;
use BadParameterException;
use xarController;
use xarMod;
use xarModVars;
use xarRoles;
use xarSecurity;
use xarSession;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * authsystem admin createpassword function
 * @extends MethodClass<AdminGui>
 */
class CreatepasswordMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Function to create a password for a user
     * @return bool|void Returns true on success, false upon failure.
     * @throws \BadParameterException Thrown if not all parameters have been given in the GET/POST data
     * @see AdminGui::createpassword()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('EditAuthsystem')) {
            return;
        }
        extract($args);

        // Get parameters
        $this->var()->check('state', $state);
        $this->var()->check('groupid', $groupid, 'int:0:', 0);
        $this->var()->check('id', $id);
        if (empty($id)) {
            throw new BadParameterException(['parameters','admin','createpassword','roles'], xarML('Invalid #(1) for #(2) function #(3)() in module #(4)'));
        }

        $pass = xarMod::apiFunc(
            'roles',
            'user',
            'makepass'
        );
        if (empty($pass)) {
            throw new BadParameterException(null, xarML('Problem generating new password'));
        }
        $role = xarRoles::get($id);
        $modifiedstatus = $role->setPass($pass);
        $modifiedrole = $role->updateItem();
        if (!$modifiedrole) {
            return;
        }
        if (!xarModVars::get('roles', 'askpasswordemail')) {
            xarController::redirect(xarController::URL(
                'roles',
                'admin',
                'showusers',
                ['id' => $groupid, 'state' => $state]
            ), null, $this->getContext());
            return true;
        } else {

            xarSession::setVar('tmppass', $pass);
            xarController::redirect(xarController::URL(
                'roles',
                'admin',
                'asknotification',
                ['id' => [$id => '1'], 'mailtype' => 'password', 'groupid' => $groupid, 'state' => $state]
            ), null, $this->getContext());
        }
    }
}
