<?php

/**
 * Activate all of the modules dependencies if possible.
 *
 * @param $maindId int ID of the module to look dependents for
 * @returns bool
 * @return true on dependencies activated, false for not
 * @raise NO_PERMISSION
 */
function modules_adminapi_activatedependency($mainId)
{
    // Security Check
    // need to specify the module because this function is called by the installer module
    if(!xarSecurityCheck('AdminModules',1,'All','All','modules')) return;

    // Argument check
    if (!isset($mainId)) {
       $msg = xarML('Missing module regid (#(1)).', $mainId);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));return;
    }

    // Get module information
    $modInfo = xarModGetInfo($mainId);
    if (!isset($modInfo)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST',
                       new SystemException(__FILE__."(".__LINE__."): Module (regid: $regid) does not exist."));
                       return;
    }
    
    $dependency = $modInfo['denpendency'];

    // See if we have lost any modules since last generation
    if (!xarModAPIFunc('modules','admin','checkmissing')) {return;}

    // Get all modules in DB
    // A module is dependent only if it was already initialised at least.
    // So db modules should be a safe start to go looking for them
    $fileModules = xarModAPIFunc('modules','admin','getfilemodules');
    if (!isset($fileModules)) return;

    foreach ($fileModules as $name => $fileInfo) {
        $fileMods[$fileInfo['regid']] = $fileInfo;
    }

    foreach ($dependecy as $module_id => $conditions) {
    
        if (is_array($conditions)) {
        
            //The module id is in $modId
            $modId = $module_id;

            //Required module inexistent
            if (!isset($fileMods[$modId])) {
                //Need to add some info for the user
                return false;
            }

            //Checks if the dependency version is bigger than the min version
            if (xarModAPIFunc('base','versions','compare',array(
                'ver1' => $conditions['minversion'],
                'ver2' => $fileMods[$modId]['version'])) < 0) {
                //Need to add some info for the user
                return false; // 1st version is bigger
            }

            //Checks if the dependency version is smaller than the max version
            if (xarModAPIFunc('base','versions','compare',array(
                'ver1' => $conditions['maxversion'],
                'ver2' => $fileMods[$modId]['version'])) > 0) {
                //Need to add some info for the user
                return false; // 1st version is smaller
            }

        } else {

            //The module id is in $conditions
            $modId = $conditions;

            //Required module inexistent
            if (!isset($fileMods[$modId])) {
                //Need to add some info for the user
                return false;
            }
            
            //We just have the ID, so no conditions to check for.
        }
        
        //So far so good, lets start the dependecy's dependecies

        //TODO: Add check for loops here..
        // Modules shouldnt depend on one that depend on itself... Still a check for this case is a good idea.
        if (!xarModAPIFunc('modules','admin','activatedependency',$module_id)) {return;}
    }

    return true;
}

?>
