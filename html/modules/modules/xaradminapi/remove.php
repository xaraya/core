<?php

/**
 * Remove a module
 *
 * @param $args['regid'] the id of the module
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION
 */
function modules_adminapi_remove($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regid)) {
        $msg = xarML('Empty regid (#(1)).', $regid);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    // Security Check
	if(!xarSecurityCheck('AdminModules')) return;

    // Remove variables and module
    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    // Get module information
    $modinfo = xarModGetInfo($regid);
    if (empty($modinfo)) {
        xarSessionSetVar('errormsg', xarML('No such module'));
        return false;
    }

    // Get module database info
    xarModDBInfoLoad($modinfo['name'], $modinfo['directory']);

    // Module deletion function
    //FIXME: add module file not exist exception?

    $xarinitfilename = 'modules/'. $modinfo['osdirectory'] .'/xarinit.php';
    // pnAPI compatibility
    if (!file_exists($xarinitfilename)) {
        $xarinitfilename = 'modules/'. $modinfo['osdirectory'] .'/pninit.php';
    }

        // FIXME: we shouldn't rely on @ signs in the code, do proper checking.
    @include $xarinitfilename;

    $func = $modinfo['name'] . '_delete';
    if (function_exists($func)) {
        if ($func() != true) {
            xarSessionSetVar('errormsg',xarML('Unable to remove module, function returned false'));
            return false;
        }
    }

    // Delete any module variables that the module cleanup function might
    // have missed
    xarModDelAllVars($modinfo['name']);

    // TODO: do the same for create hooks somewhere (on initialise ?)

    // Call any 'category' delete hooks assigned for that module
    // (notice we're using the module name as object id, and adding an
    // extra parameter telling xarModCallHooks for *which* module we're
    // calling hooks here)
    xarModCallHooks('module','remove',$modinfo['name'],'',$modinfo['name']);

    // Delete any hooks assigned for that module, or by that module
    $sql = "DELETE FROM $tables[hooks]
              WHERE xar_smodule = '" . xarVarPrepForStore($modinfo['name']) . "'
                 OR xar_tmodule = '" . xarVarPrepForStore($modinfo['name']) . "'";
    $result =& $dbconn->Execute($sql);
    if (!$result) return;

    // Delete the module from the modules table
    $sql = "DELETE FROM $tables[modules]
              WHERE xar_regid = " . xarVarPrepForStore($regid);
    $result =& $dbconn->Execute($sql);
    if (!$result) return;


    // Delete the module state from the module states table
/*
    //Get current module mode to update the proper table
    $modMode  = $modinfo['mode'];

    if ($modMode == XARMOD_MODE_SHARED) {
        $module_statesTable = $tables['system/module_states'];
    } elseif ($modMode == XARMOD_MODE_PER_SITE) {
        $module_statesTable = $tables['site/module_states'];
    }
*/
// TODO: what happens if a module state is still there in one of the subsites ?
    $module_statesTable = $tables['site/module_states'];

    $sql = "DELETE FROM $module_statesTable
            WHERE xar_regid = " . xarVarPrepForStore($regid);
    $result =& $dbconn->Execute($sql);
    if (!$result) return;

    return true;
}

?>