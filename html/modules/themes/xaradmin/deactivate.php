<?php

/**
 * Deactivate a theme
 *
 * Loads theme admin API and calls the setstate
 * function to actually perfrom the deactivation,
 * then redirects to the list function with a status
 * message and returns true.
 *
 * @access public
 * @param id the theme id to deactivate
 * @returns
 * @return
 */
function themes_admin_deactivate()
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

    // deactivate
    $deactivated = xarModAPIFunc('themes',
                                'admin',
                                'setstate',
                                array('regid' => $id,
                                      'state' => XARTHEME_STATE_INACTIVE));
    //throw back
    if (!isset($deactivated)) return;

    // Success
    xarSessionSetVar('themes_statusmsg', xarML('Theme Deactivated',
                                        'themes'));

    xarResponseRedirect(xarModURL('themes', 'admin', 'list'));

    return true;
}

?>