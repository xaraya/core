<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */

/**
 * Obtain list of hooks (optionally for a particular module)
 *
 * @param $args['modName'] optional module we're looking for
 * @return array of known hooks
 */
function modules_adminapi_gethooklist($args)
{
    // Security Check
    if(!xarSecurityCheck('AdminModules')) return;

    // Get arguments from argument array
    extract($args);

    // Argument check
    if (empty($modName)) {
        $smodId = null;
        $modName = '';
    } else {
        $smodInfo = xarMod_GetBaseInfo($modName);
        $smodId = $smodInfo['systemid'];
    }

    $dbconn  =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    // TODO: allow finer selection of hooks based on type etc., and
    //       filter out irrelevant ones (like module remove, search...)
    $bindvars = array();
    $query = "SELECT DISTINCT h.s_type, h.object, h.action, h.t_area, h.t_type,
                              h.t_func, h.s_module_id, h.t_module_id,
                              t.name
              FROM $xartable[hooks] h, $xartable[modules] t
              WHERE h.t_module_id = t.id ";

    if ($smodId != 0) {
        // Only get the hooks for $modName
        $query .= " AND ( h.s_module_id IS NULL OR  h.s_module_id = ? ) ";
        //   ORDER BY tmods.name,smods.name DESC";
        $bindvars[] = $smodId;
    } else {
        //$query .= " ORDER BY smods.name";
    }
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery($bindvars);

    // hooklist will hold the available hooks
    $hooklist = array();
    while($result->next()) {
        list($itemType, $object,$action,$area,$tmodType,$tmodFunc,$smodId,$tmodId,$tmodName) = $result->fields;

        // Avoid single-space item types e.g. for mssql
        if (!empty($itemType)) $itemType = trim($itemType);

        if (!isset($hooklist[$tmodName]))
            $hooklist[$tmodName] = array();
        if (!isset($hooklist[$tmodName]["$object:$action:$area"]))
            $hooklist[$tmodName]["$object:$action:$area"] = array();
        // if the smodName has a value the hook is active
        if (!empty($smodId)) {
            if (!isset($hooklist[$tmodName]["$object:$action:$area"][$smodId]))
                $hooklist[$tmodName]["$object:$action:$area"][$smodId] = array();
            if (empty($itemType))
                $itemType = 0;
            $hooklist[$tmodName]["$object:$action:$area"][$smodId][$itemType] = 1;
        }
    }
    $result->close();

    return $hooklist;
}

?>
