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
        $this->var()->find('itemtype', $itemtype, 'id', 1);
        $this->var()->find('id', $id, 'int:1:', 0);
        if (empty($id)) {
            return $this->ctl()->notFound();
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
        if (!$this->sec()->check('EditRoles', 1, 'Roles', $name)) {
            return;
        }

        $data['frozen'] = $this->sec()->check('ViewRoles', 0, 'Roles', $name);

        $data['id'] = $id;

        $types = $userapi->getitemtypes();

        $data['name'] = $name;

        $item = $data;
        $item['exclude_module'] = ['dynamicdata'];
        $item['module'] = 'roles';
        $item['itemtype'] = $data['itemtype']; // handle groups differently someday ?
        $item['returnurl'] = $this->ctl()->getModuleURL(
            'roles',
            'user',
            'display',
            ['id' => $id]
        );
        $hooks = [];
        $hooks = $this->mod()->callHooks('item', 'display', $id, $item);
        $data['hooks'] = $hooks;
        $data['object'] = $role;
        $this->tpl()->setPageTitle($this->var()->prep($data['name']));
        return $data;
    }
}
