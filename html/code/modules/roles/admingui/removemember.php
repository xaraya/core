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
use xarModHooks;
use xarRoles;
use xarSec;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles admin removemember function
 * @extends MethodClass<AdminGui>
 */
class RemovememberMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * removeMember - remove a user or group from a group
     * Remove a user or group as a member of another group.
     * This is an action page..
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @return string|void
     * @see AdminGui::removemember()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // get input from any view of this page
        if (!xarVar::fetch('parentid', 'int', $parentid, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('childid', 'int', $childid, xarVar::NOT_REQUIRED)) {
            return;
        }
        // call the Roles class and get the parent and child objects
        $role   = xarRoles::get($parentid);
        $member = xarRoles::get($childid);

        // Security
        if (empty($role)) {
            return xarController::notFound(null, $this->getContext());
        }
        if (empty($member)) {
            return xarController::notFound(null, $this->getContext());
        }
        if (!xarSecurity::check('RemoveRole', 1, 'Relation', $role->getName() . ":" . $member->getName())) {
            return;
        }

        // Check for authorization code
        if (!xarSec::confirmAuthKey()) {
            return xarController::badRequest('bad_author', $this->getContext());
        }

        // remove the child from the parent and bail if an error was thrown
        if (!$userapi->removemember(['id' => $childid, 'gid' => $parentid])) {
            return;
        }

        // call item create hooks (for DD etc.)
        $pargs['module']   = 'roles';
        $pargs['itemtype'] = $role->getType(); // we might have something separate for groups later on
        $pargs['itemid']   = $parentid;
        xarModHooks::call('item', 'unlink', $parentid, $pargs);

        // redirect to the next page
        xarController::redirect(xarController::URL(
            'roles',
            'admin',
            'modify',
            ['id' => $childid]
        ), null, $this->getContext());
        return true;
    }
}
