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
use xarRoles;
use BadParameterException;
use DataNotFoundException;

/**
 * roles admin createpassword function
 * @extends MethodClass<AdminGui>
 */
class CreatepasswordMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * createpassword - create a new password for the user
     * @see AdminGui::createpassword()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Security
        if (!$this->sec()->checkAccess('EditRoles')) {
            return;
        }

        // Get parameters
        $this->var()->check('state', $state);
        $this->var()->find('groupid', $groupid, 'int:0:', 0);
        $this->var()->check('id', $id);
        if (empty($id)) {
            $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
            $vars = ['parameters', 'admin', 'createpassword', 'Roles'];
            throw new BadParameterException($vars, $msg);
        }

        $pass = $userapi->makepass();
        if (empty($pass)) {
            throw new DataNotFoundException([], 'Problem generating new password');
        }
        $role = $this->user()->getRole('id', (int) $id);
        $modifiedstatus = $role->setPass($pass);
        if (!$role->updateItem()) {
            return;
        }

        if (!$this->mod()->getVar('askpasswordemail')) {
            $this->ctl()->redirect($this->ctl()->getModuleURL(
                'roles',
                'admin',
                'showusers',
                ['id' => $groupid, 'state' => $state]
            ));
        } else {
            $this->session()->setVar('tmppass', $pass);
            $this->ctl()->redirect($this->ctl()->getModuleURL(
                'roles',
                'admin',
                'asknotification',
                ['id' => [$id => '1'], 'mailtype' => 'password', 'groupid' => $groupid, 'state' => $state]
            ));
        }
        return true;
    }
}
