<?php

/**
 * Upgrade a theme
 *
 * Loads theme admin API and calls the upgrade function
 * to actually perform the upgrade, then redrects to
 * the list function and with a status message and returns
 * true.
 *
 * @param id the theme id to upgrade
 * @returns
 * @return
 */
function themes_admin_upgrade()
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

    // Upgrade theme
    $upgraded = xarModAPIFunc('themes',
                             'admin',
                             'upgrade',
                             array('regid' => $id));
    //throw back
    if(!isset($upgraded)) return;

    // Success
    xarSessionSetVar('themes_statusmsg', xarML('Theme Upgraded',
                                        'themes'));

    xarResponseRedirect(xarModURL('themes', 'admin', 'list'));

    return true;
}

?>