<?php

/**
 * Verifies if each of its dependents are ok with its version
 *
 * @param $maindId int ID of the module to look dependents for
 * @returns array
 * @return array with dependents
 * @raise NO_PERMISSION
 */
function modules_adminapi_verifydependents($mainId)
{
    // Do you think we should store these in the DB?
    // They should be rarely used, only for initialising/deactivating

    // Security Check
    // need to specify the module because this function is called by the installer module
    if(!xarSecurityCheck('AdminModules',1,'All','All','modules')) return;

    // See if we have lost any modules since last generation
    if (!xarModAPIFunc('modules','admin','checkmissing')) {return;}

    // Get module information
    $modInfo = xarModGetInfo($mainId);
    if (!isset($modInfo)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST',
                       new SystemException(__FILE__."(".__LINE__."): Module (regid: $regid) does not exist."));
                       return;
    }

    // Get all modules in DB
    // A module is dependent only if it was already initialised at least.
    // So db modules should be a safe start to go looking for them
    $dbModules = xarModAPIFunc('modules','admin','getdbmodules');
    if (!isset($dbModules)) return;

    $dbMods = array();
    
    //Finds out the modules
    foreach ($dbModules as $name => $dbInfo) {
        if ($dbInfo['state'] == XARMOD_STATE_ACTIVE ||
            $dbInfo['state'] == XARMOD_STATE_UPGRADED ||
            $dbInfo['state'] == XARMOD_STATE_INACTIVE) {
            $dbMods[$dbInfo['regid']] = $dbInfo;
        }
    }

    $dependency = $modInfo['dependency'];

    foreach ($dependecy as $module_id => $conditions) {
        //First unitialize its dependents
        if (!xarModAPIFunc('modules','admin','unitializedependents',$module_id)) {return;}

    }

    return true;
}

?>
