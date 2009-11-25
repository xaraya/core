<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Remove module and its dependents
 * To be used after the user assured he wants to initialise the module
 * and all its dependents (should show a list of them to the user)
 *
 * @author Xaraya Development Team
 * @param $maindId int ID of the module to look dependents for
 * @returns array
 * @return array with dependents
 * @throws NO_PERMISSION
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
    if (!isset($mainId)) throw new EmptyParameterException('regid');

    // See if we have lost any modules since last generation
    sys::import('modules.modules.class.installer');
    $installer = Installer::getInstance();    
    if (!$installer->checkformissing()) {
        xarLogMessage('Missing module since last generation');
        return;
    }

    //Get the dependents list
    $dependents = $installer->getalldependents($mainId);
    xarLogVariable('dependents',$dependents);

    //Deactivate Actives
    foreach ($dependents['active'] as $active_dependent) {
        if (!xarMod::apiFunc('modules', 'admin', 'deactivate', array('regid' => $active_dependent['regid']))) {
            throw new BadParameterException($active_dependent['displayname'],'Unable to deactivate module "#(1)".');
        }
    }
    
    //Remove the previously active
    foreach ($dependents['active'] as $active_dependent) {
        if (!xarMod::apiFunc('modules', 'admin', 'remove', array('regid' => $active_dependent['regid']))) {
            throw new BadParameterException($active_dependent['displayname'], 'Unable to remove module "#(1)".');
        }
    }
    
    //Remove the initialised
    foreach ($dependents['initialised'] as $active_dependent) {
        if (!xarMod::apiFunc('modules', 'admin', 'remove', array('regid' => $active_dependent['regid']))) {
            throw new BadParameterException($active_dependent['displayname'], 'Unable to remove module "#(1)".');
        }
    }
    
    return true;
}

?>