<?php

/**
 * Load admin part of the module in question
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  true on success
 * @throws  no exceptions
 * @todo    nothing
*/
function adminpanels_admin_loadnewmod(){
    // Get vars

    if (!xarVarFetch('mname','str:1:',$mname,'adminpanels')) return;

    xarResponseRedirect(xarModURL($mname, 'admin', 'main'));
    return true;
}

?>