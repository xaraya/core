<?php
/**
 * File: $Id$
 *
 * Update module information
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage modules module
 * @author Xaraya Team
 */
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
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

// Security Check
    if(!xarSecurityCheck('AdminModules',0,'All',"All:All:$regid")) return;

    // Rename operation
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    // Hooks

    // Get module name
    $modinfo = xarModGetInfo($regid);

    // Delete hook regardless
    $sql = "DELETE FROM $xartable[hooks] WHERE xar_smodule = ?";
    $result =& $dbconn->Execute($sql,array($modinfo['name']));
    if (!$result) return;

    $sql = "SELECT DISTINCT xar_id, xar_smodule, xar_stype, xar_object,
                            xar_action, xar_tarea, xar_tmodule, xar_ttype,
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

        // Avoid single-space module names e.g. for mssql
        if (!empty($hooksmodname)) {
            $hooksmodname = trim($hooksmodname);
        }

        // Get selected value of hook
        unset($hookvalue);
        if (!xarVarFetch("hooks_$hooktmodule", 'isset', $hookvalue,  NULL, XARVAR_DONT_SET)) {return;}
        // See if this is checked and isn't in the database
        if ((isset($hookvalue)) && (is_array($hookvalue)) && (empty($hooksmodname))) {
            // Insert hook if required
            foreach (array_keys($hookvalue) as $itemtype) {
                if ($itemtype == 0) $itemtype = '';
                $sql = "INSERT INTO $xartable[hooks] (
                      xar_id, xar_object, xar_action, xar_smodule,
                      xar_stype, xar_tarea, xar_tmodule, xar_ttype, xar_tfunc)
                    VALUES (?,?,?,?,?,?,?,?,?)";
                $bindvars = array($dbconn->GenId($xartable['hooks']),
                                  $hookobject,
                                  $hookaction,
                                  $modinfo['name'],
                                  $itemtype,
                                  $hooktarea,
                                  $hooktmodule,
                                  $hookttype,
                                  $hooktfunc);
                $subresult =& $dbconn->Execute($sql,$bindvars);
                if (!$subresult) return;
            }
        }
    }
    $result->Close();
    return true;
}

?>
