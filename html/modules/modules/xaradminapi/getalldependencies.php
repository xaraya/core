<?php

/**
 * Find all the modules dependencies with all the dependencies of its
 * sibblings
 *
 * @param $maindId int ID of the module to look dependents for
 * @returns bool
 * @return true on dependencies activated, false for not
 * @raise NO_PERMISSION
 */
function modules_adminapi_getalldependencies($args) 
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

	//Initialize the dependecies array
	$dependency_array = array();
	$dependency_array['unsatisfiable'] = array();
	$dependency_array['satisfiable']   = array();
	$dependency_array['satisfied']     = array();
	
	// Get module information
	$modInfo = xarModGetInfo($mainId);
	if (!isset($modInfo)) {
		//Handle the Exception Thrown
		xarExceptionHandled();
		
		//Add this module to the unsatisfiable list
		$dependency_array['unsatisfiable'][] = $mainId;

		//Return now, we cant find more info about this module
		return $dependency_array;
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

		$output = xarModAPIFunc('modules', 'admin', 'getalldependencies', array('regid'=>$modId)); 
		if (!$output) {
			$msg = xarML('Unable to get dependencies for module with ID (#(1)).', $modId);
			xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', $msg);
			return;
		}
		//This is giving : recursing detected.... ohh well
//		$dependency_array = array_merge_recursive($dependency_array, $output);
		
		$dependency_array['satisfiable'] = array_merge(
			$dependency_array['satisfiable'], 
			$output['satisfiable']);
		$dependency_array['unsatisfiable'] = array_merge(
			$dependency_array['unsatisfiable'], 
			$output['unsatisfiable']);
		$dependency_array['satisfied'] = array_merge(
			$dependency_array['satisfied'], 
			$output['satisfied']);
	}

	// Unsatisfiable and Satisfiable are assuming the user can't
	//use some hack or something to set the modules as initialized/active
	//without its proper dependencies
	if (count($dependency_array['unsatisfiable'])) {
		//Then this module is unsatisfiable too
		$dependency_array['unsatisfiable'][] = $modInfo;
	} elseif (count($dependency_array['satisfiable'])) {
		//Then this module is satisfiable too
		//As if it were initialized, then all depdencies would have
		//to be already satisfied
		$dependency_array['satisfiable'][] = $modInfo;
	} else {
		//Then this module is at least satisfiable
		//Depends if it is already initialized or not
		
		//TODO: Add version checks later on
		switch ($modInfo['state']) {
			 case XARMOD_STATE_INACTIVE:
			 case XARMOD_STATE_ACTIVE:
			 case XARMOD_STATE_UPGRADED: 
				//It is satisfied if already initialized
				$dependency_array['satisfied'][] = $modInfo;
			break;
			default:
				//If not then it is satisfiable
				$dependency_array['satisfiable'][] = $modInfo;
			break;
		}
	}

	return $dependency_array;
}

?>
