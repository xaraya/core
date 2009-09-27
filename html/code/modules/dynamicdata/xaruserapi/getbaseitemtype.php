<?php

function &dynamicdata_userapi_getbaseitemtype($args)
{
    if(!xarSecurityCheck('ViewDynamicDataItems')) return;

    $base = xarMod::apiFunc('dynamicdata','user','getbaseancestor',$args);
    return $base['itemtype'];
}

?>
