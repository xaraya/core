<?php

function mail_admin_update($args = array())
{
    // Need to pass object en itemid ourselves now as update has the 'object_' prefix apparently, doh!
    if(!xarVarFetch('objectid',   'isset', $args['objectid'],    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemid',     'isset', $args['itemid'],      NULL, XARVAR_DONT_SET)) {return;}

    return xarModFunc('dynamicdata','admin','update',$args);
}
?>