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
 * Obtain list of hooks (optionally for a particular module)
 *
 * @author Xaraya Development Team
 * @param $args['modName'] optional module we're looking for
 * @returns array
 * @return array of known hooks
 * @raise NO_PERMISSION
 */
function modules_adminapi_gethooklist($args)
{
// Security Check
    if(!xarSecurityCheck('AdminModules')) return;

    // Get arguments from argument array
    extract($args);

    // Argument check
    if (empty($modName)) {
        $modName = '';
    }

    $dbconn =& xarDBGetConn();
    $xartable      =& xarDBGetTables();

    // TODO: allow finer selection of hooks based on type etc., and
    //       filter out irrelevant ones (like module remove, search...)
    $bindvars = array();
    $query = "SELECT DISTINCT smods.xar_name, hooks.xar_stype, tmods.xar_name,
                              hooks.xar_object, hooks.xar_action, hooks.xar_tarea, hooks.xar_ttype,
                              hooks.xar_tfunc
              FROM $xartable[hooks] hooks, $xartable[modules] smods, $xartable[modules] tmods
              WHERE hooks.xar_smodid = smods.xarid AND
                    hooks.xar_tmodid = tmods.xarid ";

    if (!empty($modName)) {
        $query .= " AND ( hooks.xar_smodid = ? OR  smods.xar_name = ?) 
                 ORDER BY tmods.xar_name,smods.xar_name DESC";
        $bindvars[] = 0;
        $bindvars[] = $modName;
    } else {
        $query .= " ORDER BY smods.xar_name";
    }
    $result =& $dbconn->Execute($query,$bindvars);
    if(!$result) return;

    // hooklist will hold the available hooks
    $hooklist = array();
    for (; !$result->EOF; $result->MoveNext()) {
        list($smodName, $itemType, $tmodName,$object,$action,$area,$tmodType,$tmodFunc) = $result->fields;

        // Avoid single-space module names e.g. for mssql
        if (!empty($smodName)) {
            $smodName = trim($smodName);
        }
        // Avoid single-space item types e.g. for mssql
        if (!empty($itemType)) {
            $itemType = trim($itemType);
        }

        // Let's check to make sure this isn't a stale hook
        // if it is, unregister it and continue onto the next iteration in the for loop
        if (is_null(xarModGetIdFromName($tmodName))) {
            xarModUnregisterHook($object, $action, $area, $tmodName, $tmodType, $tmodFunc);
            continue;
        }

        if (!isset($hooklist[$tmodName])) $hooklist[$tmodName] = array();
        if (!isset($hooklist[$tmodName]["$object:$action:$area"])) $hooklist[$tmodName]["$object:$action:$area"] = array();
        // if the smodName has a value the hook is active
        if (!empty($smodName)) {
            if (!isset($hooklist[$tmodName]["$object:$action:$area"][$smodName])) $hooklist[$tmodName]["$object:$action:$area"][$smodName] = array();
            if (empty($itemType)) $itemType = 0;
            $hooklist[$tmodName]["$object:$action:$area"][$smodName][$itemType] = 1;
        }
    }
    $result->Close();

    return $hooklist;
}

?>
