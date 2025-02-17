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
use DataObjectFactory;
use xarController;
use xarMod;
use xarModHooks;
use xarRoles;
use xarSecurity;
use xarTpl;
use xarUser;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles user display function
 * @extends MethodClass<UserGui>
 */
class DisplayMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Display user
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args with $args['id']
     * @return string|void output display string
     * @see UserGui::display()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var UserApi $userapi */
        $userapi = $this->userapi();

        if (!xarVar::fetch('id', 'id', $id, xarUser::getVar('id'), xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('itemid', 'int', $itemid, null, xarVar::DONT_SET)) {
            return;
        }
        if (!xarVar::fetch('itemtype', 'int', $itemtype, 1, xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('tplmodule', 'str', $args['tplmodule'], 'roles', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('template', 'str', $args['template'], 'account', xarVar::NOT_REQUIRED)) {
            return;
        }
        if (!xarVar::fetch('layout', 'str', $args['layout'], '', xarVar::NOT_REQUIRED)) {
            return;
        }

        $id = $itemid ?? $id;


        if ($id) {
            // Get role information
            $role = xarRoles::get($id);

            if (!$role) {
                return;
            }

            $currentid = xarUser::getVar('id');
            if ($currentid == $id) {
                xarController::redirect(xarController::URL('roles', 'user', 'account'), null, $this->getContext());
            }

            $name = $role->getName();
            // Security Check
            if (!xarSecurity::check('ViewRoles', 0, 'Roles', $name)) {
                return;
            }

            $data['id'] = $role->getID();
            $itemtype = $role->getType();
            $data['itemtype'] = $itemtype;
            $data['name'] = $name;
            //get the data for a user
            if ($data['itemtype'] == xarRoles::ROLES_USERTYPE) {
                sys::import('modules.dynamicdata.class.objects.factory');
                $object = DataObjectFactory::getObject(['name' => 'roles_users']);
                $object->tplmodule = $args['tplmodule'];   // roles/xartemplates/objects/
                $object->template = $args['template'];  // showdisplay-account.xt
                $object->layout = $args['layout'];
                $object->getItem(['itemid' => $id]);
                $data['object'] = $object;
                $data['uname'] = $object->properties['uname']->getValue();
            } else {
                //get the data for a group
                $data['uname'] = '';
            }
            $item = $data;
            $item['module'] = 'roles';
            $item['itemtype'] = $data['itemtype'];
            $item['itemid'] = $id;
            $item['returnurl'] = xarController::URL(
                'roles',
                'user',
                'display',
                ['id' => $id]
            );
            $data['hooks'] = xarModHooks::call('item', 'display', $id, $item);

            xarTpl::setPageTitle(xarVar::prepForDisplay($data['name']));
        } else {
            $data['id'] = $id;
            $data['uname'] = '';
        }

        $types = $userapi->getitemtypes();
        $data['itemtypename'] = $types[$itemtype]['label'];
        $data['layout'] = $args['layout'];

        $data['context'] ??= $this->getContext();
        return xarTpl::module($args['tplmodule'], 'user', 'display', $data, $args['template']);
    }
}
