<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Remove module and its dependents
 * To be used after the user assured he wants to unitialize the module
 * and all its dependents (should show a list of them to the user)
 *
 * @author Xaraya Development Team
 * @param $maindId int ID of the module to look dependents for
 * @returns array
 * @return array with dependents
 * @raise NO_PERMISSION
 */
function modules_adminapi_removewithdependents ($args)
{
    xarLogMessage('Removing with dependents');
    $mainId = $args['regid'];

    // Security Check
    // need to specify the module because this function is called by the installer module
    if (!xarSecurityCheck('AdminModules', 1, 'All', 'All', 'modules'))
        return;

    // Argument check
    if (!isset($mainId)) {
        $msg = xarML('Missing module regid (#(1)).', $mainId);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    // See if we have lost any modules since last generation
    if (!xarModAPIFunc('modules', 'admin', 'checkmissing')) {
        xarLogMessage('Missing module since last generation');
        return;
    }

    //Get the dependents list
    $dependents = xarModAPIFunc('modules','admin','getalldependents',array('regid'=>$mainId));
    xarLogVariable('dependents',$dependents);

    //Deactivate Actives
    foreach ($dependents['active'] as $active_dependent) {
        if (!xarModAPIFunc('modules', 'admin', 'deactivate', array('regid' => $active_dependent['regid']))) {
            $msg = xarML('Unable to deactivate module "#(1)".', $active_dependent['displayname']);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', $msg);
            return;
        }
    }
    
    //Remove the previously active
    foreach ($dependents['active'] as $active_dependent) {
        if (!xarModAPIFunc('modules', 'admin', 'remove', array('regid' => $active_dependent['regid']))) {
            $msg = xarML('Unable to remove module "#(1)".', $active_dependent['displayname']);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', $msg);
            return;
        }
    }
    
    //Remove the initialised
    foreach ($dependents['initialised'] as $active_dependent) {
        if (!xarModAPIFunc('modules', 'admin', 'remove', array('regid' => $active_dependent['regid']))) {
            $msg = xarML('Unable to remove module "#(1)".', $active_dependent['displayname']);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', $msg);
            return;
        }
    }
    
    return true;
}

?>
