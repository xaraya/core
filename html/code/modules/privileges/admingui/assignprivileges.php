<?php

/**
 * @package modules\privileges
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Privileges\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Privileges\AdminGui;
use ixarMod;
use xarPrivileges;
use xarRoles;
use sys;

sys::import('xaraya.modules.method');

/**
 * privileges admin assignprivileges function
 * @extends MethodClass<AdminGui>
 */
class AssignprivilegesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @return array|string|bool|void data for the template display
     * @see AdminGui::assignprivileges()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('ManagePrivileges')) {
            return;
        }

        $data = [];
        $this->var()->find('phase', $phase, 'str:1:100', 'modify');
        $this->var()->find('tab', $data['tab'], 'str:1:100', 'all');
        $this->var()->find('tabmodule', $tabmodule, 'str:1:100', 'All Modules');

        $installed = $this->mod()->apiFunc('modules', 'admin', 'getlist', ['filter' => ['State' => ixarMod::STATE_INSTALLED]]);
        foreach ($installed as $module) {
            $moduletabs[$module['name']] = $module;
        }

        $regid = $this->mod()->getRegID($tabmodule);
        switch (strtolower($phase)) {
            case 'modify':
            default:
                switch ($data['tab']) {
                    case 'all':
                    default:
                        $assignments = xarPrivileges::getAssignments(['module' => $data['tab']]);
                        $data['anonassignments'] = [];
                        $data['groupassignments'] = [];
                        $data['userassignments'] = [];
                        foreach ($assignments as $assignment) {
                            if ($assignment['role_id'] == $this->config()->getVar('Site.User.AnonymousUID')) {
                                $data['anonassignments'][] = $assignment;
                            } elseif ($assignment['role_type'] == xarRoles::ROLES_USERTYPE) {
                                $data['userassignments'][] = $assignment;
                            } elseif ($assignment['role_type'] == xarRoles::ROLES_GROUPTYPE) {
                                $data['groupassignments'][] = $assignment;
                            }
                        }
                        break;
                }

                break;

            case 'update':
                // Confirm authorisation code
                if (!$this->sec()->confirmAuthKey()) {
                    return $this->ctl()->badRequest('bad_author');
                }
                $this->var()->find('role', $role_id, 'int', 0);
                $this->var()->find('rolename', $rolename, 'str', '');
                $this->var()->find('privilege', $privilege_id, 'int', 0);

                if (empty($role_id) && !empty($rolename)) {
                    $user = $this->mod()->apiFunc('roles', 'user', 'get', ['uname' => $rolename]);
                    $role_id = $user['id'];
                }
                if (!(empty($role_id) || empty($privilege_id))) {
                    $dbconn = $this->db()->getConn();
                    $xartable = $this->db()->getTables();
                    $query = "SELECT role_id FROM " . $xartable['security_acl'] . " WHERE role_id = ? AND privilege_id = ?";
                    $bindvars = [(int) $role_id,(int) $privilege_id];
                    $result = $dbconn->Execute($query, $bindvars);
                    if (!$result) {
                        return;
                    }

                    $found = false;
                    if ($result->first()) {
                        $found = true;
                    }
                    if (!$found) {
                        $query = "INSERT INTO " . $xartable['security_acl'] . " VALUES (?,?)";
                        if (!$dbconn->Execute($query, $bindvars)) {
                            return;
                        }
                    }
                }

                $this->ctl()->redirect($this->ctl()->getModuleURL(
                    'privileges',
                    'admin',
                    'assignprivileges',
                    ['tabmodule' => $tabmodule, 'tab' => $data['tab']]
                ));
                return true;

            case 'remove':
                $this->var()->find('assignment', $assignment, 'str', '');
                $ids = explode(',', $assignment);
                if ((count($ids) == 2) && !(empty($ids[0]) || empty($ids[1]))) {
                    $dbconn = $this->db()->getConn();
                    $xartable = $this->db()->getTables();
                    $query = "DELETE FROM " . $xartable['security_acl']
                              . " WHERE role_id = ? AND privilege_id = ?";
                    $bindvars = $ids;
                    $dbconn->Execute($query, $bindvars);
                }

                $this->ctl()->redirect($this->ctl()->getModuleURL(
                    'privileges',
                    'admin',
                    'assignprivileges',
                    ['tabmodule' => $tabmodule, 'tab' => $data['tab']]
                ));
                return true;
        }
        $data['moduletabs'] = $moduletabs;
        $data['tabmodule'] = $tabmodule;
        $data['authid'] = $this->sec()->genAuthKey();
        return $data;
    }
}
