<?php

/**
 * Initialise a theme
 * //TODO: <johnny> update for exceptions
 * 
 * Loads theme admin API and calls the initialise
 * function to actually perform the initialisation,
 * then redirects to the list function with a
 * status message and returns true.
 * 
 * @param id $ the theme id to initialise
 * @returns 
 * @return 
 */
function themes_admin_initialise()
{ 
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) return;

    if (!xarVarFetch('id', 'int:1:', $id)) return; 
    // Initialise theme
    $initialised = xarModAPIFunc('themes',
        'admin',
        'initialise',
        array('regid' => $id));

    if (!isset($initialised)) return;

    xarResponseRedirect(xarModURL('themes', 'admin', 'list'));

    return true;
} 

?>