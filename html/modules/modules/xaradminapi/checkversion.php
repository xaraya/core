<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Checks for change in module versions, and updates the status of them if any is found
 *
 * @author Xaraya Development Team
 * @param none
 * @return bool null on exceptions, true on sucess to update
 * @raise NO_PERMISSION
 */
function modules_adminapi_checkversion()
{
    static $check = false;

    // Now with dependency checking, this function may be called multiple times
    // Let's check if it already return ok and stop the processing here
    if ($check) {return true;}

    // Security Check
    // need to specify the module because this function is called by the installer module
    if(!xarSecurityCheck('AdminModules',1,'All','All','modules')) return;

    // Get all modules in the filesystem
    $fileModules = xarModAPIFunc('modules','admin','getfilemodules');
    if (!isset($fileModules)) return;

    // Get all modules in DB
    $dbModules = xarModAPIFunc('modules','admin','getdbmodules');
    if (!isset($dbModules)) return;

    // See if we have lost any modules since last generation
    foreach ($dbModules as $name => $modInfo) {

        // First we check if this module belongs to class Core or not
        if(substr($modInfo['class'], 0, 4)  == 'Core'){
            // Yup, this module either belongs to Core or maskarading as such..
            
            // although it's unlikeley that such a module is uninitialised
            // lets check anyway, and if so just skip it for now..
            // our main objective here, however, is to catch core modules that have been upgraded
            // then we must try hard to upgrade and activate it transparently
            if (!empty($fileModules[$name]) && $modInfo['version'] != $fileModules[$name]['version']) {
    
                // Get module ID
                $regId = $modInfo['regid'];
                switch ($modInfo['state']) {
                    case XARMOD_STATE_UNINITIALISED:
                        break;
                    case XARMOD_STATE_INACTIVE || XARMOD_STATE_ACTIVE || XARMOD_STATE_UPGRADED:
                        $newstate = XARMOD_STATE_INACTIVE;
                        xarModAPIFunc('modules','admin','upgrade',
                                        array(    'regid'    => $regId,
                                                'state'    => $newstate));
                        
                        $newstate = XARMOD_STATE_ACTIVE;
                        xarModAPIFunc('modules','admin','activate',
                                        array(    'regid'    => $regId,
                                                'state'    => $newstate));
                        break;
                }
            }
            
            // We are going to upgrade and activate it transparently
            
        } else {
            // It is and ordinary mortal module, no special treatment for it
        
            //TODO: Add check for any module that might depend on this one
            // If found, change its state to something inoperative too
            // New state? XAR_MODULE_DEPENDENCY_MISSING?
    
            if (!empty($fileModules[$name]) && $modInfo['version'] != $fileModules[$name]['version']) {
    
                // Get module ID
                $regId = $modInfo['regid'];
                switch ($modInfo['state']) {
                    case XARMOD_STATE_UNINITIALISED:
                        break;
                    case XARMOD_STATE_INACTIVE:
                        $newstate = XARMOD_STATE_UPGRADED;
                        break;
                    case XARMOD_STATE_ACTIVE:
                        $newstate = XARMOD_STATE_UPGRADED;
                        break;
                    case XARMOD_STATE_UPGRADED:
                        $newstate = XARMOD_STATE_UPGRADED;
                        break;
                }
                if (isset($newstate)) {
                    $set = xarModAPIFunc('modules','admin','setstate',
                                        array(    'regid'    => $regId,
                                                'state'    => $newstate));
                }
            }
        }
    }

    $check = true;

    return true;
}

?>
