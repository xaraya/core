<?php

/**
 * Initialise a module
 *
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
function modules_admin_initialise()
{
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) return;

    if (!xarVarFetch('id', 'int:1:', $id)) return; 

	//First check the modules dependencies
    if (!xarModAPIFunc('modules','admin','verifydependency',array('regid'=>$id))) {
    	//Oops, we got problems...
    	//Checking if we have already sent a GUI to the user:
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
   	
   	//Initialize with dependencies, first initialise the necessary dependecies
   	//then the module itself
	if (!xarModAPIFunc('modules','admin','initialisewithdependencies',array('regid'=>$id))) {
		//Call exception
		return;	
	} // Else

    $minfo = xarModGetInfo($id);
    // set the target location (anchor) to go to within the page
    $target = $minfo['name'];
    
    // attempt to activate
    $activated = xarModAPIFunc('modules',
                              'admin',
                              'setstate',
                              array('regid' => $id,
                                    'state' => XARMOD_STATE_ACTIVE));
    if (!isset($activated)){                             
        // something gone wrong with normal activation
        xarResponseRedirect(xarModURL('modules', 'admin', "list", array('state' => 0), NULL, $target));
    } else {
        // done with complete install cycle i.e. init+activate in a single step
        xarResponseRedirect(xarModURL('modules', 'admin', 'list', array('state' => 0), NULL, $target));
    }    

    return true;
}

?>