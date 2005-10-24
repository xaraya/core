<?php

function &dynamicdata_userapi_getbaseancestor($args)
{
    extract($args);

    if (!isset($objectid)) {
        $msg = xarML('Wrong arguments to dynamicdata_userapi_getancestors.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    }

    if(!xarSecurityCheck('ViewDynamicDataItems')) return;

    $ancestors = xarModAPIFunc('dynamicdata','user','getancestors',$args);
    return array_shift($ancestors);

}

?>
