<?php

/**
 * reset all menus to the active state
 * this is primarily used to prevent users still having
 * collapsed menus if the administrator turns off 
 * collapseable menu support
 * @return true on success, false on failure
 */
function blocks_userapi_reactivate_menus()
{
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    $ublockstable = $xartable['userblocks'];

    $query="UPDATE $ublockstable 
               SET xar_active='1' 
             WHERE xar_active='0'";

    $result =& $dbconn->Execute($query);
    if (!$result) 
        return;

    return true;
}

?>