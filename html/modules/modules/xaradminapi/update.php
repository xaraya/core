<?php
/**
 * Update module information
 *
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
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    // Get module name
    $modinfo = xarModGetInfo($regid);

    // Make it atomic
    try {
        $dbconn->begin();
        // Delete hook regardless
        $sql = "DELETE FROM $xartable[hooks] WHERE xar_smodid = ?";
        $dbconn->Execute($sql,array($modinfo['systemid']));
        
        $sql = "SELECT DISTINCT xar_id, xar_smodid, xar_stype, xar_object,
                            xar_action, xar_tarea, xar_tmodid, xar_ttype,
                            xar_tfunc
                FROM $xartable[hooks]
                WHERE xar_smodid = ?";
        $stmt = $dbconn->prepareStatement($sql);
        $result = $stmt->executeQuery(array(0));

        while($result->next()) {
            list($hookid,$hooksmodid,$hookstype,$hookobject,
                 $hookaction,$hooktarea,$hooktmodid,$hookttype,$hooktfunc) = $result->fields;
            
            // Get selected value of hook
            unset($hookvalue);
            xarVarFetch("hooks_$hooktmodule", 'isset', $hookvalue,  NULL, XARVAR_DONT_SET);
            // See if this is checked and isn't in the database
            if ((isset($hookvalue)) && (is_array($hookvalue)) && (empty($hooksmodid))) {
                // Insert hook if required
                // Prepare statement outside the loop
                $sql = "INSERT INTO $xartable[hooks] 
                    (xar_id,xar_object,xar_action,xar_smodid,xar_stype,xar_tarea,xar_tmodid,xar_ttype,xar_tfunc)
                    VALUES (?,?,?,?,?,?,?,?,?)";
                $stmt2 = $dbconn->prepareStatement($sql);
                
                foreach (array_keys($hookvalue) as $itemtype) {
                    if ($itemtype == 0) $itemtype = '';
                    $bindvars = array($dbconn->GenId($xartable['hooks']),
                                      $hookobject,$hookaction,$modinfo['systemid'],
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
