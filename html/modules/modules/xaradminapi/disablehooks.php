<?php

/**
 * Disable hooks between a caller module and a hook module
 *
 * @param $args['callerModName'] caller module
 * @param $args['hookModName'] hook module
 * @returns bool
 * @return true if successfull
 * @raise BAD_PARAM
 */
function modules_adminapi_disablehooks($args)
{
// Security Check (called by other modules, so we can't use one this here)
//    if(!xarSecurityCheck('AdminModules')) return;

    // Get arguments from argument array
    extract($args);

    // Argument check
    if (empty($callerModName) || empty($hookModName)) {
        $msg = xarML('callerModName or hookModName');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', $msg);
        return;
    }

    // Rename operation
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

// TODO: per item type

    // Delete hooks regardless
    $sql = "DELETE FROM $xartable[hooks]
            WHERE xar_smodule = '" . xarVarPrepForStore($callerModName) . "'
              AND xar_tmodule = '" . xarVarPrepForStore($hookModName) . "'";

    $result =& $dbconn->Execute($sql);
    if (!$result) return;

    return true;
}

?>