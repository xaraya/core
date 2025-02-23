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
use xarSecurity;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles admin display function
 * @extends MethodClass<AdminGui>
 */
class DisplayMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * display role
     * @return array|string|void data for the template display
     * @see AdminGui::display()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        xarVar::fetch('itemtype', 'id', $itemtype, 1, xarVar::NOT_REQUIRED);
        xarVar::fetch('id', 'int:1:', $id, 0, xarVar::NOT_REQUIRED);
        if (empty($id)) {
            return xarController::notFound(null, $this->getContext());
        }


        $data = [];
        sys::import('modules.roles.class.roles');
        $role = xarRoles::get($id);

        $data['itemtype'] = $role->getType();

        // get the array of parents of this role
        // need to display this in the template
        $parents = [];
        foreach ($role->getParents() as $parent) {
            $parents[] = ['parentid' => $parent->getID(),
                'parentname' => $parent->getName(),
                'parentuname' => $parent->getUname()];
        }
        $data['parents'] = $parents;

        $name = $role->getName();

        // Security
        if (!xarSecurity::check('EditRoles', 1, 'Roles', $name)) {
            return;
        }

        $data['frozen'] = xarSecurity::check('ViewRoles', 0, 'Roles', $name);

        $data['id'] = $id;

        $types = $userapi->getitemtypes();

        $data['name'] = $name;

        $item = $data;
        $item['exclude_module'] = ['dynamicdata'];
        $item['module'] = 'roles';
        $item['itemtype'] = $data['itemtype']; // handle groups differently someday ?
        $item['returnurl'] = xarController::URL(
            'roles',
            'user',
            'display',
            ['id' => $id]
        );
        $hooks = [];
        $hooks = xarModHooks::call('item', 'display', $id, $item);
        $data['hooks'] = $hooks;
        $data['object'] = $role;
        xarTpl::setPageTitle(xarVar::prepForDisplay($data['name']));
        return $data;
    }
}
