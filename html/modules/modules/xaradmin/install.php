<?php

/**
 * Installs a module
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
function modules_admin_install()
{
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) return;

    if (!xarVarFetch('id', 'int:1:', $id)) return; 

	//First check the modules dependencies
    if (!xarModAPIFunc('modules','admin','verifydependency',array('regid'=>$id))) {
    	//Oops, we got problems...
		//Handle the exception with a nice GUI:
		xarExceptionHandled();

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
   	
   	//Installs with dependencies, first initialise the necessary dependecies
   	//then the module itself
	if (!xarModAPIFunc('modules','admin','installwithdependencies',array('regid'=>$id))) {
		//Call exception
		return;	
	} // Else

    // Bug 1222: give exceptions raised during the install a chance to be displayed.
    if (xarExceptionMajor()) {
        return;
    }

    $minfo = xarModGetInfo($id);
    // set the target location (anchor) to go to within the page
    $target = $minfo['name'];

    xarResponseRedirect(xarModURL('modules', 'admin', 'list', array('state' => 0), NULL, $target));

    return true;
}

?>
