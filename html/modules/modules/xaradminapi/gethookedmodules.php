<?php
/**
 * Get list of modules calling a particular hook module
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Get list of modules calling a particular hook module
 *
 * @author Xaraya Development Team
 * @param $args['hookModName'] hook module we're looking for
 * @param $args['hookObject'] the object of the hook (item, module, ...) (optional)
 * @param $args['hookAction'] the action on that object (transform, display, ...) (optional)
 * @param $args['hookArea'] the area we're dealing with (GUI, API) (optional)
 * @returns array
 * @return array of modules calling this hook module
 * @throws BAD_PARAM
 */
function modules_adminapi_gethookedmodules($args)
{
// Security Check (called by other modules, so we can't use one this here)
//    if(!xarSecurityCheck('AdminModules')) return;

    // Get arguments from argument array
    extract($args);

    // Argument check
    if (empty($hookModName)) throw new EmptyParameterException('hookModName');

    $dbconn = xarDB::getConn();
    $xartable      =& xarDBGetTables();

    $bindvars = array();
    // TODO: This looks awfally similar to gethooklist in xarMod.php, investigate later
    $query = "SELECT DISTINCT smods.name, hooks.s_type
              FROM $xartable[hooks] hooks, $xartable[modules] mods, $xartable[modules] smods
              WHERE hooks.t_module_id = mods.id AND hooks.s_module_id = smods.id AND mods.name = ?";
    $bindvars[] = $hookModName;
    if (!empty($hookObject)) {
        $query .= " AND hooks.object = ?";
        $bindvars[] = $hookObject;
    }
    if (!empty($hookAction)) {
        $query .= " AND hooks.action = ?";
        $bindvars[] = $hookAction;
    }
    if (!empty($hookArea)) {
        $query .= " AND hooks.t_area = ?";
        $bindvars[] = $hookArea;
    }
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery($bindvars);

    // modlist will hold the hooked modules
    $modlist = array();
    while($result->next()) {
        list($callerModName,$callerItemType) = $result->fields;
        if (empty($callerModName)) continue;
        if (empty($callerItemType)) {
            $callerItemType = 0;
        }
        $modlist[$callerModName][$callerItemType] = 1;
    }
    $result->close();

    return $modlist;
}

?>
