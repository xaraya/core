<?php

/**
 * Update module information
 * @param $args['regid'] the id number of the module to update
 * @param $args['displayname'] the new display name of the module
 * @param $args['description'] the new description of the module
 * @returns bool
 * @return true on success, false on failure
 */
function modules_adminapi_update($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regid)) {
        $msg = xarML('Empty regid (#(1)).', $regid);
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

// Security Check
	if(!xarSecurityCheck('AdminModules',0,'All',"All:All:$regid")) return;

    // Rename operation
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    // Hooks

    // Get module name
    $modinfo = xarModGetInfo($regid);

    // Delete hook regardless
    $sql = "DELETE FROM $xartable[hooks]
            WHERE xar_smodule = '" . xarVarPrepForStore($modinfo['name']) . "'";

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
            WHERE xar_smodule =''";

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

        // Get selected value of hook
        $hookvalue = xarVarCleanFromInput("hooks_$hooktmodule");

        // See if this is checked and isn't in the database
        if ((isset($hookvalue)) && (is_array($hookvalue)) && (empty($hooksmodname))) {
            // Insert hook if required
            foreach (array_keys($hookvalue) as $itemtype) {
                if ($itemtype == 0) $itemtype = '';
                $sql = "INSERT INTO $xartable[hooks] (
                      xar_id,
                      xar_object,
                      xar_action,
                      xar_smodule,
                      xar_stype,
                      xar_tarea,
                      xar_tmodule,
                      xar_ttype,
                      xar_tfunc)
                    VALUES (
                      " . xarVarPrepForStore($dbconn->GenId($xartable['hooks'])) . ",
                      '" . xarVarPrepForStore($hookobject) . "',
                      '" . xarVarPrepForStore($hookaction) . "',
                      '" . xarVarPrepForStore($modinfo['name']) . "',
                      '" . xarVarPrepForStore($itemtype) . "',
                      '" . xarVarPrepForStore($hooktarea) . "',
                      '" . xarVarPrepForStore($hooktmodule) . "',
                      '" . xarVarPrepForStore($hookttype) . "',
                      '" . xarVarPrepForStore($hooktfunc) . "')";
                $subresult =& $dbconn->Execute($sql);
                if (!$subresult) return;
            }
        }
    }
    $result->Close();

    return true;
}

?>
