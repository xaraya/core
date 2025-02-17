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
use xarModVars;
use xarRoles;
use xarSec;
use xarSecurity;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles admin delete function
 * @extends MethodClass<AdminGui>
 */
class DeleteMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Delete a role
     * prompts for confirmation
     * @see AdminGui::delete()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        if (!xarVar::fetch('id', 'id', $id, 0, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('itemid', 'id', $itemid, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('confirmation', 'str:1:', $confirmation, '', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('returnurl', 'str', $returnurl, '', xarVar::NOT_REQUIRED)) {
            return;
        }

        $id = $itemid ?? $id;

        // Call the Roles class
        sys::import('modules.roles.class.roles');
        // get the role to be deleted
        $role = xarRoles::get($id);
        if (empty($role)) {
            return xarController::notFound(null, $this->getContext());
        }
        $itemtype = $role->getType();

        // get the array of parents of this role
        // need to display this in the template
        $parents = [];
        foreach ($role->getParents() as $parent) {
            $parents[] = ['parentid' => $parent->getID(),
                'parentname' => $parent->getName()];
        }
        $data['parents'] = $parents;

        $data['object'] = $role;
        $name = $role->getName();

        // Security
        if (!xarSecurity::check('ManageRoles', 1, 'Roles', $name)) {
            return;
        }

        $data['frozen'] = !xarSecurity::check('ManageRoles', 0, 'Roles', $name);

        // Prohibit removal of any groups that have children
        if ($role->countChildren()) {
            return xarTpl::module('roles', 'user', 'errors', ['layout' => 'remove_nonempty_group', 'user' => $role->getName()]);
        }
        // Prohibit removal of any groups or users the system needs
        if ($id == (int) xarModVars::get('roles', 'admin')) {
            return xarTpl::module('roles', 'user', 'errors', ['layout' => 'remove_siteadmin', 'user' => $role->getUName()]);
        }
        if ($id == (int) xarModVars::get('roles', 'defaultgroup')) {
            return xarTpl::module('roles', 'user', 'errors', ['layout' => 'default_usergroup', 'group' => $role->getName()]);
        }

        $types = $userapi->getitemtypes();
        $data['itemtypename'] = $types[$itemtype]['label'];

        if (empty($confirmation)) {
            // Load Template
            $data['itemtype'] = $itemtype;
            $types = $userapi->getitemtypes();
            $data['authid'] = xarSec::genAuthKey();
            $data['id'] = $id;
            $data['ptype'] = $role->getType();
            $data['deletelabel'] = xarML('Delete');
            $data['name'] = $name;
            $data['returnurl'] = $returnurl;
            return $data;
        } else {
            if (!xarSec::confirmAuthKey()) {
                return xarController::badRequest('bad_author', $this->getContext());
            }
            // Check to make sure the user is not active on the site.
            $check = $userapi->getactive(['id' => $id]);

            if (empty($check)) {
                // Try to remove the role and bail if an error was thrown
                if (!$role->deleteItem()) {
                    return;
                }

                // call item delete hooks (for DD etc.)
                // TODO: move to remove() function
                $pargs['exclude_module'] = ['dynamicdata'];
                $pargs['module'] = 'roles';
                $pargs['itemtype'] = $itemtype;
                $pargs['itemid'] = $id;
                xarModHooks::call('item', 'delete', $id, $pargs);
            } else {
                return xarTpl::module('roles', 'user', 'errors', ['layout' => 'remove_active_session', 'user' => $role->getName()]);
            }
            // redirect to the next page
            if (empty($returnurl)) {
                xarController::redirect(xarController::URL('roles', 'admin', 'showusers'), null, $this->getContext());
            } else {
                xarController::redirect($returnurl, null, $this->getContext());
            }
            return true;
        }
    }
}
