<?php
/**
 * File: $Id$
 *
 * Remove a module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage modules module
 * @author Xaraya Team 
 */
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
/*
    if (!xarModAPIFunc('modules','admin','verifydependents',array('regid'=>$regid))) {
        //TODO: Add description of the dependencies
        $msg = xarML('There are dependents to the module "#(1)" that weren\'t removed yet.', $modInfo['displayname']);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MODULE_DEPENDENCY', $msg);
        return;
    }
*/
    // Delete any module variables that the module cleanup function might
    // have missed.
    // This needs to be done before the module ntry is removed.
    xarModDelAllVars($modinfo['name']);

    // If the files have been removed, the module will now also be removed from the db
    if ($modinfo['state'] == XARMOD_STATE_MISSING_FROM_UNINITIALISED ||
        $modinfo['state'] == XARMOD_STATE_MISSING_FROM_INACTIVE ||
        $modinfo['state'] == XARMOD_STATE_MISSING_FROM_ACTIVE ||
        $modinfo['state'] == XARMOD_STATE_MISSING_FROM_UPGRADED ) {
        $query = "DELETE FROM " . $tables['modules'] .
                  " WHERE xar_regid = " . xarVarPrepForStore($modinfo['regid']);
        $result =& $dbconn->Execute($query);
        if (!$result) return;
        $query = "DELETE FROM " . $tables['system/module_states'] .
                  " WHERE xar_regid = " . xarVarPrepForStore($modinfo['regid']);
        $result =& $dbconn->Execute($query);
        if (!$result) return;
    }
    else {
        // Module deletion function
        if (!xarModAPIFunc('modules',
                           'admin',
                           'executeinitfunction',
                           array('regid'    => $regid,
                                 'function' => 'delete'))) {
            //Raise an Exception
            return;
        }

        // Update state of module
        $res = xarModAPIFunc('modules',
                            'admin',
                            'setstate',
                             array('regid' => $regid,
                                  'state' => XARMOD_STATE_UNINITIALISED));
    }

    // Call any 'category' delete hooks assigned for that module
    // (notice we're using the module name as object id, and adding an
    // extra parameter telling xarModCallHooks for *which* module we're
    // calling hooks here)
    xarModCallHooks('module','remove',$modinfo['name'],'',$modinfo['name']);

    // Delete any hooks assigned for that module, or by that module
    $query = "DELETE FROM $tables[hooks]
              WHERE xar_smodule = '" . xarVarPrepForStore($modinfo['name']) . "'
                 OR xar_tmodule = '" . xarVarPrepForStore($modinfo['name']) . "'";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Collect the block types and remove them
    $query = "SELECT xar_id
              FROM $tables[block_types]
              WHERE xar_module = '" . xarVarPrepForStore($modinfo['name']) . "'";
    $result =& $dbconn->Execute($query);
    if (!$result) return;
    while (!$result->EOF) {
        list($typeid) = $result->fields;
        $query = "DELETE FROM $tables[block_instances]
                  WHERE xar_type_id = " . $typeid;
        $result1 =& $dbconn->Execute($query);
        if (!$result1) return;
        $result->MoveNext();
    }
    $query = "DELETE FROM $tables[block_types]
              WHERE xar_module = '" . xarVarPrepForStore($modinfo['name']) . "'";
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    return true;
}

