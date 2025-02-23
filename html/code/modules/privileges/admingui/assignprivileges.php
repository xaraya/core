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
use xarConfigVars;
use xarController;
use xarDB;
use xarMod;
use xarPrivileges;
use xarRoles;
use xarSec;
use xarSecurity;
use xarVar;
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
        if (!xarSecurity::check('ManagePrivileges')) {
            return;
        }

        $data = [];
        xarVar::fetch('phase', 'str:1:100', $phase, 'modify', xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY);
        xarVar::fetch('tab', 'str:1:100', $data['tab'], 'all', xarVar::NOT_REQUIRED);
        xarVar::fetch('tabmodule', 'str:1:100', $tabmodule, 'All Modules', xarVar::NOT_REQUIRED);

        $installed = xarMod::apiFunc('modules', 'admin', 'getlist', ['filter' => ['State' => xarMod::STATE_INSTALLED]]);
        foreach ($installed as $module) {
            $moduletabs[$module['name']] = $module;
        }

        $regid = xarMod::getRegID($tabmodule);
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
                            if ($assignment['role_id'] == xarConfigVars::get(null, 'Site.User.AnonymousUID')) {
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
                if (!xarSec::confirmAuthKey()) {
                    return xarController::badRequest('bad_author', $this->getContext());
                }
                xarVar::fetch('role', 'int', $role_id, 0, xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY);
                xarVar::fetch('rolename', 'str', $rolename, '', xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY);
                xarVar::fetch('privilege', 'int', $privilege_id, 0, xarVar::NOT_REQUIRED);

                if (empty($role_id) && !empty($rolename)) {
                    $user = xarMod::apiFunc('roles', 'user', 'get', ['uname' => $rolename]);
                    $role_id = $user['id'];
                }
                if (!(empty($role_id) || empty($privilege_id))) {
                    $dbconn = xarDB::getConn();
                    $xartable = xarDB::getTables();
                    $query = "SELECT role_id FROM " . $xartable['security_acl'] . " WHERE role_id = ? AND privilege_id = ?";
                    $bindvars = [(int) $role_id,(int) $privilege_id];
                    $result = $dbconn->Execute($query, $bindvars);
                    if (!$result) {
                        return;
                    }

                    $found = false;
                    while (!$result->EOF) {
                        $found = true;
                        break;
                    }
                    if (!$found) {
                        $query = "INSERT INTO " . $xartable['security_acl'] . " VALUES (?,?)";
                        if (!$dbconn->Execute($query, $bindvars)) {
                            return;
                        }
                    }
                }

                xarController::redirect(xarController::URL(
                    'privileges',
                    'admin',
                    'assignprivileges',
                    ['tabmodule' => $tabmodule, 'tab' => $data['tab']]
                ), null, $this->getContext());
                return true;

            case 'remove':
                xarVar::fetch('assignment', 'str', $assignment, '', xarVar::NOT_REQUIRED, xarVar::PREP_FOR_DISPLAY);
                $ids = explode(',', $assignment);
                if ((count($ids) == 2) && !(empty($ids[0]) || empty($ids[1]))) {
                    $dbconn = xarDB::getConn();
                    $xartable = xarDB::getTables();
                    $query = "DELETE FROM " . $xartable['security_acl'] .
                              " WHERE role_id = ? AND privilege_id = ?";
                    $bindvars = $ids;
                    $dbconn->Execute($query, $bindvars);
                }

                xarController::redirect(xarController::URL(
                    'privileges',
                    'admin',
                    'assignprivileges',
                    ['tabmodule' => $tabmodule, 'tab' => $data['tab']]
                ), null, $this->getContext());
                return true;
        }
        $data['moduletabs'] = $moduletabs;
        $data['tabmodule'] = $tabmodule;
        $data['authid'] = xarSec::genAuthKey();
        return $data;
    }
}
