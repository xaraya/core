<?php

/**
 * Initialise a module
 *
 * Loads module admin API and calls the initialise
 * function to actually perform the initialisation,
 * then redirects to the list function with a
 * status message and returns true.
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
    	xarVarFetch('command', 'enum', $command, 0, XARVAR_NOT_REQUIRED);
    	
    	//Let's make a nice GUI to show the user the options
    	$data = array();
    	$data['id'] = $id;
    	$data['dependencies'] = xarModAPIFunc('modules','admin','getalldependencies',array('regid'=>$id));
    	foreach ($data['dependencies'] as $dep) {
    		if ($dep 
    	}
    	return $data;
    }

    // Initialise module
    $initialised = xarModAPIFunc('modules',
                                'admin',
                                'initialise',
                                array('regid' => $id));

    // throw back exception (may be NULL or false)
    if (empty($initialised)) return;
    $minfo=xarModGetInfo($id);
    // set the target location (anchor) to go to within the page
    $target=$minfo['name'];

    xarResponseRedirect(xarModURL('modules', 'admin', "list", array('state' => 0), NULL, $target));

    return true;
}

?>