<?php

/**
 * the main administration function - pass-thru
 */
function privileges_admin_main()
{

// Security Check
    if(!xarSecurityCheck('ViewPrivileges')) return;

    if (xarModGetVar('adminpanels', 'overview') == 0){
        return array();
    } else {
        xarResponseRedirect(xarModURL('privileges', 'admin', 'viewprivileges'));
    }
    // success
    return true;

}


?>