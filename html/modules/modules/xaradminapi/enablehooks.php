<?php

/**
 * Enable hooks between a caller module and a hook module
 *
 * @param $args['callerModName'] caller module
 * @param $args['hookModName'] hook module
 * @returns bool
 * @return true if successfull
 * @raise BAD_PARAM
 */
function modules_adminapi_enablehooks($args)
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

    $sql = "SELECT DISTINCT xar_id,
                            xar_smodule,
                            xar_stype,
                            xar_object,
                            xar_action,
                            xar_tarea,
                            xar_tmodule,
                            xar_ttype,
                            xar_tfunc
            FROM $xartable[hooks]
            WHERE xar_smodule = ''
              AND xar_tmodule = '" . xarVarPrepForStore($hookModName) . "'";

    $result =& $dbconn->Execute($sql);
    if (!$result) return;

    for (; !$result->EOF; $result->MoveNext()) {
        list($hookid,
             $hooksmodname,
             $hookstype,
             $hookobject,
             $hookaction,
             $hooktarea,
             $hooktmodule,
             $hookttype,
             $hooktfunc) = $result->fields;

        $sql = "INSERT INTO $xartable[hooks] (
                      xar_id,
                      xar_object,
                      xar_action,
                      xar_smodule,
                      xar_tarea,
                      xar_tmodule,
                      xar_ttype,
                      xar_tfunc)
                    VALUES (
                      " . xarVarPrepForStore($dbconn->GenId($xartable['hooks'])) . ",
                      '" . xarVarPrepForStore($hookobject) . "',
                      '" . xarVarPrepForStore($hookaction) . "',
                      '" . xarVarPrepForStore($callerModName) . "',
                      '" . xarVarPrepForStore($hooktarea) . "',
                      '" . xarVarPrepForStore($hooktmodule) . "',
                      '" . xarVarPrepForStore($hookttype) . "',
                      '" . xarVarPrepForStore($hooktfunc) . "')";
        $subresult =& $dbconn->Execute($sql);
        if (!$subresult) return;
    }
    $result->Close();

    return true;
}

?>