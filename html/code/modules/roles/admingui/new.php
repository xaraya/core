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
use xarRoles;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles admin new function
 * @extends MethodClass<AdminGui>
 */
class NewMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Show new role form
     * @author Marc Lutolf
     * @author Johnny Robeson
     * @return array|string|bool|void data for the template display
     * @see AdminGui::new()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('AddRoles')) {
            return;
        }

        $data = [];
        $this->var()->find('parentid', $data['parentid'], 'id', (int) $this->mod()->getVar('defaultgroup'));
        $this->var()->find('itemtype', $data['itemtype'], 'int', xarRoles::ROLES_USERTYPE);
        $this->var()->find('duvs', $data['duvs'], 'array', []);
        $this->var()->find('confirm', $confirm, 'str', '');

        if ($data['itemtype'] == xarRoles::ROLES_USERTYPE) {
            $name = 'roles_users';
        } elseif ($data['itemtype'] == xarRoles::ROLES_GROUPTYPE) {
            $name = 'roles_groups';
        }

        $data['object'] = $this->data()->getObject(['name'   => $name]);

        // call item new hooks
        $item = $data;
        $item['exclude_module'] = ['dynamicdata'];
        $item['module'] = 'roles';
        $item['itemtype'] = $data['itemtype'];
        $item['itemid'] = '';
        $data['hooks'] = $this->mod()->notifyHooks('ItemNew', $item);

        if ($confirm) {
            // Check for a valid confirmation key
            if (!$this->sec()->confirmAuthKey()) {
                return;
            }

            // Enforce a check on the existence of a user of this user name
            $data['object']->properties['uname']->validation_existrule = 1;

            // Get the data from the form
            $isvalid = $data['object']->checkInput();

            if (!$isvalid) {
                // Bad data: redisplay the form with error messages
                $data['context'] ??= $this->getContext();
                return $this->tpl()->module('roles', 'admin', 'new', $data);
            } else {
                // Good data: create the item
                $itemid = $data['object']->createItem();

                // Jump to the next page
                $this->ctl()->redirect($this->ctl()->getModuleURL('roles', 'admin', 'new'));
                return true;
            }
        }
        return $data;
    }
}
