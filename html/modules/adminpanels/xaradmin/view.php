<?php

/**
 * standard admin overview
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  data for template or void on failure
 * @throws  XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION'
*/
function adminpanels_admin_view(){

    // Security Check
    if(!xarSecurityCheck('EditPanel')) return;

    // TODO: prepare the overview based on what is configured by config
    $data = array();

    // push data to template
    return $data;
}

?>