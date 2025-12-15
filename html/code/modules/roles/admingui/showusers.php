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
use Query;
use xarRoles;

/**
 * roles admin showusers function
 * @extends MethodClass<AdminGui>
 */
class ShowusersMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Show users of this role
     * @return array|void data for the template display
     * @see AdminGui::showusers()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Security
        if (!$this->sec()->checkAccess('EditRoles')) {
            return;
        }

        if ($this->mem()->has('roles', 'defaultgroupid')) {
            $defaultgroupid = $this->mem()->get('roles', 'defaultgroupid');
        } else {
            $defaultgroupid = $this->mod()->getVar('defaultgroup');
        }
        $this->mem()->set('roles', 'defaultgroupid', $defaultgroupid);

        $data = [];
        $this->var()->find('id', $id, 'int:0:', $defaultgroupid);
        $this->var()->find('startnum', $startnum, 'int:1:', 1);
        $this->var()->find('state', $data['state'], 'int:0:', xarRoles::ROLES_STATE_CURRENT);
        $this->var()->check('selstyle', $data['selstyle'], 'isset', $this->session()->getVar('rolesdisplay'));
        $this->var()->find('invalid', $data['invalid'], 'str:0:', null);
        $this->var()->find('order', $data['order'], 'str:0:', 'name');
        $this->var()->find('search', $data['search'], 'str:0:', null);
        $this->var()->check('reload', $reload, 'str:0:', null);
        $this->var()->check('numitems', $numitems, 'int:1', (int) $this->mod()->getVar('items_per_page'));
        if (empty($data['selstyle'])) {
            $data['selstyle'] = 0;
        }
        $this->session()->setVar('rolesdisplay', $data['selstyle']);

        // Get information on the group we're at
        $data['groups']     = $userapi->getallgroups();
        $data['groupid']   = $id;
        $data['totalusers'] = $userapi->countall();

        if ($id != 0) {
            // Call the Roles class and get the role
            $role      = xarRoles::get($id);
            $ancestors = $role->getRoleAncestors();
            $data['groupname'] = $role->getName();
            $data['itemtype'] = $role->getType();
            $data['title'] = '';
            $data['ancestors'] = [];
            foreach ($ancestors as $ancestor) {
                $data['ancestors'][] = ['name' => $ancestor->getName(),
                    'id' => $ancestor->getID()];
            }
        } else {
            $data['title'] = $this->ml('All ') . " ";
            $data['groupname'] = '';
            $data['itemtype'] = 0;
        }
        $xar = $this->getStaticServices();

        // Check if we already have a selection
        $q = new Query();
        $q = $q->sessiongetvar('rolesquery', $xar);
        $q = '';

        if (empty($q) || isset($reload)) {
            $xartable = $this->db()->getTables();
            $q = new Query('SELECT', '', '', 0, $xar);
            $q->addtable($xartable['roles'], 'r');
            $q->addfields(['r.id AS id','r.name AS name']);

            //Create the selection
            $c = [];
            if (!empty($data['search'])) {
                $c[] = $q->plike('name', '%' . $data['search'] . '%');
                $c[] = $q->plike('uname', '%' . $data['search'] . '%');
                $c[] = $q->plike('email', '%' . $data['search'] . '%');
                $q->qor($c);
            }
            $q->eq('r.itemtype', xarRoles::ROLES_USERTYPE);

            // Add state
            if ($data['state'] == xarRoles::ROLES_STATE_CURRENT) {
                $q->ne('state', xarRoles::ROLES_STATE_DELETED);
            } elseif ($data['state'] == xarRoles::ROLES_STATE_ALL) {
            } else {
                $q->eq('state', $data['state']);
            }

            // If a group was chosen, get only the users of that group
            if ($id != 0) {
                $q->addtable($xartable['rolemembers'], 'rm');
                $q->join('r.id', 'rm.role_id');
                $q->eq('rm.parent_id', $id);
            }

            // Save the query so we can reuse it somewhere
            $q->sessionsetvar('rolesquery', $xar);
        }

        // Sort ye
        // FIXME: this hardwiring is only possible because this list os not configurable
        // this should be using the properties['regdate']->source like ObjectList setSort() does
        if ($data['order'] == 'regdate') {
            $data['order'] = 'date_reg';
        }
        $q->setorder($data['order']);
        if ($data['order'] == 'date_reg') {
            $data['order'] = 'regdate';
        }

        // Add limits
        $q->setrowstodo($numitems);
        $q->setstartat($startnum);

        if (!$q->run()) {
            return;
        }


        $data['totalselect'] = $q->getrows();

        switch ($data['state']) {
            case xarRoles::ROLES_STATE_CURRENT :
            default:
                if ($data['totalselect'] == 0) {
                    $data['message'] = $this->ml('There are no users');
                }
                $data['title'] .= $this->ml('Users');
                break;
            case xarRoles::ROLES_STATE_INACTIVE:
                if ($data['totalselect'] == 0) {
                    $data['message'] = $this->ml('There are no inactive users');
                }
                $data['title'] .= $this->ml('Inactive Users');
                break;
            case xarRoles::ROLES_STATE_NOTVALIDATED:
                if ($data['totalselect'] == 0) {
                    $data['message'] = $this->ml('There are no users waiting for validation');
                }
                $data['title'] .= $this->ml('Users Waiting for Validation');
                break;
            case xarRoles::ROLES_STATE_ACTIVE:
                if ($data['totalselect'] == 0) {
                    $data['message'] = $this->ml('There are no active users');
                }
                $data['title'] .= $this->ml('Active Users');
                break;
            case xarRoles::ROLES_STATE_PENDING:
                if ($data['totalselect'] == 0) {
                    $data['message'] = $this->ml('There are no pending users');
                }
                $data['title'] .= $this->ml('Pending Users');
                break;
        }
        // assemble the info for the display
        $users = [];

        $ids = [];

        foreach ($q->output() as $row) {
            $users[$row['id']]['frozen'] = !$this->sec()->check('EditRoles', 0, 'Roles', $row['name']);

        }
        if ($id != 0) {
            $data['title'] .= " " . $this->ml('of Group') . " ";
        }

        //selstyle
        $data['style'] = ['0' => $this->ml('Simple'),
            '1' => $this->ml('Tree'),
            '2' => $this->ml('Tabbed'),
        ];

        $object = $this->data()->getObjectList(['name' => 'roles_users']);

        // Load Template
        $data['id']        = $id;
        $data['users']      = $users;
        $data['object']     = $object;
        $data['authid']     = $this->sec()->genAuthKey();
        $data['removeurl']  = $this->ctl()->getModuleURL('roles', 'admin', 'delete', ['id' => $id]);
        $filter['startnum'] = '%%';
        $filter['id']      = $id;
        $filter['state']    = $data['state'];
        $filter['search']   = $data['search'];
        $filter['order']    = $data['order'];

        $data['startnum'] = $startnum;
        $data['itemsperpage'] = $numitems;
        $data['urltemplate'] = $this->ctl()->getModuleURL('roles', 'admin', 'showusers', $filter);
        $data['urlitemmatch'] = '%%';

        return $data;
    }
}
