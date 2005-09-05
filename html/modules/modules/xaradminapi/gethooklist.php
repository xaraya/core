<?php
/**
 * File: $Id$
 *
 * Obtain list of hooks (optionally for a particular module)
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage modules module
 * @author Xaraya Team 
 */
/**
 * Obtain list of hooks (optionally for a particular module)
 *
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
    // MrB: changed the IS NULL statement to ='', query returned no records.
    $query = "SELECT DISTINCT xar_smodule, xar_stype,
                            xar_tmodule,
                            xar_object,
                            xar_action,
                            xar_tarea
            FROM $xartable[hooks] ";

    if (!empty($modName)) {
        $query .= " WHERE xar_smodule=''
                       OR xar_smodule = '" . xarVarPrepForStore($modName) . "'
                 ORDER BY xar_tmodule,
                          xar_smodule DESC";
    } else {
        $query .= " ORDER BY xar_tmodule";
    }

    $result =& $dbconn->Execute($query);
    if(!$result) return;

    // hooklist will hold the available hooks
    $hooklist = array();
    for (; !$result->EOF; $result->MoveNext()) {
        list($smodName, $itemType, $tmodName,$object,$action,$area) = $result->fields;

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