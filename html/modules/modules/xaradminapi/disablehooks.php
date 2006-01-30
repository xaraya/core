<?php
/**
 * Disable hooks between a caller module and a hook module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Disable hooks between a caller module and a hook module
 * Note : generic hooks will not be disabled if a specific item type is given
 *
 * @author Xaraya Development Team
 * @param $args['callerModName'] caller module
 * @param $args['callerItemType'] optional item type for the caller module
 * @param $args['hookModName'] hook module
 * @returns bool
 * @return true if successfull
 * @raise BAD_PARAM
 */
function modules_adminapi_disablehooks($args)
{
    // Security Check (called by other modules, so we can't use one this here)
    //    if(!xarSecurityCheck('AdminModules')) return;

    // Get arguments from argument array
    extract($args);

    // Argument check
    if (empty($callerModName)) throw new EmptyParameterException('callerModName');
    if (empty($hookModName))  throw new EmptyParameterException('hookModName');

    if (empty($callerItemType)) {
        $callerItemType = '';
    }

    // Rename operation
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    // Delete hooks regardless
    // New query: select on the mod id's instead of their names
    // optionally: get the ids first and then use the select
    // better: construct a join with the modules table but not possible for postgres for example
    $smodInfo = xarMod_GetBaseInfo($callerModName);
    $smodId = $smodInfo['systemid'];
    $tmodInfo = xarMod_GetBaseInfo($hookModName);
    $tmodId = $tmodInfo['systemid'];
    $sql = "DELETE FROM $xartable[hooks] WHERE xar_smodid = ? AND xar_stype = ? AND xar_tmodid = ?";
    $stmt = $dbconn->prepareStatement($sql);

    try {
        $dbconn->begin();
        $bindvars = array($smodId,$callerItemType,$tmodId);
        $stmt->executeUpdate($bindvars);
        $dbconn->commit();
    } catch (SQLException $e) {
        $dbconn->rollback();
        throw $e;
    }

    return true;
}

?>
