<?php

/**
 * deactivate a block
 * @param $args['bid'] the ID of the block to deactivate
 * @returns bool
 * @return true on success, false on failure
 */
function blocks_adminapi_deactivate($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($bid)) {
        xarSessionSetVar('errormsg', _MODARGSERROR);
        return false;
    }

    // Security
	if(!xarSecurityCheck('EditBlock',1,'Block',"::$bid")) return;

    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();
    $blockstable = $xartable['blocks'];

    // Deactivate
    $query = "UPDATE $blockstable
            SET xar_active = 0
            WHERE xar_bid = " . xarVarPrepForStore($bid);
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    return true;
}

?>