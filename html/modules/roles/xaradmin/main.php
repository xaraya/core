<?php

/**
 * the main administration function
 */
function roles_admin_main()
{
    // Security Check
    if (!xarSecurityCheck('EditRole')) return;

    if (xarModGetVar('adminpanels', 'overview') == 0) {
        return array();
    } else {
        xarResponseRedirect(xarModURL('roles', 'admin', 'viewroles'));
    }
    // success
    return true;
}

?>