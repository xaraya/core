<?php
/**
 * Set the state of a module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Set the state of a module
 *
 * @author Xaraya Development Team
 * @param $args['regid'] the module id
 * @param $args['state'] the state
 * @throws BAD_PARAM,NO_PERMISSION
 */
function modules_adminapi_setstate($args)
{
    // Get arguments from argument array

    extract($args);

    // Argument check
    if ((!isset($regid)) ||
        (!isset($state))) {
        $msg = xarML('Empty regid (#(1)) or state (#(2)).', $regid, $state);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
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
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $oldState = $modInfo['state'];
//    echo $oldState.$state;exit;
    // Check valid state transition
    switch ($state) {
        case XARMOD_STATE_UNINITIALISED:

            if (($oldState == XARMOD_STATE_MISSING_FROM_UNINITIALISED) ||
                ($oldState == XARMOD_STATE_ERROR_UNINITIALISED)) break;

            if ($oldState != XARMOD_STATE_INACTIVE) {
                // New Module
                $module_statesTable = $xartable['system/module_states'];
                $query = "SELECT * FROM $module_statesTable WHERE xar_regid = ?";
                $result =& $dbconn->Execute($query,array($regid));
                if (!$result) return;
                if ($result->EOF) {
                    // Bug #1813 - Have to use GenId to get or create the sequence 
                    // for xar_id or the sequence for xar_id will not be available
                    // in PostgreSQL
                    $seqId = $dbconn->GenId($module_statesTable);

                    $query = "INSERT INTO $module_statesTable
                                (xar_id, xar_regid, xar_state)
                        VALUES  (?,?,?)";
                    $bindvars = array($seqId,$regid,$state);

                    $result =& $dbconn->Execute($query,$bindvars);
                    if (!$result) return;
                }
                return true;
            }

            break;
        case XARMOD_STATE_INACTIVE:
            if (($oldState != XARMOD_STATE_UNINITIALISED) &&
                ($oldState != XARMOD_STATE_ACTIVE) &&
                ($oldState != XARMOD_STATE_MISSING_FROM_INACTIVE) &&
                ($oldState != XARMOD_STATE_ERROR_INACTIVE) &&
                ($oldState != XARMOD_STATE_UPGRADED)) {
                xarSessionSetVar('errormsg', xarML('Invalid module state transition'));
                return false;
            }
            break;
        case XARMOD_STATE_ACTIVE:
            if (($oldState != XARMOD_STATE_INACTIVE) &&
                ($oldState != XARMOD_STATE_ERROR_ACTIVE) &&
                ($oldState != XARMOD_STATE_MISSING_FROM_ACTIVE)) {
                xarSessionSetVar('errormsg', xarML('Invalid module state transition'));
                return false;
            }
            break;
        case XARMOD_STATE_UPGRADED:
            if (($oldState != XARMOD_STATE_INACTIVE) &&
                ($oldState != XARMOD_STATE_ACTIVE) &&
                ($oldState != XARMOD_STATE_ERROR_UPGRADED) &&
                ($oldState != XARMOD_STATE_MISSING_FROM_UPGRADED)) {
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
              SET xar_state = ? WHERE xar_regid = ?";
    $bindvars = array($state,$regid);
    $result =& $dbconn->Execute($query,$bindvars);
    if (!$result) {return;}
    // We're update module state here we must update at least
    // the base info in the cache.
    $modInfo['state']=$state;
    xarVarSetCached('Mod.Infos',$regid,$modInfo);
    //xarVarSetCached('Mod.BaseInfos',$modInfo['name'],$modInfo);

    return true;
}

?>