<?php

/**
 * Activate a theme
 *
 * Loads theme admin API and calls the activate
 * function to actually perform the activation,
 * then redirects to the list function with a
 * status message and returns true.
 *
 * @param id the theme id to activate
 * @returns
 * @return
 */
function themes_admin_activate()
{
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) return;

    $id = xarVarCleanFromInput('id');
    if (empty($id)) {
        $msg = xarML('No theme id specified',
                    'themes');
        xarExceptionSet(XAR_USER_EXCEPTION,
                    'MISSING_DATA',
                     new DefaultUserException($msg));
        return;
    }

    // Activate
    $activated = xarModAPIFunc('themes',
                              'admin',
                              'activate',
                              array('regid' => $id));

    //throw back
    if (!isset($activated)) return;

    // Set State
    $set = xarModAPIFunc('themes',
                              'admin',
                              'setstate',
                              array('regid' => $id,
                                    'state' => XARTHEME_STATE_ACTIVE));

    //throw back
    if (!isset($set)) return;

    // Success
    xarSessionSetVar('themes_statusmsg', xarML('Theme Activated',
                                        'themes'));

    xarResponseRedirect(xarModURL('themes', 'admin', 'list'));

    return true;
}

?>