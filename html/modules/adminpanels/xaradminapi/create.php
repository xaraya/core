<?php

/**
 * create a new admin item (a stub, not used atm)
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   none
 * @return  true on success or void on failure
 * @throws  NO_PERMISSION exception
 * @todo    nothing
*/
function adminpanels_adminapi_create($args){

    extract($args);

    // Security Check
    if(!xarSecurityCheck('AddPanel',1,'Item',"$name:All:All")) return;

    // not used yet, just return
    return true;
}

?>