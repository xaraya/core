<?php

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

    // Get module information
    $modInfo = xarModGetInfo($args['regid']);
	
    if (empty($modInfo['osdirectory']) || !is_dir('modules/'. $modInfo['osdirectory'])) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST',
                       new SystemException(__FILE__."(".__LINE__."): Module (regid: $args[regid] - directory: $modInfo[osdirectory]) does not exist."));
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

    if (!empty($xarinitfile)) {
        include_once $xarinitfile;

        $func = $modInfo['name'] . '_'.$args['function'];
        if (function_exists($func)) {
            if ($func() != true) {
                return false;
            }
        }
    }

	return true;
}

?>
