<?php

/**
 * Remove a module
 *
 * Loads module admin API and calls the remove function
 * to actually perform the removal, then redirects to
 * the list function with a status message and retursn true.
 *
 * @access public
 * @param  id the module id
 * @returns mixed
 * @return true on success
 */

// Remove/Deactivate/Install GUI functions are basically copied and pasted versions...
// Refactor later on
function modules_admin_remove ()
{
     // Security and sanity checks
    if (!xarSecConfirmAuthKey()) return;

    if (!xarVarFetch('id', 'int:1:', $id)) return; 

	//First check the modules dependencies
    $dependents = xarModAPIFunc('modules','admin','getalldependents',array('regid'=>$id));
	if (count($dependents['active'])      > 0   ||
	    count($dependents['initialised']) > 1 ) {
    	//Checking if the user has already passed thru the GUI:
    	xarVarFetch('command', 'checkbox', $command, false, XARVAR_NOT_REQUIRED);
    } else {
    	//No dependents, jump dependency GUI
    	$command = true;
    }

   	if (!$command) {
  		//Let's make a nice GUI to show the user the options
   		$data                 = array();
   		$data['id']           = $id;
	    $data['authid']       = xarSecGenAuthKey();
   		$data['dependencies'] = $dependents;
   		return $data;
   	}
   	
   	//Installs with dependencies, first initialise the necessary dependecies
   	//then the module itself
	if (!xarModAPIFunc('modules','admin','removewithdependents',array('regid'=>$id))) {
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
