<?php

function &dynamicdata_userapi_getbaseitemtype($args)
{
    if(!xarSecurityCheck('ViewDynamicDataItems')) return;

    $base = xarModAPIFunc('dynamicdata','user','getbaseancestor',$args);
    return $base['itemtype'];
}

?>
