<?php

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
		//They come in 3 arrays: satisfied, satisfiable and unsatisfiable
		//First 2 have $modInfo under them foreach module,
		//3rd has only 'regid' key with the ID of the module
	    $data['authid']       = xarSecGenAuthKey();
   		$data['dependencies'] = $dependents;
   		return $data;
   	}
   	
   	//Installs with dependencies, first initialise the necessary dependecies
   	//then the module itself
	if (!xarModAPIFunc('modules','admin','deactivatewithdependents',array('regid'=>$id))) {
		//Call exception
		return;	
	} // Else


    $minfo=xarModGetInfo($id);

    // set the target location (anchor) to go to within the page 
    $target=$minfo['name'];

    // Hmmm, I wonder if the target adding is considered a hack
    // it certainly depends on the implementation of xarModUrl
    //    xarResponseRedirect(xarModURL('modules', 'admin', "list#$target"));
    xarResponseRedirect(xarModURL('modules', 'admin', 'list', array('state' => 0), NULL, $target));

    return true;
}

?>
