<?php

/**
 * Deactivate a	theme
 * 
 * Loads theme admin API and calls the setstate
 * function	to actually	perfrom	the	deactivation,
 * then	redirects to the list function with	a status
 * message and returns true.
 * 
 * @access public 
 * @param id $ the theme id	to deactivate
 * @returns	
 * @return 
 */
function themes_admin_deactivate(){ 
	// Security	and	sanity checks
	if (!xarSecConfirmAuthKey()) return;

	if (!xarVarFetch('id', 'int:1:', $id)) return; 
	// deactivate
	$deactivated = xarModAPIFunc('themes','admin','setstate',array('regid' => $id,'state' => XARTHEME_STATE_INACTIVE)); 
	// throw back
	if (!isset($deactivated)) return;

	xarResponseRedirect(xarModURL('themes',	'admin', 'list'));

	return true;
} 

?>