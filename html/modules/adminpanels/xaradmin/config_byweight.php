<?php

/**
 * Configure sort order in admin menu by weight
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  true on success or void on failure
 * @throws  no exceptions
 * @todo    stub atm, needs to be completed
*/
function adminpanels_admin_config_byweight(){
    // Get vars

    // redirect back to adminpanels configuration
    xarResponseRedirect(xarModURL('adminpanels', 'admin', 'modifyconfig'));
    return true;
}

?>