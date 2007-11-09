<?php
    function privileges_admin_assignprivileges()
    {
        if (!xarSecurityCheck('AdminPrivilege')) return;
        if (!xarVarFetch('phase', 'str:1:100', $phase, 'modify', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
        if (!xarVarFetch('tab', 'str:1:100', $data['tab'], 'all', XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('tabmodule', 'str:1:100', $tabmodule, 'All Modules', XARVAR_NOT_REQUIRED)) return;

		$installed = xarModAPIFunc('modules', 'admin', 'getlist', array('filter' => array('State' => XARMOD_STATE_INSTALLED)));
		foreach ($installed as $module) {
			$moduletabs[$module['name']] = $module;
		}

        $regid = xarModGetIDFromName($tabmodule);
        switch (strtolower($phase)) {
            case 'modify':
            default:
                switch ($data['tab']) {
                    case 'all':
                    default:
                    $data['assignments'] = xarPrivileges::getAssignments(array('module' => $data['tab']));
                    break;
                }

                break;

            case 'update':
                // Confirm authorisation code
                if (!xarSecConfirmAuthKey()) return;
                if (!xarVarFetch('role', 'int', $role_id, 0, XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                if (!xarVarFetch('rolename', 'str', $rolename, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                if (!xarVarFetch('privilege', 'int', $privilege_id, 0, XARVAR_NOT_REQUIRED)) return;

                if (empty($role_id) && !empty($rolename)) {
					$user = xarModAPIFunc('roles','user','get',array('uname' => $rolename));
					$role_id = $user['id'];
                }
				if (!(empty($role_id) || empty($privilege_id))) {
			        $dbconn = xarDB::getConn();
					$xartable = xarDB::getTables();
					$query = "INSERT INTO " . $xartable['security_acl'] . " VALUES (?,?)";
					$bindvars = array($role_id,$privilege_id);
					if (!$dbconn->Execute($query,$bindvars)) return;
				}

                xarResponseRedirect(xarModURL('privileges', 'admin', 'assignprivileges',array('tabmodule' => $tabmodule, 'tab' => $data['tab'])));
                return true;
                break;
            case 'remove':
                if (!xarVarFetch('assignment', 'str', $assignment, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                $ids = explode(',',$assignment);
				if ((count($ids) == 2) && !(empty($ids[0]) || empty($ids[1]))) {
			        $dbconn = xarDB::getConn();
					$xartable = xarDB::getTables();
					$query = "DELETE FROM " . $xartable['security_acl'] .
							  " WHERE partid= ? AND permid= ?";
					$bindvars = $ids;
					$dbconn->Execute($query,$bindvars);
				}

                xarResponseRedirect(xarModURL('privileges', 'admin', 'assignprivileges',array('tabmodule' => $tabmodule, 'tab' => $data['tab'])));
                return true;
                break;
        }
        $data['moduletabs'] = $moduletabs;
        $data['tabmodule'] = $tabmodule;
        $data['authid'] = xarSecGenAuthKey();
        return $data;
    }
?>
