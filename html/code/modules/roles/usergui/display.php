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

        $this->var()->find('id', $id, 'id', $this->user()->getId());
        $this->var()->check('itemid', $itemid, 'int', null);
        $this->var()->find('itemtype', $itemtype, 'int', 1);
        $this->var()->find('tplmodule', $args['tplmodule'], 'str', 'roles');
        $this->var()->find('template', $args['template'], 'str', 'account');
        $this->var()->find('layout', $args['layout'], 'str', '');

        $id = $itemid ?? $id;


        if ($id) {
            // Get role information
            $role = xarRoles::get($id);

            if (!$role) {
                return;
            }

            $currentid = $this->user()->getId();
            if ($currentid == $id) {
                $this->ctl()->redirect($this->ctl()->getModuleURL('roles', 'user', 'account'));
            }

            $name = $role->getName();
            // Security Check
            if (!$this->sec()->check('ViewRoles', 0, 'Roles', $name)) {
                return;
            }

            $data['id'] = $role->getID();
            $itemtype = $role->getType();
            $data['itemtype'] = $itemtype;
            $data['name'] = $name;
            //get the data for a user
            if ($data['itemtype'] == xarRoles::ROLES_USERTYPE) {
                sys::import('modules.dynamicdata.class.objects.factory');
                $object = $this->data()->getObject(['name' => 'roles_users']);
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
            $item['returnurl'] = $this->ctl()->getModuleURL(
                'roles',
                'user',
                'display',
                ['id' => $id]
            );
            $data['hooks'] = $this->mod()->callHooks('item', 'display', $id, $item);

            $this->tpl()->setPageTitle($this->var()->prep($data['name']));
        } else {
            $data['id'] = $id;
            $data['uname'] = '';
        }

        $types = $userapi->getitemtypes();
        $data['itemtypename'] = $types[$itemtype]['label'];
        $data['layout'] = $args['layout'];

        $data['context'] ??= $this->getContext();
        return $this->tpl()->module($args['tplmodule'], 'user', 'display', $data, $args['template']);
    }
}
