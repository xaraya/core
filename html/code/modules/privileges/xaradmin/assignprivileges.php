<?php
/**
 * @package modules
 * @subpackage privileges module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/1098.html
 */
/**
 * @return array data for the template display
 */

    function privileges_admin_assignprivileges()
    {
        // Security
        if (!xarSecurityCheck('ManagePrivileges')) return;
        
        if (!xarVarFetch('phase', 'str:1:100', $phase, 'modify', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
        if (!xarVarFetch('tab', 'str:1:100', $data['tab'], 'all', XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('tabmodule', 'str:1:100', $tabmodule, 'All Modules', XARVAR_NOT_REQUIRED)) return;

        $installed = xarMod::apiFunc('modules', 'admin', 'getlist', array('filter' => array('State' => XARMOD_STATE_INSTALLED)));
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
                    $assignments = xarPrivileges::getAssignments(array('module' => $data['tab']));
                    $data['anonassignments'] = array();
                    $data['groupassignments'] = array();
                    $data['userassignments'] = array();
                    foreach ($assignments as $assignment) {
                        if ($assignment['role_id'] == xarConfigVars::get(null,'Site.User.AnonymousUID'))
                            $data['anonassignments'][] = $assignment;
                        elseif ($assignment['role_type'] == xarRoles::ROLES_USERTYPE)
                            $data['userassignments'][] = $assignment;
                        elseif ($assignment['role_type'] == xarRoles::ROLES_GROUPTYPE)
                            $data['groupassignments'][] = $assignment;
                    }
                    break;
                }

                break;

            case 'update':
                // Confirm authorisation code
                if (!xarSecConfirmAuthKey()) {
                    return xarTpl::module('privileges','user','errors',array('layout' => 'bad_author'));
                }        
                if (!xarVarFetch('role', 'int', $role_id, 0, XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                if (!xarVarFetch('rolename', 'str', $rolename, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                if (!xarVarFetch('privilege', 'int', $privilege_id, 0, XARVAR_NOT_REQUIRED)) return;

                if (empty($role_id) && !empty($rolename)) {
                    $user = xarMod::apiFunc('roles','user','get',array('uname' => $rolename));
                    $role_id = $user['id'];
                }
                if (!(empty($role_id) || empty($privilege_id))) {
                    $dbconn = xarDB::getConn();
                    $xartable =& xarDB::getTables();
                    $query = "SELECT role_id FROM " . $xartable['security_acl'] . " WHERE role_id = ? AND privilege_id = ?";
                    $bindvars = array((int)$role_id,(int)$privilege_id);
                    $result =& $dbconn->Execute($query,$bindvars);
                    if (!$result) return;

                    $found = false;
                    while (!$result->EOF) {
                        $found = true;
                        break;
                    }
                    if (!$found) {
                        $query = "INSERT INTO " . $xartable['security_acl'] . " VALUES (?,?)";
                        if (!$dbconn->Execute($query,$bindvars)) return;
                    }
                }

                xarController::redirect(xarModURL('privileges', 'admin', 'assignprivileges',array('tabmodule' => $tabmodule, 'tab' => $data['tab'])));
                return true;
                break;
            case 'remove':
                if (!xarVarFetch('assignment', 'str', $assignment, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                $ids = explode(',',$assignment);
                if ((count($ids) == 2) && !(empty($ids[0]) || empty($ids[1]))) {
                    $dbconn = xarDB::getConn();
                    $xartable =& xarDB::getTables();
                    $query = "DELETE FROM " . $xartable['security_acl'] .
                              " WHERE role_id = ? AND privilege_id = ?";
                    $bindvars = $ids;
                    $dbconn->Execute($query,$bindvars);
                }

                xarController::redirect(xarModURL('privileges', 'admin', 'assignprivileges',array('tabmodule' => $tabmodule, 'tab' => $data['tab'])));
                return true;
                break;
        }
        $data['moduletabs'] = $moduletabs;
        $data['tabmodule'] = $tabmodule;
        $data['authid'] = xarSecGenAuthKey();
        return $data;
    }
?>
