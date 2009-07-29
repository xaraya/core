<?php
/**
 * Verifies if all dependencies of a module are satisfied.
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Verifies if all dependencies of a module are satisfied.
 * To be used before initializing a module.
 *
 * @author Xaraya Development Team
 * @param $maindId int ID of the module to look dependents for
 * @returns bool
 * @return true on dependencies verified and ok, false for not
 * @throws NO_PERMISSION
 */
function modules_adminapi_verifydependency($args)
{
    $mainId = $args['regid'];

    // Security Check
    // need to specify the module because this function is called by the installer module
    if(!xarSecurityCheck('AdminModules',1,'All','All','modules')) return;

    // Argument check
    if (!isset($mainId)) throw new EmptyParameterException('mainId');

    // Get module information
    $modInfo = xarModGetInfo($mainId);
    if (!isset($modInfo)) throw new ModuleBaseInfoNotFoundException("with regid $regid");

    // See if we have lost any modules since last generation
    if (!xarModAPIFunc('modules','admin','checkmissing')) {
        throw new ModuleNotFoundException();
    }

    // Get all modules in DB
    // A module is able to fullfil a dependency if it is not missing.
    // So db modules should be a safe start to go looking for them
    $dbModules = xarModAPIFunc('modules','admin','getdbmodules');
    if (!isset($dbModules)) throw new ModuleNotFoundException();

    $dbMods = array();

    //Find the modules which are active (should upgraded be added too?)
    foreach ($dbModules as $name => $dbInfo) {
        if (($dbInfo['state'] != XARMOD_STATE_MISSING_FROM_UNINITIALISED) && ($dbInfo['state'] < XARMOD_STATE_MISSING_FROM_INACTIVE))
        {
            $dbMods[$dbInfo['regid']] = $dbInfo;
        }
    }

    if (!empty($modInfo['extensions'])) {
        foreach ($modInfo['extensions'] as $extension) {
            if (!empty($extension) && !extension_loaded($extension)) {
                $msg = xarML("Required PHP extension '#(1)' is missing for module '#(2)'", $extension, $modInfo['displayname']);
                throw new Exception($msg);
            }
        }
    }

    $dependency = $modInfo['dependency'];

    if (empty($dependency)) {
        $dependency = array();
    }

    foreach ($dependency as $module_id => $conditions) {

        if (is_array($conditions)) {

            //Required module inexistent
            if (!isset($dbMods[$module_id]))
                throw new ModuleNotFoundException($module_id,'Required module missing (ID #(1))');

            if (xarModAPIFunc('base','versions','compare',array(
                'ver1'      => $conditions['minversion'],
                'ver2'      => $dbMods[$module_id]['version'],
                'normalize' => 'numeric')) < 0) {
                //Need to add some info for the user
                return false; // 1st version is bigger
            }

           //Not to be checked, at least not for now
           /*
            if (xarModAPIFunc('base','versions','compare',array(
                'ver1'       => $conditions['maxversion'],
                'ver2'       => $dbMods[$module_id]['version'],
                'normalize'  => 'numeric')) > 0) {
                //Need to add some info for the user
                return false; // 1st version is smaller
            }
            */

        } else {
            //Required module inexistent
            if (!isset($dbMods[$conditions]))
                throw new ModuleNotFoundException($conditions,'Required module missing (ID #(1))');
        }
    }

    return true;
}

?>
