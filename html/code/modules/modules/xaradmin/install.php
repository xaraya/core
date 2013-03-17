<?php
/**
 * Installs a module
 *
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * Installs a module
 *
 * @author Xaraya Development Team
 * Loads module admin API and calls the initialise
 * function to actually perform the initialisation,
 * then redirects to the list function with a
 * status message and returns true.
 * <andyv implementation of JC's request> attempt to activate module immediately after it's inited
 *
 * @param id the module id to initialise
 * @return boolean true on success, false on failure
 */
sys::import('modules.modules.class.installer');

function modules_admin_install()
{
    // Security
    if (!xarSecurityCheck('AdminModules')) return; 
    
    $installer = Installer::getInstance();    
    // Security and sanity checks
    // TODO: check under what conditions this is needed
//    if (!xarSecConfirmAuthKey()) return;

    if (!xarVarFetch('id', 'int:1:', $id, 0, XARVAR_NOT_REQUIRED)) return;
    if (empty($id)) return xarResponse::notFound();
    if (!xarVarFetch('return_url', 'pre:trim:str:1:',
        $return_url, '', XARVAR_NOT_REQUIRED)) return;

    // First check for a proper core version
    if (!$installer->checkCore($id)) 
        return xarTpl::module('modules','user','errors',array('layout' => 'invalid_core', 'modname' => xarMod::getName($id)));

    //Next check the modules dependencies
    // TODO: investigate try/catch clause here, it's not trivial
    try {
        $installer->verifydependency($id);

        //Checking if the user has already passed thru the GUI:
        xarVarFetch('command', 'checkbox', $command, false, XARVAR_NOT_REQUIRED);
    } catch (ModuleNotFoundException $e) {
        $command = false;
    }

    $data['propertydependencies'] = $installer->getpropdependencies($id);
    $data['moduledependencies'] = $installer->getalldependencies($id);

    //Only show the status screen if there are dependencies that cannot be satisfied
    if (!$command && (!empty($data['moduledependencies']['unsatisfiable']) || !empty($data['propertydependencies']['unsatisfiable']))) {
        //Let's make a nice GUI to show the user the options
        $data['id'] = $id;
        //They come in 3 arrays: satisfied, satisfiable and unsatisfiable
        //First 2 have $modInfo under them for each module,
        //3rd has only 'regid' key with the ID of the module

        // get any dependency info on this module for a better message if something is missing
            $thisinfo = xarMod::getInfo($id);
            $data['displayname'] = $thisinfo['displayname'];
        if (!empty($thisinfo['dependencyinfo'])) {
            $data['dependencyinfo'] = $thisinfo['dependencyinfo'];
        } elseif (!empty($thisinfo['dependency'])) {
            $data['dependencyinfo'] = $thisinfo['dependency'];
        } else {
            $data['dependencyinfo'] = array();
        }

        $data['authid']       = xarSecGenAuthKey();
        $data['return_url'] = $return_url;
        return $data;
    }

    // See if we have lost any modules since last generation
    if (!$installer->checkformissing()) {return;}

    xarSession::setVar('installing',true);

    $minfo = xarMod::getInfo($id);

    //Bail if we've lost our module
    if ($minfo['state'] != XARMOD_STATE_MISSING_FROM_INACTIVE) {
        //Installs with dependencies, first initialise the necessary dependencies
        //then the module itself
        $installer->installmodule($id);
    }
    // Note: if the module installed successfully, the above method will have already redirected,
    // and thus the following won't be executed 
    xarSessionDelVar('installing');

    // set the target location (anchor) to go to within the page
    $target = $minfo['name'];

    if (function_exists('xarOutputFlushCached')) {
        xarOutputFlushCached('base');
        xarOutputFlushCached('modules');
        xarOutputFlushCached('base-block');
    }

    if (empty($return_url))
        $return_url = xarModURL('modules', 'admin', 'list', array('state' => 0), NULL, $target);

    xarController::redirect($return_url);
    return true;
}

?>
