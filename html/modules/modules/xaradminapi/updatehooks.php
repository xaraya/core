<?php
/**
 * Update hooks for a particular hook module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Update hooks for a particular hook module
 *
 * @author Xaraya Development Team
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
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

// Security Check
    if(!xarSecurityCheck('AdminModules',0,'All',"All:All:$regid")) return;

    // Get database connection and table names
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    // Get module name
    $modinfo = xarModGetInfo($regid);
    if (empty($modinfo['name'])) {
        $msg = xarML('Invalid module name');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    // Delete all entries of modules using this hook (but don't delete the '' module)
    // signaling there *is* a hook, we want to keep that knowledge in
    $sql = "DELETE FROM $xartable[hooks] WHERE xar_tmodule = ? AND xar_smodule <> ''";
    $result =& $dbconn->Execute($sql,array($modinfo['name']));
    if (!$result) return;

    // get the list of all (active) modules
    $modList = xarModAPIFunc('modules', 'admin', 'getlist');
    //throw back
    if (!isset($modList)) return;

    // see for which one(s) we need to enable this hook
    $todo = array();
    foreach ($modList as $mod) {
        // Get selected value of hook (which is an array of all the itemtypes selected)
        // hooked_$mod['name'][0] contains the global setting ( 0 -> not, 1 -> all, 2 -> some)
        xarVarFetch("hooked_" . $mod['name'],'isset',$ishooked,'',XARVAR_DONT_REUSE);
        // No setting or explicit NOT, skip it (note: empty shouldn't occur anymore
        if (!empty($ishooked) && $ishooked[0] != 0) {
            // There is something in there, either for all itemtypes or for some
            $todo[$mod['name']] = $ishooked;
        }
    }

    // nothing more to do here
    if (count($todo) < 1) {
        return true;
    }

    // get the list of individual hooks offered by this module
    $sql = "SELECT DISTINCT xar_id, xar_smodule, xar_stype, xar_object,
                            xar_action, xar_tarea, xar_tmodule, xar_ttype,
                            xar_tfunc
            FROM $xartable[hooks]
            WHERE xar_tmodule = ?";

    $result =& $dbconn->Execute($sql,array($modinfo['name']));
    if (!$result) return;

    for (; !$result->EOF; $result->MoveNext()) {
        list($hookid, $hooksmodname, $hookstype, $hookobject, $hookaction,
             $hooktarea, $hooktmodule, $hookttype, $hooktfunc) = $result->fields;

        // Avoid single-space module names e.g. for mssql
        if (!empty($hooksmodname)) {
            $hooksmodname = trim($hooksmodname);
        }

        // See if this is checked and isn't in the database
        if (empty($hooksmodname)) {
            foreach ($todo as $modname => $hookvalue) {
                // Insert hook if required
                xarLogMessage('Value: ' . $hookvalue[0] . ' for ' . $modname);

                // If user specified ALL specifically, set itemtype hard to empty
                if ($hookvalue[0] == 1) {
                    $itemtype = '';
                    $sql = "INSERT INTO $xartable[hooks] (
                                xar_id, xar_object, xar_action, xar_smodule,
                                xar_stype, xar_tarea, xar_tmodule, xar_ttype, xar_tfunc)
                                VALUES (?,?,?,?,?,?,?,?,?)";
                    $bindvars = array($dbconn->GenId($xartable['hooks']),
                                      $hookobject, $hookaction, $modname,
                                      $itemtype, $hooktarea, $hooktmodule,
                                      $hookttype,$hooktfunc);
                    $subresult =& $dbconn->Execute($sql,$bindvars);
                    if (!$subresult) return;
                    // we're done for this module
                    continue;
                }

                // If user specified SOME specifically, skip itemtype 0
                foreach (array_keys($hookvalue) as $itemtype) {
                    // If user specified SOME specifically, skip itemtype 0
                    if ($hookvalue[0] == 2 && $itemtype == 0) continue;

                    $sql = "INSERT INTO $xartable[hooks] (
                                xar_id, xar_object, xar_action, xar_smodule,
                                xar_stype, xar_tarea, xar_tmodule, xar_ttype, xar_tfunc)
                                VALUES (?,?,?,?,?,?,?,?,?)";
                    $bindvars = array($dbconn->GenId($xartable['hooks']),
                                      $hookobject, $hookaction, $modname,
                                      $itemtype, $hooktarea, $hooktmodule,
                                      $hookttype,$hooktfunc);
                    $subresult =& $dbconn->Execute($sql,$bindvars);
                    if (!$subresult) return;
                }
            }
        }
    }
    $result->Close();

    return true;
}

?>