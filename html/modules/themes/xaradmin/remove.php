<?php

/**
 * Remove a theme
 *
 * Loads theme admin API and calls the remove function
 * to actually perform the removal, then redirects to
 * the list function with a status message and retursn true.
 *
 * @access public
 * @param  id the theme id
 * @returns mixed
 * @return true on success
 */
function themes_admin_remove()
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

    // Remove theme
    $removed = xarModAPIFunc('themes',
                            'admin',
                            'remove',
                            array('regid' => $id));
    //throw back
    if(!isset($removed)) return;

    //Success
    xarSessionSetVar('themes_statusmsg', xarML('Theme Removed',
                                        'themes'));

    xarResponseRedirect(xarModURL('themes', 'admin', 'list'));

    return true;
}

?>