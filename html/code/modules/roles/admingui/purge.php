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
use Xaraya\Modules\Roles\AdminApi;
use Xaraya\Modules\Roles\UserApi;
use xarRoles;
use Exception;

/**
 * roles admin purge function
 * @extends MethodClass<AdminGui>
 */
class PurgeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * purge users by status
     * @param array $args
     * with
     *     'status' the status we are purging
     *     'confirmation' confirmation that this item can be purge
     * @todo kinda long, no?
     * @return array|void data for the template display
     * @see AdminGui::purge()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Security
        if (!$this->sec()->checkAccess('ManageRoles')) {
            return;
        }

        $data = [];
        // Get parameters from whatever input we need
        $this->var()->find('operation', $data['operation'], 'str', 'recall');
        $this->var()->find('confirmation', $confirmation, 'str', 0);

        extract($args);

        // Get database setup
        $dbconn = $this->db()->getConn();
        $xartable = $this->db()->getTables();
        $rolestable = $xartable['roles'];

        $deleted = '[' . $this->ml('deleted') . ']';
        $numitems = (int) $this->mod()->getVar('items_per_page');
        // Make sure a value was retrieved for items_per_page
        if (empty($numitems)) {
            $numitems = -1;
        }

        if ($data['operation'] == 'recall') {
            $this->var()->check('recallstate', $data['recallstate'], 'int:1:', null);
            $this->var()->check('recallsubmit', $recallsubmit, 'str', null);
            $this->var()->check('recallsearch', $data['recallsearch'], 'str', null);
            $this->var()->find('startnum', $startnum, 'int:1:', 1);
            $this->var()->find('recallids', $recallids, 'isset', []);
            $this->var()->find('groupid', $data['groupid'], 'int:1', 0);

            if ($confirmation == $this->ml("Recall")) {
                // --- recall users and groups
                if (!$this->sec()->checkAccess('ManageRoles')) {
                    return;
                }
                if ($data['groupid'] != 0) {
                    $parentgroup = xarRoles::get($data['groupid']);
                }
                foreach ($recallids as $id => $val) {
                    $role = xarRoles::get($id);
                    $state = $role->getType() ? xarRoles::ROLES_STATE_ACTIVE : $data['recallstate'];
                    $recalled = $adminapi->recall(['id' => $id,
                        'state' => $state]);
                    $parentgroup->addmember($role);
                }
            }
            // --- display roles that can be recalled
            //Create the selection
            $query = "SELECT id, uname, name, email, itemtype, date_reg FROM $rolestable WHERE state = ? AND date_reg != ?" ;
            $bindvars[] = xarRoles::ROLES_STATE_DELETED;
            $bindvars[] = 0;

            if (!empty($data['recallsearch'])) {
                $query .= " AND (name LIKE %" . $data['recallsearch'] . "%";
                $query .= " OR uname LIKE %" . $data['recallsearch'] . "%";
                $query .= " OR email LIKE %" . $data['recallsearch'] . "%)";
            }
            $query .= " ORDER BY name";
            $result = $dbconn->SelectLimit($query, $numitems, $startnum - 1, $bindvars);
            $roles = [];

            while ($result->next()) {
                [$id, $uname, $name, $email, $itemtype, $date_reg] = $result->fields;
                $roles[] = [
                    'id' => $id,
                    'uname' => $uname,
                    'name' => $name,
                    'email' => $email,
                    'itemtype' => $itemtype,
                    'date_reg' => $date_reg,
                ];
            }
            $data['totalselect'] = count($roles);

            if ($data['totalselect'] == 0) {
                $data['recallmessage'] = $this->ml('There are no deleted groups/users ');
            } else {
                $data['recallmessage']         = '';
            }

            $recallroles = [];
            foreach ($roles as $role) {
                // check each role's user name
                if (empty($role['uname'])) {
                    $msg = $this->ml('Execution halted: the role with id #(1) has an empty name. This needs to be corrected manually in the database.', $role['id']);
                    throw new Exception($msg);
                }
                if ($this->sec()->check('ReadRoles', 0, 'All', $role['uname'] . ":All:" . $role['id'])) {
                    $skip = 0;
                    $unique = 1;
                    $thisrole = xarRoles::get($role['id']);
                    $existinguser = $userapi->get(['uname' => $role['uname'], 'state' => xarRoles::ROLES_STATE_CURRENT]);
                    if ($thisrole->getType() != xarRoles::ROLES_USERTYPE) {
                        if (is_array($existinguser)) {
                            $unique = 0;
                        }
                        $role['uname'] = "";
                    } else {
                        $uname1 = explode($deleted, $role['uname']);
                        // checking empty unames for code robustness :-)
                        if ($uname1[0] == '') {
                            $existinguser = 0;
                            $skip = 1;
                        } elseif (is_array($existinguser)) {
                            $unique = 0;
                        }
                        $role['uname'] = $uname1[0];
                        // now check that email is unique if this has to be checked (fix for nonexisting Bug)
                        if ($this->mod()->getVar('uniqueemail')) {
                            $existinguser = $userapi->get(['email' => $email[0], 'state' => xarRoles::ROLES_STATE_CURRENT]);
                            if (is_array($existinguser)) {
                                $unique = 0;
                            }
                        }
                    }
                    if (!$skip) {
                        $types = $userapi->getitemtypes();
                        $role['itemtype'] = $types[$role['itemtype']]['label'];
                        $role['unique'] = $unique;
                        $recallroles[] = $role;
                    }
                }
            }
            // --- send to template
            $data['groups'] = $userapi->getallgroups();
            $recallfilter['startnum'] = '%%';
            $filter['state']         = $data['recallstate'];
            $recallfilter['recallsearch']   = $data['recallsearch'];
            $data['submitRecall']    = $this->ml('Recall');
            $data['recallroles']     = $recallroles;
            $data['startnum'] = $startnum;
            $data['urltemplate'] = $this->ctl()->getModuleURL('roles', 'admin', 'purge', $recallfilter);
            $data['urlitemmatch'] = '%%';
            $data['itemsperpage'] = $numitems;

        }
        //--------------------------------------------------------
        elseif ($data['operation'] == 'purge') {
            $this->var()->check('purgestate', $data['purgestate'], 'int', -1);
            $this->var()->check('purgesearch', $data['purgesearch'], 'str', null);
            $this->var()->check('purgesubmit', $purgesubmit, 'str', null);
            $this->var()->find('startnum', $startnum, 'int:1:', 1);
            $this->var()->find('purgeids', $purgeids, 'isset', []);

            // Check for confirmation.
            if ($confirmation == $this->ml("Purge")) {
                // --- purge users
                if (!$this->sec()->checkAccess('AdminRoles')) {
                    return;
                }
                foreach ($purgeids as $id => $val) {
                    // --- skip if we are trying to remove the designated site admin.
                    // TODO: insert error feedabck here somehow
                    if ($id == $this->mod()->getVar('admin')) {
                        continue;
                    }
                    // --- do this in 2 stages. First, delete the role: this will update the user
                    // --- count on all the role's parents
                    $role = xarRoles::get($id);
                    $role->deleteItem();
                    // --- now actually remove the data from the role's entry
                    $query = "UPDATE $rolestable SET name = ?, uname = ?, pass = ?, email = ?, date_reg = ?, state = ? WHERE id = ?" ;
                    $bindvars = [];
                    $bindvars[] = '';
                    $bindvars[] = $deleted . microtime(true) . '.' . $id;
                    $bindvars[] = '';
                    $bindvars[] = '';
                    $bindvars[] = 0;
                    $bindvars[] = xarRoles::ROLES_STATE_DELETED;
                    $bindvars[] = $id;
                    $dbconn = $this->db()->getConn();
                    $result = $dbconn->Execute($query, $bindvars);
                    // --- Let any hooks know that we have purged this user.
                    $item['module'] = 'roles';
                    $item['itemid'] = $id;
                    $item['method'] = 'purge';
                    $this->mod()->callHooks('item', 'delete', $id, $item);
                }
            }

            // --- display users that can be purged
            $bindvars = [];
            $selection = " WHERE email != ?";
            $bindvars[] = '';
            //Create the selection
            if ($data['purgestate'] != -1) {
                $selection .= " AND state = ? ";
                $bindvars[] = $data['purgestate'];
                switch ($data['purgestate']):
                    case xarRoles::ROLES_STATE_DELETED:
                        $data['purgestatetext'] = 'deleted';
                        break ;
                    case xarRoles::ROLES_STATE_INACTIVE:
                        $data['purgestatetext'] = 'inactive';
                        break ;
                    case xarRoles::ROLES_STATE_NOTVALIDATED:
                        $data['purgestatetext'] = 'not validated';
                        break ;
                    case xarRoles::ROLES_STATE_ACTIVE:
                        $data['purgestatetext'] = 'active';
                        break ;
                    case xarRoles::ROLES_STATE_PENDING:
                        $data['purgestatetext'] = 'pending';
                        break ;
                endswitch ;
            } else {
                $data['purgestatetext'] = '';
            }
            if (!empty($data['purgesearch'])) {
                $selection .= " AND (
                                      (name LIKE ?) OR
                                      (uname LIKE ?) OR
                                      (email LIKE ?)
                                    )";
                $bv = '%' . $data['purgesearch'] . '%';
                $bindvars[] = $bv;
                $bindvars[] = $bv;
                $bindvars[] = $bv;
            }
            // Select-clause.
            $query = '
                SELECT DISTINCT id,
                        uname,
                        name,
                        email,
                        state,
                        date_reg
                        FROM ' . $rolestable
                        . $selection
                        . ' ORDER BY name';

            $stmt = $dbconn->prepareStatement($query);

            $result = $stmt->executeQuery($bindvars);
            $data['totalselect'] = $result->getRecordCount();

            if ($startnum != 0) {
                $stmt->setLimit($numitems);
                $stmt->setOffset($startnum - 1);
                $result = $stmt->executeQuery($bindvars);
            }

            if ($data['totalselect'] == 0) {
                $data['purgemessage'] = $this->ml('There are no users selected');
            } else {
                $data['purgemessage']         = '';
            }

            $purgeusers = [];
            while ($result->next()) {
                [$id, $uname, $name, $email, $state, $date_reg] = $result->fields;
                // check each role's name and user name
                if (empty($name) || empty($uname)) {
                    $msg = $this->ml('Execution halted: the role with id #(1) has an empty name or user name. This needs to be corrected manually in the database.', $id);
                    throw new Exception($msg);
                }
                switch ($state):
                    case xarRoles::ROLES_STATE_DELETED:
                        $state = 'deleted';
                        break ;
                    case xarRoles::ROLES_STATE_INACTIVE:
                        $state = 'inactive';
                        break ;
                    case xarRoles::ROLES_STATE_NOTVALIDATED:
                        $state = 'not validated';
                        break ;
                    case xarRoles::ROLES_STATE_ACTIVE:
                        $state = 'active';
                        break ;
                    case xarRoles::ROLES_STATE_PENDING:
                        $state = 'pending';
                        break ;
                endswitch ;
                $purgeusers[] = [
                    'id'        => $id,
                    'uname'     => $uname,
                    'name'      => $name,
                    'email'     => $email,
                    'state'      => $state,
                    'date_reg'  => $date_reg,
                ];
            }
            // --- send to template
            $purgefilter['startnum'] = '%%';
            $purgefilter['purgesearch'] = $data['purgesearch'];

            $data['submitPurge'] = $this->ml('Purge');
            $data['purgeusers']  = $purgeusers;
            $data['startnum'] = $startnum;
            $data['urltemplate'] = $this->ctl()->getModuleURL('roles', 'admin', 'purge', $purgefilter);
            $data['urlitemmatch'] = '%%';
            $data['itemsperpage'] = $numitems;

        } // end elseif

        // --- finish up
        $data['authid'] = $this->sec()->genAuthKey();
        // Return
        return $data;
    }
}
