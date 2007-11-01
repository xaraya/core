<?php
/**
 * Find all the module's dependencies
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Find all the module's dependencies with all the dependencies of its
 * siblings
 *
 * @author Xaraya Development Team
 * @param $maindId int ID of the module to look dependents for
 * @returns bool
 * @return true on dependencies activated, false for not
 * @throws NO_PERMISSION
 */
function modules_adminapi_getalldependencies($args)
{
    static $checked_ids = array();

    $mainId = $args['regid'];

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

    //Initialize the dependecies array
    $dependency_array = array();
    $dependency_array['unsatisfiable'] = array();
    $dependency_array['satisfiable']   = array();
    $dependency_array['satisfied']     = array();

    if(in_array($mainId,$checked_ids)) {
        xarLogMessage("Already got the dependencies of $mainId, skipping");
        return $dependency_array; // Done that, been there
    }
    $checked_ids[] = $mainId;

    // Get module information
    try {
        $modInfo = xarModGetInfo($mainId);
    } catch (NotFoundExceptions $e) {
        //Add this module to the unsatisfiable list
        $dependency_array['unsatisfiable'][] = $mainId;
        //Return now, we cant find more info about this module
        return $dependency_array;
    }

    if (!empty($modInfo['extensions'])) {
        foreach ($modInfo['extensions'] as $extension) {
            if (!empty($extension) && !extension_loaded($extension)) {
                //Add this extension to the unsatisfiable list
                $dependency_array['unsatisfiable'][] = $extension;
            }
        }
    }

    $dependency = $modInfo['dependency'];

    if (empty($dependency)) {
        $dependency = array();
    }

    //The dependencies are ok, they shouldnt change in the middle of the
    //script execution, so let's assume this.
    foreach ($dependency as $module_id => $conditions) {
        if (is_array($conditions)) {
            //The module id is in $modId
            $modId = $module_id;
        } else {
            //The module id is in $conditions
            $modId = $conditions;
        }

        // RECURSIVE CALL
        $output = xarModAPIFunc('modules', 'admin', 'getalldependencies', array('regid'=>$modId));
        if (!$output) {
            $msg = xarML('Unable to get dependencies for module with ID (#(1)).', $modId);
            throw new Exception($msg);
        }
        //This is giving : recursing detected.... ohh well
//        $dependency_array = array_merge_recursive($dependency_array, $output);

        // FIXME: as the array uses numeric keys, this creates duplicates
        $dependency_array['satisfiable'] = array_merge(
            $dependency_array['satisfiable'],
            $output['satisfiable']);
        $dependency_array['unsatisfiable'] = array_merge(
            $dependency_array['unsatisfiable'],
            $output['unsatisfiable']);
        $dependency_array['satisfied'] = array_merge(
            $dependency_array['satisfied'],
            $output['satisfied']);
    }

    // Unsatisfiable and Satisfiable are assuming the user can't
    //use some hack or something to set the modules as initialized/active
    //without its proper dependencies
    if (count($dependency_array['unsatisfiable'])) {
        //Then this module is unsatisfiable too
        $dependency_array['unsatisfiable'][] = $modInfo;
    } elseif (count($dependency_array['satisfiable'])) {
        //Then this module is satisfiable too
        //As if it were initialized, then all depdencies would have
        //to be already satisfied
        $dependency_array['satisfiable'][] = $modInfo;
    } else {
        //Then this module is at least satisfiable
        //Depends if it is already initialized or not

        //TODO: Add version checks later on
        // Add a new state in the dependency array for version
        // So that we can present that nicely in the gui...

        switch ($modInfo['state']) {
            case XARMOD_STATE_ACTIVE:
            case XARMOD_STATE_UPGRADED:
                //It is satisfied if already initialized
                $dependency_array['satisfied'][] = $modInfo;
            break;
            case XARMOD_STATE_INACTIVE:
            case XARMOD_STATE_UNINITIALISED:
                //If not then it is satisfiable
                $dependency_array['satisfiable'][] = $modInfo;
            break;
            default:
                //If not then it is satisfiable
                $dependency_array['unsatisfiable'][] = $modInfo;
            break;
        }
    }

    return $dependency_array;
}

?>
