<?php

/**
 * main themes module function
 * @return themes_admin_main
 *
 */
function themes_admin_main()
{
    // Security Check
	if(!xarSecurityCheck('AdminTheme')) return;

    if (xarModGetVar('adminpanels', 'overview') == 0){
        // Return the output
        return array();
    } else {
        xarResponseRedirect(xarModURL('themes', 'admin', 'list'));
    }
    // success
    return true;
}

?>
