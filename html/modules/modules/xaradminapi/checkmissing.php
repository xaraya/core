<?php

/**
 * Checks missing modules, and updates the status of them if any is found
 *
 * @param none
 * @return bool null on exceptions, true on sucess to update
 * @raise NO_PERMISSION
 */
function modules_adminapi_checkmissing()
{
	static $check = false;
	
	//Now with dependency checking, this function may be called multiple times
	//Let's check if it already return ok and stop the processing here
	if ($check) {return true;}

    // Security Check
    // need to specify the module because this function is called by the installer module
    if(!xarSecurityCheck('AdminModules',1,'All','All','modules')) return;

    //Get all modules in the filesystem
    $fileModules = xarModAPIFunc('modules','admin','getfilemodules');
    if (!isset($fileModules)) return;

    // Get all modules in DB
    $dbModules = xarModAPIFunc('modules','admin','getdbmodules');
    if (!isset($dbModules)) return;

    // See if we have lost any modules since last generation
    foreach ($dbModules as $name => $modInfo) {

		//TODO: Add check for any module that might depend on this one
		// If found, change its state to something inoperative too
		// New state? XAR_MODULE_DEPENDENCY_MISSING?
		
        if (empty($fileModules[$name])) {
            // Old module

            // Get module ID
            $regId = $modInfo['regid'];
            // Set state of module to 'missing'
            $set = xarModAPIFunc('modules',
                                'admin',
                                'setstate',
                                array('regid'=> $regId,
                                      'state'=> XARMOD_STATE_MISSING));
            //throw back
            if (!isset($set)) return;
        }
    }
    
    $check = true;
    
    return true;
}

?>
