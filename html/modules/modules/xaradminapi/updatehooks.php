<?php
/**
 * File: $Id$
 *
 * Update hooks for a prticular hook module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage modules module
 * @author Xaraya Team 
 */
/**
 * Update hooks for a particular hook module
 *
 * @param $args['regid'] the id number of the hook module
 * @returns bool
 * @return true on success, false on failure
 */
function modules_adminapi_updatehooks($args)
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

    // Get database connection and table names
    list($dbconn) = xarDBGetConn();
    $xartable = xarDBGetTables();

    // Get module name
    $modinfo = xarModGetInfo($regid);
    if (empty($modinfo['name'])) {
        $msg = xarML('Invalid module name');
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    // Delete all entries of modules using this hook (but don't delete the '' module)
    $sql = "DELETE FROM $xartable[hooks]
            WHERE xar_tmodule = '" . xarVarPrepForStore($modinfo['name']) . "'
            AND   xar_smodule <> ''";

    $result =& $dbconn->Execute($sql);
    if (!$result) return;

    // get the list of all (active) modules
    $modList = xarModAPIFunc('modules', 
                             'admin', 
                             'GetList');
    //throw back
    if (!isset($modList)) return;

    // see for which one(s) we need to enable this hook
    $todo = array();
    foreach ($modList as $mod) {
        // Get selected value of hook
        $ishooked = xarVarCleanFromInput("hooked_" . $mod['name']);
        if (!empty($ishooked)) {
            $todo[$mod['name']] = $ishooked;
        }
    }

    // nothing more to do here
    if (count($todo) < 1) {
        return true;
    }

    // get the list of individual hooks offered by this module
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
            WHERE xar_tmodule ='" . xarVarPrepForStore($modinfo['name']) . "'";

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

        // See if this is checked and isn't in the database
        if (empty($hooksmodname)) {
            foreach ($todo as $modname => $hookvalue) {
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
                          '" . xarVarPrepForStore($modname) . "',
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
    }
    $result->Close();

    return true;
}

?>
