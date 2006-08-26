<?php
/**
 * Verifies if all dependencies of a module are satisfied.
 *
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
    if (!isset($mainId)) {
        $msg = xarML('Missing module regid (#(1)).', $mainId);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));return;
    }

    // Get module information
    $modInfo = xarModGetInfo($mainId);
    if (!isset($modInfo)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST',
                       new SystemException(__FILE__."(".__LINE__."): Module (regid: $regid) does not exist."));
                       return;
    }


    // See if we have lost any modules since last generation
    if (!xarModAPIFunc('modules','admin','checkmissing')) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST', 'Missing Module');
        return;
    }

    // Get all modules in DB
    // A module is able to fullfil a dependency only if it is activated at least.
    // So db modules should be a safe start to go looking for them
    $dbModules = xarModAPIFunc('modules','admin','getdbmodules');
    if (!isset($dbModules)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST', 'Unable to find modules in the database');
        return;
    }

    $dbMods = array();

    //Find the modules which are active (should upgraded be added too?)
    foreach ($dbModules as $name => $dbInfo) {
        if ($dbInfo['state'] == XARMOD_STATE_ACTIVE) 
        {
            $dbMods[$dbInfo['regid']] = $dbInfo;
        }
    }

    if (!empty($modInfo['extensions'])) {
        foreach ($modInfo['extensions'] as $extension) {
            if (!empty($extension) && !extension_loaded($extension)) {
                xarErrorSet(
                    XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST',
                    new SystemException(xarML("Required PHP extension '#(1)' is missing for module '#(2)'", $extension, $modInfo['displayname']))
                );
                //Need to add some info for the user
                return false;
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
            if (!isset($dbMods[$module_id])) {
                xarErrorSet(
                    XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST',
                    new SystemException(xarML('Required module missing (ID #(1))', $module_id))
                );
                //Need to add some info for the user
                return false;
            }

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
            if (!isset($dbMods[$conditions])) {
                xarErrorSet(
                    XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST',
                    new SystemException(xarML('Required module missing (ID #(1))', $conditions))
                );
                //Need to add some info for the user
                return false;
            }
        }
    }

    return true;
}

?>