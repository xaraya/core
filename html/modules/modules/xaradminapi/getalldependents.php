<?php

/**
 * Find all the modules dependents recursively
 *
 * @param $maindId int ID of the module to look dependents for
 * @returns bool
 * @return true on dependencies activated, false for not
 * @raise NO_PERMISSION
 */
function modules_adminapi_getalldependents ($args) 
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
	$dependents_array                  = array();
	$dependents_array['active']        = array();
	$dependents_array['initialised']   = array();
	
    //Get all modules in the filesystem
    $fileModules = xarModAPIFunc('modules','admin','getfilemodules');
    if (!isset($fileModules)) return;

    // Get all modules in DB
    $dbModules = xarModAPIFunc('modules','admin','getdbmodules');
    if (!isset($dbModules)) return;

    foreach ($fileModules as $name => $modinfo) {

	    // If the module is not in the database, than its not initialised or activated
        if (empty($dbModules[$name])) continue;
		
		if (isset($modinfo['dependency']) &&
		    !empty($modinfo['dependency'])) {
			$dependency = $modinfo['dependency'];
		} else {
			$dependency = array();
		}
		
		foreach ($dependency as $module_id => $conditions) {
			if (is_array($conditions)) {
				//The module id is in $modId
				$modId = $module_id;
			} else {
				//The module id is in $conditions
				$modId = $conditions;
			}
			
			//Not depedent, then go to the next dependency!!!
			if ($modId != $mainId) continue;
			
			//If we are here, then it is dependent
			$output = xarModAPIFunc('modules', 'admin', 'getalldependents', array('regid' => $modinfo['regid'])); 
			if (!$output) {
				$msg = xarML('Unable to get dependencies for module with ID (#(1)).', $modinfo['regid']);
				xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', $msg);
				return;
			}
	
			//This is giving : recursing detected.... ohh well
			//$dependency_array = array_merge_recursive($dependency_array, $output);
	
			$dependents_array['active'] = array_merge(
				$dependents_array['active'], 
				$output['active']);
			$dependents_array['initialised'] = array_merge(
				$dependents_array['initialised'], 
				$output['initialised']);
		}
	}

	// Get module information
	$modInfo = xarModGetInfo($mainId);

	//TODO: Add version checks later on
	switch ($modInfo['state']) {
		case XARMOD_STATE_ACTIVE:
		case XARMOD_STATE_UPGRADED: 
			//It is satisfied if already initialized
			$dependents_array['active'][] = $modInfo;
		break;
		case XARMOD_STATE_INACTIVE:
		default:
			//If not then it is satisfiable
			$dependents_array['initialised'][] = $modInfo;
		break;
	}

	return $dependents_array;
}

?>