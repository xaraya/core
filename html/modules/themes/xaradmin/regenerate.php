<?php

/**
 * Regenerate list of available themes
 *
 * Loads theme admin API and calls the regenerate function
 * to actually perform the regeneration, then redirects
 * to the list function with a status meessage and returns true.
 *
 * @access public
 * @param none
 * @returns bool
 * @
 */
function themes_admin_regenerate()
{
    // Security check
    if (!xarSecConfirmAuthKey()) return;
    // Regenerate themes
    $regenerated = xarModAPIFunc('themes', 'admin', 'regenerate');

    if (!isset($regenerated)) return;

    //Success
    xarSessionSetVar('themes_statusmsg', xarML('Theme list regenerated from filesystem',
                                        'themes'));

    // Redirect
    xarResponseRedirect(xarModURL('themes', 'admin', 'list'));

    return true;
}

?>