<?php

/**
 * Remove module and its dependents
 * To be used after the user assured he wants to unitialize the module
 * and all its dependents (should show a list of them to the user)
 *
 * @param $maindId int ID of the module to look dependents for
 * @returns array
 * @return array with dependents
 * @raise NO_PERMISSION
 */
function modules_adminapi_removewithdependents ($args)
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

	// Make xarModGetInfo not cache anything...
	//We should make a funcion to handle this instead of seeting a global var
	//or maybe whenever we have a central caching solution...
	$GLOBALS['xarMod_noCacheState'] = true;

	// Get module information
	$modInfo = xarModGetInfo($mainId);
	if (!isset($modInfo)) {
		xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST', new SystemException(__FILE__."(".__LINE__."): Module (regid: $regid) does not exist."));
		return;
	}

	$dependency = $modInfo['dependency'];

	if (empty($dependency)) {
		$dependency = array();
	}

    // Get all modules in DB
    // A module is dependent only if it was already initialised at least.
    // So db modules should be a safe start to go looking for them
    $dbModules = xarModAPIFunc('modules','admin','getdbmodules');
    if (!isset($dbModules)) return;

    //Finds out the active/upgraded/inactive modules 
    foreach ($dbModules as $name => $dbInfo) 
	{
		//At least Inactive... XARMOD_STATE are useless.. They should make
		//us able to use them to reflect that active modules were first initialised
        if ($dbInfo['state'] == XARMOD_STATE_ACTIVE ||
            $dbInfo['state'] == XARMOD_STATE_UPGRADED ||
			$dbInfo['state'] == XARMOD_STATE_INACTIVE) 
			foreach ($dbInfo['dependency'] as $module_id => $conditions) {
				if (is_array($conditions)) {
					//The module id is in $modId
					$modId = $module_id;
				} else {
					//The module id is in $conditions
					$modId = $conditions;
				}

				//If them match, then it is a dependent module				
				if ($modId == $mainId) {

					//Recurse				
					//Later on let's add some check for circular dependencies
					if (!xarModAPIFunc('modules', 'admin', 'removewithdependents', array('regid'=>$modId))) {
						$msg = xarML('Unable to initialize dependecy module with ID (#(1)).', $modId);
						xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', $msg);
						return;
					}
				}
			}
        }
	}

	switch ($modInfo['state']) {
		case XARMOD_STATE_ACTIVE:
		case XARMOD_STATE_UPGRADED: 
			//It is already installed
			$active = true;
		case XARMOD_STATE_INACTIVE:
			$active = false;
			break;

		default:
			return true;
	}

	//Checks if the module is already active
	if ($active) {
		// Finally, now that dependents are dealt with, deactivate the module
		if (!xarModAPIFunc('modules', 'admin', 'deactivate', array('regid' => $mainId))) {
			$msg = xarML('Unable to deactivate module "#(1)".', $modInfo['displayname']);
			xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', $msg);
			return;
		}
	}

	// And remove it!
	if (!xarModAPIFunc('modules', 'admin', 'remove', array('regid' => $mainId))) {
		$msg = xarML('Unable to remove module "#(1)".', $modInfo['displayname']);
		xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', $msg);
		return;
	}

	return true;
}

?>
