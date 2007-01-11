<?php
/**
 * Installs a module
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
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
 * @returns
 * @return
 */
function modules_admin_install()
{
    // Security and sanity checks
    // TODO: check under what conditions this is needed
//    if (!xarSecConfirmAuthKey()) return;

    if (!xarVarFetch('id', 'int:1:', $id)) return;

    //First check the modules dependencies
    // TODO: investigate try/catch clause here, it's not trivial
    try {
        xarModAPIFunc('modules','admin','verifydependency',array('regid'=>$id));

        //Checking if the user has already passed thru the GUI:
        xarVarFetch('command', 'checkbox', $command, false, XARVAR_NOT_REQUIRED);
    } catch (ModuleNotFoundException $e) {
        $command = false;
    }

    if (!$command) {
        //Let's make a nice GUI to show the user the options
        $data = array();
        $data['id'] = $id;
        //They come in 3 arrays: satisfied, satisfiable and unsatisfiable
        //First 2 have $modInfo under them foreach module,
        //3rd has only 'regid' key with the ID of the module

        // get any dependency info on this module for a better message if something is missing
            $thisinfo = xarModGetInfo($id);
            $data['displayname'] = $thisinfo['displayname'];
        if (!empty($thisinfo['dependencyinfo'])) {
            $data['dependencyinfo'] = $thisinfo['dependencyinfo'];
        } elseif (!empty($thisinfo['dependency'])) {
            $data['dependencyinfo'] = $thisinfo['dependency'];
        } else {
            $data['dependencyinfo'] = array();
        }

        $data['authid']       = xarSecGenAuthKey();
        $data['dependencies'] = xarModAPIFunc('modules','admin','getalldependencies',array('regid'=>$id));
        return $data;
    }

    // See if we have lost any modules since last generation
    if (!xarModAPIFunc('modules', 'admin', 'checkmissing')) {
        return;
    }

    xarSessionSetVar('installing',true);
    $minfo=xarModGetInfo($id);
    //Bail if we've lost our module
    if ($minfo['state'] != XARMOD_STATE_MISSING_FROM_INACTIVE) {
        //Installs with dependencies, first initialise the necessary dependencies
        //then the module itself
        xarSessionSetVar('modulestoinstall',serialize(array()));
        if (!xarModAPIFunc('modules','admin','installwithdependencies',array('regid'=>$id, 'phase' => 0))) {
            // Don't return yet - the stack is rendered here.
            //return;
        }
    }
    xarSessionDelVar('installing');

    // set the target location (anchor) to go to within the page
    $target = $minfo['name'];

    if (function_exists('xarOutputFlushCached')) {
        xarOutputFlushCached('base');
        xarOutputFlushCached('modules');
        xarOutputFlushCached('base-block');
    }

    // The module might have properties, after installing, flush the property cache otherwise you will
    // get errors on displaying the property.
    if(!xarModAPIFunc('dynamicdata','admin','importpropertytypes', array('flush' => true))) {
        return false; //FIXME: Do we want an exception here if flushing fails?
    }
    xarResponseRedirect(xarModURL('modules', 'admin', 'list', array('state' => 0), NULL, $target));

    return true;
}

?>
