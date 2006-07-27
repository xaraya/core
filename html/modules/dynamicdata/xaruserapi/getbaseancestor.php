<?php

function &dynamicdata_userapi_getbaseancestor($args)
{
    if(!xarSecurityCheck('ViewDynamicDataItems')) return;

    $ancestors = xarModAPIFunc('dynamicdata','user','getancestors',$args);
    $ancestors = array_shift($ancestors);
    return $ancestors;
}

?>
