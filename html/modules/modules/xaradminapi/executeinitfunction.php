<?php
/**
 * File: $Id$
 *
 * Loads xarinit or pninit and executes the given function
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage modules module
 * @author Xaraya Team
 */
/**
 * Loads xarinit or pninit and executes the given function
 *
 * @param $args['regid'] the id of the module
 * @param $args['function'] name of the function to be called
 * @returns bool
 * @return true on success, false on failure in the called function
 * @raise BAD_PARAM, NO_PERMISSION
 */
function modules_adminapi_executeinitfunction ($args)
{
    // Security Check
    if(!xarSecurityCheck('AdminModules')) return;

    // Argument check
    if (!isset($args['regid'])) {
        $msg = xarML('Missing module regid.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', $msg);
        return;
    }

    // Get module information
    $modInfo = xarModGetInfo($args['regid']);

    if (!isset($modInfo['osdirectory']) ||
        empty($modInfo['osdirectory']) ||
        !is_dir('modules/'. $modInfo['osdirectory'])) {

        $msg = xarML('Module (regid: #(1) - directory: #(2) does not exist.', $args['regid'], $modInfo['osdirectory']);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST',  $msg);
        return;
    }

    // Get module database info, they might be needed in the function to be called
    xarMod__loadDbInfo($modInfo['name'], $modInfo['osdirectory']);

    // pnAPI compatibility
    $xarinitfile = '';
    if (file_exists('modules/'. $modInfo['osdirectory'] .'/xarinit.php')) {
        $xarinitfile = 'modules/'. $modInfo['osdirectory'] .'/xarinit.php';
    } elseif (file_exists('modules/'. $modInfo['osdirectory'] .'/pninit.php')) {
        $xarinitfile = 'modules/'. $modInfo['osdirectory'] .'/pninit.php';
    }

    if (empty($xarinitfile)) {
        /*
        $msg = xarML('No Initialization File Found for Module "#(1)"', $modInfo['name']);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST', $msg);
        return;
        */
        //Return gracefully, the metaweblogapi doesnt have the init file..
        //Should it be obligatory? The same can be asked about each individual process function
        // (init/activate/deactivate/remobve)
        return true;
    }

    // if (!empty($xarinitfile)) {
    ob_start();
    $r = include_once($xarinitfile);
    $error_msg = strip_tags(ob_get_contents());
    ob_end_clean();

    if (empty($r) || !$r) {
        $msg = xarML("Could not load file: [#(1)].\n\n Error Caught:\n #(2)", $xarinitfile, $error_msg);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST', $msg);
        return;
    }

    $func = $modInfo['name'] . '_'.$args['function'];
    if (function_exists($func)) {
        if ($args['function'] == 'upgrade') {
            // pass the old version as argument to the upgrade function
            $result = $func($modInfo['version']);
        } else {
            $result = $func();
        }

        // If an exception was set, then return
        if (xarCurrentErrorType() != XAR_NO_EXCEPTION) return;

        if ($result === false) {
            $msg = xarML('While changing state of the #(1) module, the function #(2) returned a false value when executed.', $modInfo['name'], $func);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', $msg);
            return;
        } elseif ($result != true) {
            $msg = xarML('An error ocurred while changing state of the #(1) module, executing function #(2)', $modInfo['name'], $func);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'FUNCTION_FAILED', $msg);
            return;
        }
    } else {
        // A lot of init files dont have the function, maily activate...
        // Should we enforce them to have it?
        /*
        // file exists, but function not found. Exception!
        $msg = xarML('Module change of state failed because your module did not include an #(1) function: #(2)', $args['function'], $func);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FUNCTION_NOT_EXIST', $msg);
        return;
        */
    }
    //}

    return true;
}

?>
