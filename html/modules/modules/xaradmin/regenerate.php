<?php

/**
 * Regenerate list of available modules
 *
 * Loads module admin API and calls the regenerate function
 * to actually perform the regeneration, then redirects
 * to the list function with a status meessage and returns true.
 *
 * @access public
 * @param none
 * @returns bool
 * @
 */
function modules_admin_regenerate()
{
    // Security check
    if (!xarSecConfirmAuthKey()) return;

    // Regenerate modules
    $regenerated = xarModAPIFunc('modules', 'admin', 'regenerate');
    
    if (!isset($regenerated)) return;
    
    // Redirect
    xarResponseRedirect(xarModURL('modules', 'admin', 'list'));

    return true;
}

?>
