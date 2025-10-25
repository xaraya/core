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
 * roles admin modify function
 * @extends MethodClass<AdminGui>
 */
class ModifyMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Modify role details
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return mixed data array for the template display or output display string if invalid data submitted
     * @see AdminGui::modify()
     */
    public function __invoke(array $args = [])
    {
        $data = [];
        $this->var()->find('confirm', $confirm, 'int', 0);
        $this->var()->find('id', $id, 'id', 0);
        $this->var()->check('itemid', $data['itemid'], 'id', null);
        $id = $data['itemid'] ?? $id;

        $this->var()->find('duvs', $data['duvs'], 'array', []);

        $data['object'] = xarRoles::get($id);
        if (empty($data['object'])) {
            return $this->ctl()->notFound();
        }
        $data['object']->properties['name']->display_layout = 'single';
        $data['itemtype'] = $data['object']->getType();

        $parents = [];
        $names = [];

        foreach ($data['object']->getParents() as $parent) {
            if ($this->sec()->check('RemoveRole', 0, 'Relation', $parent->getName() . ":" . $data['object']->getName())) {
                $parents[] = ['parentid' => $parent->getID(),
                    'parentname' => $parent->getName(),
                    'parentuname' => $parent->getUname()];
                $names[] = $parent->getName();
            }
        }

        $groups = [];
        foreach (xarRoles::getgroups() as $temp) {
            $nam = $temp['name'];
            // TODO: this is very inefficient. Here we have the perfect use case for embedding security checks directly into the SQL calls
            if (!$this->sec()->check('AttachRole', 0, 'Relation', $nam . ":" . $data['object']->getName())) {
                continue;
            }
            if (!in_array($nam, $names) && $temp['id'] != $id) {
                $names[] = $nam;
                $groups[] = ['did' => $temp['id'],
                    'dname' => $temp['name']];
            }
        }

        $this->session()->setVar('ddcontext.roles', [
            'return_url' => $this->ctl()->getCurrentURL(),
            'parents' => $parents,
            'groups' => $groups,
            'basetype' => $data['itemtype'],
        ]);

        // Security
        if (!$this->sec()->check('EditRole', 0, 'Roles', $data['object']->getName())) {
            if (!$this->sec()->check('ReadRoles', 1, 'Roles', $data['object']->getName())) {
                return;
            }
        }

        // call item modify hooks (for DD etc.)
        $item = $data;
        $item['exclude_module'] = ['dynamicdata'];
        $item['module'] = 'roles';
        $item['itemtype'] = $data['object']->getType();
        $item['itemid'] = $id;
        $data['hooks'] = $this->mod()->notifyHooks('ItemModify', $item);

        $data['groups'] = $groups;
        $data['parents'] = $parents;

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
                return $this->tpl()->module('roles', 'admin', 'modify', $data);
            } else {
                // Good data: create the item
                $itemid = $data['object']->updateItem(['itemid' => $data['itemid']]);

                // Jump to the next page
                $this->ctl()->redirect($this->ctl()->getModuleURL(
                    'roles',
                    'admin',
                    'modify',
                    ['itemid' => $data['itemid']]
                ));
                return true;
            }
        }
        return $data;
    }
}
