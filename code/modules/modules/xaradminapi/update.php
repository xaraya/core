<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
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
    if (!isset($regid)) throw new EmptyParameterException('regid');

    // Security Check
    if(!xarSecurityCheck('AdminModules',0,'All',"All:All:$regid")) return;

    // Rename operation
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    // Get module name
    $modinfo = xarModGetInfo($regid);

    // Make it atomic
    try {
        $dbconn->begin();
        // Delete hook regardless
        $sql = "DELETE FROM $xartable[hooks] WHERE s_module_id = ?";
        $dbconn->Execute($sql,array($modinfo['systemid']));

        $sql = "SELECT DISTINCT id, s_module_id, s_type, object,
                            action, t_area, t_module_id, t_type,
                            t_func
                FROM $xartable[hooks]
                WHERE s_module_id IS NULL";
        $stmt = $dbconn->prepareStatement($sql);
        $result = $stmt->executeQuery();

        $modList = xarModAPIFunc('modules', 'admin', 'getlist');
        $todo = array();
        foreach ($modList as $mod) $todo[$mod['systemid']] = $mod['name'];

        while($result->next()) {
            list($hookid,$hooksmodid,$hookstype,$hookobject,
                 $hookaction,$hooktarea,$hooktmodid,$hookttype,$hooktfunc) = $result->fields;

            // Get selected value of hook
            unset($hookvalue);
            // ignore modules that are missing or in some weird state
            if (!isset($todo[$hooktmodid])) continue;
            xarVarFetch("hooks_" . $todo[$hooktmodid], 'isset', $hookvalue,  NULL, XARVAR_DONT_SET);
            // See if this is checked and isn't in the database
            if ((isset($hookvalue)) && (is_array($hookvalue)) && (empty($hooksmodid))) {
                // Insert hook if required
                // Prepare statement outside the loop
                $sql = "INSERT INTO $xartable[hooks]
                    (object,action,s_module_id,s_type,t_area,t_module_id,t_type,t_func)
                    VALUES (?,?,?,?,?,?,?,?)";
                $stmt2 = $dbconn->prepareStatement($sql);

                foreach (array_keys($hookvalue) as $itemtype) {
                    if ($itemtype == 0) $itemtype = '';
                    $bindvars = array($hookobject,$hookaction,$modinfo['systemid'],
                                      $itemtype,$hooktarea,$hooktmodid,
                                      $hookttype,$hooktfunc);
                    $stmt2->executeUpdate($bindvars);
                }
            }
        }
        $dbconn->commit();
    } catch (SQLException $e) {
        $dbconn->rollback();
        throw $e;
    }
    $result->close();
    return true;
}

?>
