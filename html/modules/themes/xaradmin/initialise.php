<?php

/**
 * Initialise a theme
//TODO: <johnny> update for exceptions
 *
 * Loads theme admin API and calls the initialise
 * function to actually perform the initialisation,
 * then redirects to the list function with a
 * status message and returns true.
 *
 * @param id the theme id to initialise
 * @returns
 * @return
 */
function themes_admin_initialise()
{
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) return;

    $id = xarVarCleanFromInput('id');
    if (!isset($id)) {
        $msg = xarML('No theme id specified',
                    'themes');
        xarExceptionSet(XAR_USER_EXCEPTION,
                    'MISSING_DATA',
                     new DefaultUserException($msg));
        return;
    }

    // Initialise theme
    $initialised = xarModAPIFunc('themes',
                                'admin',
                                'initialise',
                                array('regid' => $id));

    if (!isset($initialised)) return;

    // Success
    xarSessionSetVar('themes_statusmsg', xarML('Theme Initialised',
                                        'themes'));

    xarResponseRedirect(xarModURL('themes', 'admin', 'list'));

    return true;
}

?>