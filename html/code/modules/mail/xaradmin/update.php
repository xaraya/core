<?php

function mail_admin_update($args = array())
{
     // Security
    if (!xarSecurityCheck('EditMail')) return;
     
    // Need to pass object en itemid ourselves now as update has the 'object_' prefix apparently, doh!
    if(!xarVarFetch('objectid',   'isset', $args['objectid'],    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemid',     'isset', $args['itemid'],      NULL, XARVAR_DONT_SET)) {return;}

    return xarMod::guiFunc('dynamicdata','admin','update',$args);
}
?>