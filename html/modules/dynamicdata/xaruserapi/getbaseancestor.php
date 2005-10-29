<?php

function &dynamicdata_userapi_getbaseancestor($args)
{
    extract($args);

    if(!xarSecurityCheck('ViewDynamicDataItems')) return;

    $ancestors = xarModAPIFunc('dynamicdata','user','getancestors',$args);
    return array_shift($ancestors);

}

?>
