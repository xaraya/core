<?php

/**
 * purge users by status
 * @param 'status' the status we are purging
 * @param 'confirmation' confirmation that this item can be purge
 */
function roles_admin_purge($args)
{
    // Get parameters from whatever input we need
    if (!xarVarFetch('state', 'int:1:', $state, NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('confirmation', 'isset', $confirmation, NULL, XARVAR_DONT_SET)) return;

    extract($args);

    // Security Check
    if(!xarSecurityCheck('DeleteRole')) return;

    // Check for confirmation.
    if (empty($confirmation)) {
    $data['submitlabel']    = xarML('Submit');
    $data['authid']         = xarSecGenAuthKey();

    return $data;

    }

    // If we get here it means that the user has confirmed the action

    // Confirm authorisation code
    if (!xarSecConfirmAuthKey()) return;

    // The API function is called
    if (!xarModAPIFunc('roles',
                       'admin',
                       'purge',
                        array('state' => $state))) return;

    xarResponseRedirect(xarModURL('roles', 'admin', 'viewroles'));

    // Return
    return true;
}

?>