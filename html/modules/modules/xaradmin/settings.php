<?php

/**
 * List modules and current settings
 * @param several params from the associated form in template
 *
 */
function modules_admin_settings()
{
    // Security Check
    if(!xarSecurityCheck('AdminModules')) return;

    // form parameters
    $hidecore   = xarVarCleanFromInput('hidecore');
    $regen      = xarVarCleanFromInput('regen');
    $selstyle   = xarVarCleanFromInput('selstyle');
    $selfilter  = xarVarCleanFromInput('selfilter');
    $selsort    = xarVarCleanFromInput('selsort');
    // make sure we dont miss empty variables (which were not passed thru)
    if(empty($selstyle)) $selstyle                  = 'plain';
    if(empty($selfilter)) $selfilter                = XARMOD_STATE_ANY;
    if(empty($hidecore)) $hidecore                  = 0;
    if(empty($selsort)) $selsort                    = 'namedesc';
    
    xarModSetVar('modules', 'hidecore', $hidecore);
    xarModSetVar('modules', 'selstyle', $selstyle);
    xarModSetVar('modules', 'selfilter', $selfilter);
    xarModSetVar('modules', 'selsort', $selsort);
    
    xarResponseRedirect(xarModURL('modules', 'admin', 'list', array('regen' => $regen)));
}

?>
