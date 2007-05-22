<?php
/**
 * Update hooks for a particular hook module
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
    if (!isset($regid)) throw new EmptyParameterException('regid');

    // Security Check
    if(!xarSecurityCheck('AdminModules',0,'All',"All:All:$regid")) return;

    // Get database connection and table names
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    // Get module name
    $modinfo = xarModGetInfo($regid);
    if (empty($modinfo['name'])) {
        throw new ModuleNotFoundException($regid,'Invalid module name found while updating hooks for module with regid #(1)');
    }

    // Make the whole thing atomic
    try {
        $dbconn->begin();

        // Delete all entries of modules using this hook (but don't delete the '' module)
        // signaling there *is* a hook, we want to keep that knowledge in.
        $sql = "DELETE FROM $xartable[hooks] WHERE t_module_id = ? AND s_module_id <> ?";
        $dbconn->Execute($sql,array($modinfo['systemid'],0));

        // get the list of all (active) modules
        $modList = xarModAPIFunc('modules', 'admin', 'getlist');

        // see for which one(s) we need to enable this hook
        $todo = array();
        foreach ($modList as $mod) {
            // Get selected value of hook (which is an array of all the itemtypes selected)
            // hooked_$mod['name'][0] contains the global setting ( 0 -> not, 1 -> all, 2 -> some)
            xarVarFetch("hooked_" . $mod['name'],'isset',$ishooked,'',XARVAR_DONT_REUSE);
            // No setting or explicit NOT, skip it (note: empty shouldn't occur anymore
            if (!empty($ishooked) && $ishooked[0] != 0) {
                // There is something in there, either for all itemtypes or for some
                $todo[$mod['systemid']] = $ishooked;
            }
        }
        // nothing more to do here
        if (empty($todo)) {
            $dbconn->commit();
            return true;
        }

        // get the list of individual hooks offered by this module
        $sql = "SELECT DISTINCT id, s_module_id, s_type, object,
                            action, t_area, t_module_id, t_type,
                            t_func
                FROM $xartable[hooks]
                WHERE t_module_id = ?";
        $stmt = $dbconn->prepareStatement($sql);
        $result = $stmt->executeQuery(array($modinfo['systemid']));

        // Prepare the insert statement outside the loops
        $sql = "INSERT INTO $xartable[hooks]
            (object,action,s_module_id,s_type,t_area,t_module_id,t_type,t_func)
            VALUES (?,?,?,?,?,?,?,?)";
        $stmt2 = $dbconn->prepareStatement($sql);
        while($result->next()) {
            list($hookid, $hooksmodid, $hookstype, $hookobject, $hookaction,
                 $hooktarea, $hooktmodid, $hookttype, $hooktfunc) = $result->fields;

            // See if this is checked and isn't in the database
            if (empty($hooksmodid)) {
                foreach ($todo as $modId => $hookvalue) {
                    // Insert hook if required
                    xarLogMessage('Value: ' . $hookvalue[0] . ' for ' . $modId);

                    // If user specified ALL specifically, set itemtype hard to empty
                    if ($hookvalue[0] == 1) {
                        $itemtype = ''; // Make this 0 later on
                        $bindvars = array($hookobject, $hookaction, $modId,
                                          $itemtype, $hooktarea, $hooktmodid,
                                          $hookttype,$hooktfunc);
                        $stmt2->executeUpdate($bindvars);
                        // we're done for this module
                        continue;
                    }

                    foreach (array_keys($hookvalue) as $itemtype) {
                        // If user specified SOME specifically, skip itemtype 0
                        if ($hookvalue[0] == 2 && $itemtype == 0) continue;

                        $bindvars = array($hookobject, $hookaction, $modId,
                                          $itemtype, $hooktarea, $hooktmodid,
                                          $hookttype,$hooktfunc);
                        $stmt2->executeUpdate($bindvars);
                    }
                }
            }
        }
        $dbconn->commit();
    } catch(Exception $e) {
        // Catch *any* exception now, split out better, later
        $dbconn->rollback();
        throw $e;
    }
    $result->close();

    return true;
}

?>
