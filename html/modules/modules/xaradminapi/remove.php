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

    // Security Check
	if(!xarSecurityCheck('AdminModules')) return;

    // Remove variables and module
    list($dbconn) = xarDBGetConn();
    $tables = xarDBGetTables();

    // Get module information
    $modinfo = xarModGetInfo($regid);

    //TODO: Add check if there is any dependents

    // Module deletion function
	if (!xarModAPIFunc('modules',
	                   'admin',
					   'executeinitfunction',
	                   array('regid'    => $regid,
                             'function' => 'delete'))) {
		//Raise an Exception
		return;
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

    // Update state of module
    $res = xarModAPIFunc('modules',
                        'admin',
                        'setstate',
                        array('regid' => $regid,
                              'state' => XARMOD_STATE_UNINITIALISED));

    return true;
}

?>
