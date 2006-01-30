<?php
/**
 * Install a module with all its dependencies.
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Install a module with all its dependencies.
 *
 * @author Xaraya Development Team
 * @param $maindId int ID of the module to look dependents for
 * @returns bool
 * @return true on dependencies activated, false for not
 * @raise NO_PERMISSION
 */
function modules_adminapi_installwithdependencies ($args)
{
    //    static $installed_ids = array();
    $mainId = $args['regid'];


    // FIXME: check if this is necessary, it shouldn't, we should have checked it earlier
    //     if(in_array($mainId, $installed_ids)) {
    //         xarLogMessage("Already installed $mainId in this request, skipping");
    //         return true;
    //     }
    //     $installed_ids[] = $mainId;

    // Security Check
    // need to specify the module because this function is called by the installer module
    if (!xarSecurityCheck('AdminModules', 1, 'All', 'All', 'modules'))
        return;

    // Argument check
    if (!isset($mainId)) throw new EmptyParameterException('regid');

    // See if we have lost any modules since last generation
    if (!xarModAPIFunc('modules', 'admin', 'checkmissing')) {
        return;
    }

    // Make xarModGetInfo not cache anything...
    //We should make a funcion to handle this or maybe whenever we
    //have a central caching solution...
    $GLOBALS['xarMod_noCacheState'] = true;

    // Get module information
    $modInfo = xarModGetInfo($mainId);
    if (!isset($modInfo)) {
        throw new ModuleNotFoundException($regid,'Module (regid: #(1)) does not exist.');
    }

    switch ($modInfo['state']) {
        case XARMOD_STATE_ACTIVE:
        case XARMOD_STATE_UPGRADED:
            //It is already installed
            return true;
        case XARMOD_STATE_INACTIVE:
            $initialised = true;
            break;
        default:
            $initialised = false;
            break;
    }

    if (!empty($modInfo['extensions'])) {
        foreach ($modInfo['extensions'] as $extension) {
            if (!empty($extension) && !extension_loaded($extension)) {
                throw new ModuleNotFoundException(array($extension,$modInfo['displayname']),
                                                  "Required PHP extension '#(1)' is missing for module '#(2)'");
            }
        }
    }

    $dependency = $modInfo['dependency'];

    if (empty($dependency)) {
        $dependency = array();
    }

    //The dependencies are ok, assuming they shouldnt change in the middle of the
    //script execution.
    foreach ($dependency as $module_id => $conditions) {
        if (is_array($conditions)) {
            //The module id is in $modId
            $modId = $module_id;
        } else {
            //The module id is in $conditions
            $modId = $conditions;
        }

        if (!xarModAPIFunc('modules', 'admin', 'installwithdependencies', array('regid'=>$modId))) {
            $msg = xarML('Unable to initialize dependency module with ID (#(1)).', $modId);
            throw new Exception($msg);
        }
    }

    //Checks if the module is already initialised
    if (!$initialised) {
        // Finally, now that dependencies are dealt with, initialize the module
        if (!xarModAPIFunc('modules', 'admin', 'initialise', array('regid' => $mainId))) {
            $msg = xarML('Unable to initialize module "#(1)".', $modInfo['displayname']);
            throw new Exception($msg);
        }
    }

    // And activate it!
    if (!xarModAPIFunc('modules', 'admin', 'activate', array('regid' => $mainId))) {
        $msg = xarML('Unable to activate module "#(1)".', $modInfo['displayname']);
        throw new Exception($msg);
    }
    
    return true;
}

?>
