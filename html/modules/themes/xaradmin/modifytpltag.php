<?php

/**
 * List themes and current settings
 * @param none
 */
function themes_admin_modifytpltag()
{
	// Security Check
	if (!xarSecurityCheck('AdminTheme', 0, 'All', '::')) return;
	
	$aData = array();

	// form parameters
	if (!xarVarFetch('tagname', 'str::', $tagname, '')) return;

	// get the tags as an array
	$aTplTag = xarModAPIFunc('themes', 
	                         'admin', 
	                         'gettpltag', 
	                         array('tagname'=>$tagname));

	$aData = $aTplTag;
	$aData['authid'] = xarSecGenAuthKey();
	$aData['updateurl'] = xarModUrl('themes', 
	                                'admin', 
	                                'updatetpltag');

	return $aData;
}

?>