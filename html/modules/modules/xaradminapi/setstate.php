<?php

/**
 * Set the state of a module
 *
 * @param $args['regid'] the module id
 * @param $args['state'] the state
 * @raise BAD_PARAM,NO_PERMISSION
 */
function modules_adminapi_setstate($args)
{
    // Get arguments from argument array

    extract($args);

    // Argument check
    if ((!isset($regid)) ||
        (!isset($state))) {
        $msg = xarML('Empty regid (#(1)) or state (#(2)).', $regid, $state);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    // Security Check
    if(!xarSecurityCheck('AdminModules')) return;

    // Clear cache to make sure we get newest values
    if (xarVarIsCached('Mod.Infos', $regid)) {
        xarVarDelCached('Mod.Infos', $regid);
    }

    //Get module info
    $modInfo = xarModGetInfo($regid);

    //Set up database object
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    $oldState = $modInfo['state'];
    // Check valid state transition
    switch ($state) {
        case XARMOD_STATE_UNINITIALISED:

            if ($oldState != XARMOD_STATE_INACTIVE) {
                // New Module
                $module_statesTable = $xartable['system/module_states'];
                $query = "SELECT * FROM $module_statesTable WHERE xar_regid = $regid";
                $result =& $dbconn->Execute($query);
                if (!$result) return;
                if ($result->EOF) {
                    $query = "INSERT INTO $module_statesTable
                       (xar_regid,
                        xar_state)
                        VALUES
                        ('" . xarVarPrepForStore($regid) . "',
                         '" . xarVarPrepForStore($state) . "')";

                    $result =& $dbconn->Execute($query);
                    if (!$result) return;
                }
                return true;
            }

            break;
        case XARMOD_STATE_INACTIVE:
            break;
        case XARMOD_STATE_ACTIVE:
            if (($oldState == XARMOD_STATE_UNINITIALISED) ||
                ($oldState == XARMOD_STATE_MISSING) ||
                ($oldState == XARMOD_STATE_UPGRADED)) {
                xarSessionSetVar('errormsg', xarML('Invalid module state transition'));
                return false;
            }
            break;
        case XARMOD_STATE_MISSING:
            break;
        case XARMOD_STATE_UPGRADED:
            if ($oldState == XARMOD_STATE_UNINITIALISED) {
                xarSessionSetVar('errormsg', xarML('Invalid module state transition'));
                return false;
            }
            break;
    }

    //Get current module mode to update the proper table
    $modMode  = $modInfo['mode'];

    if ($modMode == XARMOD_MODE_SHARED) {
        $module_statesTable = $xartable['system/module_states'];
    } elseif ($modMode == XARMOD_MODE_PER_SITE) {
        $module_statesTable = $xartable['site/module_states'];
    }

    $query = "UPDATE $module_statesTable
              SET xar_state = " . xarVarPrepForStore($state) . "
              WHERE xar_regid = " . xarVarPrepForStore($regid);
    $result =& $dbconn->Execute($query);

    if (!$result) {return;}

    // We're update module state here we must update at least
    // the base info in the cache.
    $modInfo['state']=$state;
    xarVarSetCached('Mod.Infos',$regid,$modInfo);
    //xarVarSetCached('Mod.BaseInfos',$modInfo['name'],$modInfo);
    return true;
}

?>
