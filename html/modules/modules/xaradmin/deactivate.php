<?php
/**
 * File: $Id$
 *
 * Deactivate a module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage modules module
 * @author Xaraya Team 
 */
/**
 * Deactivate a module
 *
 * Loads module admin API and calls the setstate
 * function to actually perfrom the deactivation,
 * then redirects to the list function with a status
 * message and returns true.
 *
 * @access public
 * @param id the mdoule id to deactivate
 * @returns
 * @return
 */
function modules_admin_deactivate ()
{
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) return;

    if (!xarVarFetch('id', 'int:1:', $id)) return;


    //First check the modules dependencies
    $dependents = xarModAPIFunc('modules','admin','getalldependents',array('regid'=>$id));
    if (count($dependents['active']) > 1) {
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
        //They come in 2 arrays: active, initialised
        //Both have $name => $modInfo under them foreach
        $data['authid']       = xarSecGenAuthKey();
        $data['dependencies'] = $dependents;
        return $data;
    }

    // See if we have lost any modules since last generation
    if (!xarModAPIFunc('modules', 'admin', 'checkmissing')) {
        return;
    }

    $minfo=xarModGetInfo($id);
    //Bail if we've lost our module
    if ($minfo['state'] != XARMOD_STATE_MISSING_FROM_ACTIVE) {
        //Deactivate with dependents, first dependents
        //then the module itself
        if (!xarModAPIFunc('modules','admin','deactivatewithdependents',array('regid'=>$id))) {
            //Call exception
            return;
        } // Else
    }

    // set the target location (anchor) to go to within the page
    $target=$minfo['name'];

    // Hmmm, I wonder if the target adding is considered a hack
    // it certainly depends on the implementation of xarModUrl
    //    xarResponseRedirect(xarModURL('modules', 'admin', "list#$target"));
    xarResponseRedirect(xarModURL('modules', 'admin', 'list', array('state' => 0), NULL, $target));

    return true;
}

?>
