<?php

/**
 * List modules and current settings
 * @param several params from the associated form in template
 *
 */
function themes_admin_settings()
{
    // Security Check
	if(!xarSecurityCheck('AdminTheme')) return;
    // form parameters
    if (!xarVarFetch('selstyle', 'str:1:', $selstyle, 'plain', XARVAR_NOT_REQUIRED)) return; 
    if (!xarVarFetch('selfilter', 'str:1:', $selfilter, 'XARTHEME_STATE_ANY', XARVAR_NOT_REQUIRED)) return; 
    if (!xarVarFetch('selsort', 'str:1:', $selsort, 'namedesc', XARVAR_NOT_REQUIRED)) return; 
    if (!xarVarFetch('regen', 'str:1:', $regen, XARVAR_NOT_REQUIRED)) return; 

    xarModSetVar('themes', 'selstyle', $selstyle);
    xarModSetVar('themes', 'selfilter', $selfilter);
    xarModSetVar('themes', 'selsort', $selsort);

    xarResponseRedirect(xarModURL('themes', 'admin', 'list', array('regen' => $regen)));
}

?>