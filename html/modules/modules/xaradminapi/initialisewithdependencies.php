<?php

/**
 * Initialize module with its dependencies.
 *
 * @param $maindId int ID of the module to look dependents for
 * @returns bool
 * @return true on dependencies activated, false for not
 * @raise NO_PERMISSION
 */
function modules_adminapi_initialisewithdependencies($args) 
{
	
	$mainId = $args['regid'];

	// Security Check
	// need to specify the module because this function is called by the installer module
	if (!xarSecurityCheck('AdminModules', 1, 'All', 'All', 'modules'))
		return;

	// Argument check
	if (!isset($mainId)) {
		$msg = xarML('Missing module regid (#(1)).', $mainId);
		xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
		return;
	}

	// See if we have lost any modules since last generation
	if (!xarModAPIFunc('modules', 'admin', 'checkmissing')) {
		return;
	}

	// Get module information
	$modInfo = xarModGetInfo($mainId);
	if (!isset($modInfo)) {
		xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST', new SystemException(__FILE__."(".__LINE__."): Module (regid: $regid) does not exist."));
		return;
	}

	switch ($modInfo['state']) {
		case XARMOD_STATE_INACTIVE:
		case XARMOD_STATE_ACTIVE:
		case XARMOD_STATE_UPGRADED: 
			//It is already initialised
			return true;
		default:
		break;
	}


	$dependency = $modInfo['dependency'];

	if (empty($dependency)) {
		$dependency = array();
	}

	//The dependencies are ok, they shouldnt change in the middle of the 
	//script execution, so let's assume this.
	foreach ($dependency as $module_id => $conditions) {
		if (is_array($conditions)) {
			//The module id is in $modId
			$modId = $module_id;
		} else {
			//The module id is in $conditions
			$modId = $conditions;
		}

		if (!xarModAPIFunc('modules', 'admin', 'initialisewithdependencies', array('regid'=>$modId))) {
			$msg = xarML('Unable to initialize dependecy module with ID (#(1)).', $modId);
			xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', $msg);
			return;
		}
	}

	// Finally, now that dependencies are dealt with, initialize the module
	if (!xarModAPIFunc('modules', 'admin', 'initialise', array('regid' => $mainId))) {
		$msg = xarML('Unable to initialize module "#(1)".', $modInfo['displayname']);
		xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', $msg);
		return;
	}

	return true;
}

?>
