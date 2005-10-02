<?php
/**
 * Installs a module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
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
    if (!xarSecConfirmAuthKey()) return;

    if (!xarVarFetch('id', 'int:1:', $id)) return;

    //First check the modules dependencies
    if (!xarModAPIFunc('modules','admin','verifydependency',array('regid'=>$id))) {
        //Oops, we got problems...
        //Handle the exception with a nice GUI:
        xarErrorHandled();

        //Checking if the user has already passed thru the GUI:
        xarVarFetch('command', 'checkbox', $command, false, XARVAR_NOT_REQUIRED);
    } else {
        //No dependencies problems, jump dependency GUI
        $command = true;
    }

    if (!$command) {
        //Let's make a nice GUI to show the user the options
        $data = array();
        $data['id'] = $id;
        //They come in 3 arrays: satisfied, satisfiable and unsatisfiable
        //First 2 have $modInfo under them foreach module,
        //3rd has only 'regid' key with the ID of the module
        $data['authid']       = xarSecGenAuthKey();
        $data['dependencies'] = xarModAPIFunc('modules','admin','getalldependencies',array('regid'=>$id));
        return $data;
    }

    // See if we have lost any modules since last generation
    if (!xarModAPIFunc('modules', 'admin', 'checkmissing')) {
        return;
    }

    $minfo=xarModGetInfo($id);
    //Bail if we've lost our module
    if ($minfo['state'] != XARMOD_STATE_MISSING_FROM_INACTIVE) {
        //Installs with dependencies, first initialise the necessary dependecies
        //then the module itself
        if (!xarModAPIFunc('modules','admin','installwithdependencies',array('regid'=>$id))) {
            // Don't return yet - the stack is rendered here.
            //return;
        }
    }

    // Send the full error stack to the install template for rendering.
    // (The hope is that all errors can be rendered like this eventually)
    if (xarCurrentErrorType()) {
        // Get the error stack
        $errorstack = xarErrorget();
        // Free up the error stack since we are handling it locally.
        xarErrorFree();
        // Return the stack for rendering.
        return array('errorstack' => $errorstack);
    }

    // set the target location (anchor) to go to within the page
    $target = $minfo['name'];
    
    if (function_exists('xarOutputFlushCached')) {
        xarOutputFlushCached('adminpanels');
        xarOutputFlushCached('base-block');
    }

    xarResponseRedirect(xarModURL('modules', 'admin', 'list', array('state' => 0), NULL, $target));

    return true;
}

?>