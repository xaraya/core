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
    $regen      = xarVarCleanFromInput('regen');
    $selstyle   = xarVarCleanFromInput('selstyle');
    $selfilter  = xarVarCleanFromInput('selfilter');
    $selsort    = xarVarCleanFromInput('selsort');

    // make sure we dont miss empty variables (which were not passed thru)
    if(empty($selstyle)) $selstyle                  = 'plain';
    if(empty($selfilter)) $selfilter                = XARTHEME_STATE_ANY;
    if(empty($selsort)) $selsort                    = 'namedesc';

    xarModSetVar('themes', 'selstyle', $selstyle);
    xarModSetVar('themes', 'selfilter', $selfilter);
    xarModSetVar('themes', 'selsort', $selsort);

    xarResponseRedirect(xarModURL('themes', 'admin', 'list', array('regen' => $regen)));
}

?>
