<?php
/**
 * Enable hooks between a caller module and a hook module
 *
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
        // TODO: do this differently, the baseinfo function is supposed to be protected
        $smodInfo = xarMod_GetBaseInfo($callerModName);
        $smodId = $smodInfo['systemid'];
        $tmodInfo = xarMod_GetBaseInfo($hookModName);
        $tmodId = $tmodInfo['systemid'];
        $sql = "DELETE FROM $xartable[hooks] WHERE xar_smodid = ? AND xar_stype = ? AND xar_tmodid = ?";
        $bindvars = array($smodId,$callerItemType,$tmodId);
        $dbconn->Execute($sql,$bindvars);

        $sql = "SELECT DISTINCT xar_id, xar_smodid, xar_stype, xar_object,
                                xar_action, xar_tarea, xar_tmodid, xar_ttype,
                                xar_tfunc
                FROM $xartable[hooks]
                WHERE xar_smodid = ? AND xar_tmodid = ?";
        $stmt1 = $dbconn->prepareStatement($sql);
        $result = $stmt1->executeQuery(array(0,$tmodId));

        // Prepare the statement outside the loop
        $sql = "INSERT INTO $xartable[hooks]
                (xar_id,xar_object,xar_action,xar_smodid,xar_stype,xar_tarea,xar_tmodid,xar_ttype,xar_tfunc)
                VALUES (?,?,?,?,?,?,?,?,?)";
        $stmt = $dbconn->prepareStatement($sql);

        while($result->next()) {
            list($hookid,$hooksmodId,$hookstype,$hookobject,$hookaction,
                 $hooktarea,$tmodId,$hookttype,$hooktfunc) = $result->fields;

            $bindvars = array($dbconn->GenId($xartable['hooks']),
                              $hookobject, $hookaction, $smodId,
                              $callerItemType, $hooktarea, $tmodId,
                              $hookttype, $hooktfunc);
            $stmt->executeUpdate($bindvars);
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
