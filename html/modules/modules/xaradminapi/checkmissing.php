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
    
    return true;
}

?>
