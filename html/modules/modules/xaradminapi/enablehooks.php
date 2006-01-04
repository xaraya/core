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
 * Enable hooks between a caller module and a hook module
 * Note : hooks will be enabled for all item types if no specific item type is given
 *
 * @author Xaraya Development Team
 * @param $args['callerModName'] caller module
 * @param $args['callerItemType'] optional item type for the caller module
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
    if (empty($callerModName)) throw new EmptyParameterException('callerModName');
    if (empty($hookModName))   throw new EmptyParameterException('hookModName');
 
    if (empty($callerItemType)) {
        $callerItemType = '';
    }

    // Rename operation
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    // Delete hooks regardless
    try {
        $dbconn->begin();
        $sql = "DELETE FROM $xartable[hooks]
                WHERE xar_smodule = ? AND xar_stype = ? AND xar_tmodule = ?";
        $bindvars = array($callerModName,$callerItemType,$hookModName);
        $dbconn->Execute($sql,$bindvars);
       
        $sql = "SELECT DISTINCT xar_id, xar_smodule, xar_stype, xar_object,
                                xar_action, xar_tarea, xar_tmodule, xar_ttype,
                                xar_tfunc
                FROM $xartable[hooks]
                WHERE xar_smodule = '' AND xar_tmodule = ?";
        $result = $dbconn->Execute($sql,array($hookModName));
        
        // Prepare the statement outside the loop
        $sql = "INSERT INTO $xartable[hooks] 
                (xar_id,xar_object,xar_action,xar_smodule,xar_stype,xar_tarea,xar_tmodule,xar_ttype,xar_tfunc)
                VALUES (?,?,?,?,?,?,?,?,?)";
        $stmt = $dbconn->prepareStatement($sql);

        for (; !$result->EOF; $result->MoveNext()) {
            list($hookid, $hooksmodname, $hookstype, $hookobject, 
                 $hookaction,  $hooktarea, $hooktmodule, $hookttype,
                 $hooktfunc) = $result->fields;
            
            $bindvars = array($dbconn->GenId($xartable['hooks']),
                              $hookobject, $hookaction, $callerModName,
                              $callerItemType, $hooktarea, $hooktmodule,
                              $hookttype, $hooktfunc);
            $stmt->executeUpdate($bindvars);
        }
        $dbconn->commit();
        $stmt->close();
    } catch (SQLException $e) {
        $dbconn->rollback();
        throw $e;
    }
    $result->close();

    return true;
}

?>
