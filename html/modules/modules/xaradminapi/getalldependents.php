<?php
/**
 * Find all the modules dependents recursively
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * Find all the modules dependents recursively
 *
 * @author Xaraya Development Team
 * @param $maindId int ID of the module to look dependents for
 * @return array
 * @throws NO_PERMISSION
 */
function modules_adminapi_getalldependents ($args)
{
    static $dependent_ids = array();

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

    //Initialize the dependencies array
    $dependents_array                  = array();
    $dependents_array['active']        = array();
    $dependents_array['initialised']   = array();


    // If we have already got the same id in the same request, dont do it again.
    if(in_array($mainId, $dependent_ids)) {
        xarLogMessage("We already checked $mainId, not doing it a second time");
        return $dependents_array;
    }
    $dependent_ids[] = $mainId;

    //Get all modules in the filesystem
    $fileModules = xarModAPIFunc('modules','admin','getfilemodules');
    if (!isset($fileModules)) return;

    // Get all modules in DB
    $dbModules = xarModAPIFunc('modules','admin','getdbmodules');
    if (!isset($dbModules)) return;

    foreach ($fileModules as $name => $modinfo) {

        // If the module is not in the database, than its not initialised or activated
        if (!isset($dbModules[$name])) continue;

        // If the module is not INITIALISED dont bother...
        // Later on better have a full range of possibilities (adding missing and
        // unitialised). For that a good cleanup in the constant logic and
        // adding a proper array of module states would be nice...
        if ($dbModules[$name]['state'] == XARMOD_STATE_UNINITIALISED) continue;

        if (isset($modinfo['dependency']) &&
            !empty($modinfo['dependency'])) {
            $dependency = $modinfo['dependency'];
        } else {
            $dependency = array();
        }

        foreach ($dependency as $module_id => $conditions) {
            if (is_array($conditions)) {
                //The module id is in $modId
                $modId = $module_id;
            } else {
                //The module id is in $conditions
                $modId = $conditions;
            }

            //Not dependent, then go to the next dependency!!!
            if ($modId != $mainId) continue;

            //If we are here, then it is dependent
            // RECURSIVE CALL
            $output = xarModAPIFunc('modules', 'admin', 'getalldependents', array('regid' => $modinfo['regid']));
            if (!$output) {
                $msg = xarML('Unable to get dependencies for module with ID (#(1)).', $modinfo['regid']);
                throw new Exception($msg);
            }

            //This is giving : recursing detected.... ohh well
            //$dependency_array = array_merge_recursive($dependency_array, $output);

            $dependents_array['active'] = array_merge(
                $dependents_array['active'],
                $output['active']);
            $dependents_array['initialised'] = array_merge(
                $dependents_array['initialised'],
                $output['initialised']);
        }
    }

    // Get module information
    $modInfo = xarModGetInfo($mainId);

    //TODO: Add version checks later on
    switch ($modInfo['state']) {
        case XARMOD_STATE_ACTIVE:
        case XARMOD_STATE_UPGRADED:
            //It is satisfied if already initialized
            $dependents_array['active'][] = $modInfo;
        break;
        case XARMOD_STATE_INACTIVE:
        default:
            //If not then it is satisfiable
            $dependents_array['initialised'][] = $modInfo;
        break;
    }

    return $dependents_array;
}

?>
