<?php

/**
 * Set preferences for modules module
 *
 * @access public
 * @param none
 * @returns array
 * @todo 
 */
function modules_admin_prefs(){
    
    // Security check
    if(!xarSecurityCheck('AdminModules')) return;
    
    $data = array();
    
    // done
    return $data;
}

?>