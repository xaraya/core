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

    if (!xarVarFetch('id', 'int:1:', $id)) return; 
    // Upgrade theme
    $upgraded = xarModAPIFunc('themes',
                             'admin',
                             'upgrade',
                             array('regid' => $id));
    //throw back
    if(!isset($upgraded)) return;

    xarResponseRedirect(xarModURL('themes', 'admin', 'list'));

    return true;
}

?>