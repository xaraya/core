<?php

/**
 * Main admin gui function, entry point
 *
 * @return bool
 */
function base_admin_main()
{
// Security Check
    if(!xarSecurityCheck('AdminBase')) return;

    if (xarModGetVar('adminpanels', 'overview') == 0){
        // Return the output
        return array();
    } else {
        xarResponseRedirect(xarModURL('base', 'admin', 'sysinfo'));
    }
    // success
    return true;
}

?>
