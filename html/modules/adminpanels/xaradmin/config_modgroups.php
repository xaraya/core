<?php

/**
 * Add, delete, modify groups in admin menu
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  true on success or void on failure
 * @throws  no exceptions
 * @todo    stub atm, needs to be completed
*/
function adminpanels_admin_config_modgroups(){
    // Get vars

    // redirect back to adminpanels configuration
    xarResponseRedirect(xarModURL('adminpanels', 'admin', 'modifyconfig'));
    return true;
}

?>