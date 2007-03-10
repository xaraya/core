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
 * @todo Do the db changes in a transaction to completely fail or succeed?
 */
function modules_adminapi_setstate($args)
{
    // Get arguments from argument array

    extract($args);

    // Argument check
    if (!isset($regid)) throw new EmptyParameterException('regid');
    if (!isset($state)) throw new EmptyParameterException('state');

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

    // Check valid state transition
    switch ($state) {
        case XARMOD_STATE_UNINITIALISED:
            // So, we're basically good all the time here?
            if (($oldState == XARMOD_STATE_MISSING_FROM_UNINITIALISED) ||
                ($oldState == XARMOD_STATE_ERROR_UNINITIALISED)) break;

            if ($oldState != XARMOD_STATE_INACTIVE) {
                // New Module
                break;
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
                throw new Exception("Setting from $oldState to $state for module $regid failed");
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
    $modulesTable = $xartable['modules'];
    $query = "UPDATE $modulesTable SET state = ? WHERE regid = ?";
    $bindvars = array($state,$regid);
    $dbconn->Execute($query,$bindvars);

    // We're update module state here we must update at least
    // the base info in the cache.
    $modInfo['state']=$state;
    xarVarSetCached('Mod.Infos',$regid,$modInfo);
    xarVarSetCached('Mod.BaseInfos',$modInfo['name'],$modInfo);

    return $state;
}

?>
