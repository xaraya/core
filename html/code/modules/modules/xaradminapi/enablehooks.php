<?php
/**
 * @package modules
 * @subpackage modules module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * 
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
 * @throws BAD_PARAM
 */
function modules_adminapi_enablehooks($args)
{
    // Security Check (called by other modules, so we can't use one this here)
    //    if(!xarSecurityCheck('ManageModules')) return;

    // Get arguments from argument array
    extract($args);

    // Argument check
    if (empty($callerModName)) throw new EmptyParameterException('callerModName');
    if (empty($hookModName))   throw new EmptyParameterException('hookModName');

    // CHECKME: don't allow hooking to yourself !?
    if ($callerModName == $hookModName) {
        throw new BadParameterException('hookModName');
    }

    if (empty($callerItemType)) {
        $callerItemType = '';
    }

    // Rename operation
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    // Delete hooks regardless
    try {
        $dbconn->begin();
        // TODO: do this differently, the baseinfo function is supposed to be protected
        $smodInfo = xarMod_GetBaseInfo($callerModName);
        $smodId = $smodInfo['systemid'];
        $tmodInfo = xarMod_GetBaseInfo($hookModName);
        $tmodId = $tmodInfo['systemid'];
        $sql = "DELETE FROM $xartable[hooks] WHERE s_module_id = ? AND s_type = ? AND t_module_id = ?";
        $bindvars = array($smodId,$callerItemType,$tmodId);
        $dbconn->Execute($sql,$bindvars);

        $sql = "SELECT DISTINCT id, s_module_id, s_type, object,
                                action, t_area, t_module_id, t_type,
                                t_func, t_file
                FROM $xartable[hooks]
                WHERE t_module_id = ?";
//                WHERE s_module_id = ? AND t_module_id = ?";
        $stmt1 = $dbconn->prepareStatement($sql);
//        $result = $stmt1->executeQuery(array(null,$tmodId));
        $result = $stmt1->executeQuery(array($tmodId));

        // Prepare the statement outside the loop
        $sql = "INSERT INTO $xartable[hooks]
                (object,action,s_module_id,s_type,t_area,t_module_id,t_type,t_func,t_file)
                VALUES (?,?,?,?,?,?,?,?,?)";
        $stmt = $dbconn->prepareStatement($sql);

        while($result->next()) {
            list($hookid,$hooksmodId,$hookstype,$hookobject,$hookaction,
                 $hooktarea,$tmodId,$hookttype,$hooktfunc,$hooktfile) = $result->fields;

            $bindvars = array($hookobject, $hookaction, $smodId,
                              $callerItemType, $hooktarea, $tmodId,
                              $hookttype, $hooktfunc,$hooktfile);
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
