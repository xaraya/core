<?php

/**
 * Blocks Functions
 */
function blocks_admin_main()
{

// Security Check
	if(!xarSecurityCheck('EditBlock')) return;

    if (xarModGetVar('adminpanels', 'overview') == 0){
        // Return the output
        return array();
    } else {
        xarResponseRedirect(xarModURL('blocks', 'admin', 'view_instances'));
    }
    // success
    return true;
}

?>