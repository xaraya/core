<?php

/**
 * Upgrade a module
 *
 * Loads module admin API and calls the upgrade function
 * to actually perform the upgrade, then redrects to
 * the list function and with a status message and returns
 * true.
 *
 * @param id the module id to upgrade
 * @returns
 * @return
 */
function modules_admin_upgrade()
{
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) return;

    if (!xarVarFetch('id', 'int:1:', $id)) return; 

    // Upgrade module
    $upgraded = xarModAPIFunc('modules',
                             'admin',
                             'upgrade',
                             array('regid' => $id));
    //throw back
    if(!isset($upgraded)) return;
    $minfo=xarModGetInfo($id);
    // set the target location (anchor) to go to within the page 
    $target=$minfo['name'];
    // Hmmm, I wonder if the target adding is considered a hack
    // it certainly depends on the implementation of xarModUrl
    //    xarResponseRedirect(xarModURL('modules', 'admin', "list#$target"));
    xarResponseRedirect(xarModURL('modules', 'admin', "list", array('state' => 0), NULL, $target));
    
    return true;
}

?>