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
use xarController;
use xarMod;
use xarRoles;
use xarSec;
use xarSecurity;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles admin addmember function
 * @extends MethodClass<AdminGui>
 */
class AddmemberMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * addMember - assign a user or group to a group
     * Make a user or group a member of another group.
     * This is an action page..
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @return string|void
     * @see AdminGui::addmember()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // get parameters
        $this->var()->find('id', $id, 'int:1:', 0);
        $this->var()->find('roleid', $roleid, 'int:1:', 0);
        if (empty($id)) {
            return xarController::notFound(null, $this->getContext());
        }
        if (empty($roleid)) {
            return xarController::notFound(null, $this->getContext());
        }
        // call the Roles class and get the parent and child objects
        $role   = xarRoles::get($roleid);
        $member = xarRoles::get($id);

        // Security
        if (!xarSecurity::check('AttachRole', 1, 'Relation', $role->getName() . ":" . $member->getName())) {
            return;
        }

        // Check for authorization code
        if (!xarSec::confirmAuthKey()) {
            return xarController::badRequest('bad_author', $this->getContext());
        }

        // check that this assignment hasn't already been made
        if ($member->isEqual($role)) {
            return xarTpl::module('roles', 'user', 'errors', ['layout' => 'self_assignment']);
        }

        // check that this assignment hasn't already been made
        if ($member->isParent($role)) {
            return xarTpl::module('roles', 'user', 'errors', ['layout' => 'duplicate_assignment']);
        }

        // check that the parent is not already a child of the child
        if ($role->isAncestor($member)) {
            return xarTpl::module('roles', 'user', 'errors', ['layout' => 'circular_assignment']);
        }

        // assign the child to the parent and bail if an error was thrown
        if (!$userapi->addmember(['id' => $id, 'gid' => $roleid])) {
            return;
        }

        // redirect to the next page
        xarController::redirect(xarController::URL(
            'roles',
            'admin',
            'modify',
            ['id' => $id]
        ), null, $this->getContext());
        return true;
    }
}
