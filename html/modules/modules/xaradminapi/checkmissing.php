<?php
/**
 * File: $Id$
 *
 * Checks for missing modules 
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage modules module
 * @author Xaraya Team 
 */
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
            switch ($modInfo['state']) {
                case XARMOD_STATE_UNINITIALISED:
                    $newstate = XARMOD_STATE_MISSING_FROM_UNINITIALISED;
                    break;
                case XARMOD_STATE_INACTIVE:
                    $newstate = XARMOD_STATE_MISSING_FROM_INACTIVE;
                    break;
                case XARMOD_STATE_ACTIVE:
                    $newstate = XARMOD_STATE_MISSING_FROM_ACTIVE;
                    break;
                case XARMOD_STATE_UPGRADED:
                    $newstate = XARMOD_STATE_MISSING_FROM_UPGRADED;
                    break;
            }
            if (isset($newstate)) {
                $set = xarModAPIFunc('modules',
                                    'admin',
                                    'setstate',
                                    array('regid'=> $regId,
                                          'state'=> $newstate));
            }
        }
    }

    $check = true;

    return true;
}

?>