<?php

/**
 * main modules module function
 * @return modules_admin_main
 *
 */
function modules_admin_main()
{
    // Security Check
    if(!xarSecurityCheck('AdminModules')) return;

    if (xarModGetVar('adminpanels', 'overview') == 0){
        // Return the output
        return array();
    } else {
        xarResponseRedirect(xarModURL('modules', 'admin', 'list'));
    }
    // success
    return true;
}

?>
