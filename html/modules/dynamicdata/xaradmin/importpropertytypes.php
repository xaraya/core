<?php
function dynamicdata_admin_importpropertytypes ($args)
{
    
    $args['flush'] = 'false';
    $success = xarModAPIFunc('dynamicdata','admin','importpropertytypes', $args);
    
    return array();
}
?>