<?php
/**
 * File: $Id$
 *
 * Get a list of modules calling a particular hook module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @subpackage modules module
 * @author Xaraya Team 
 */
/**
 * Get list of modules calling a particular hook module
 *
 * @param $args['hookModName'] hook module we're looking for
 * @param $args['hookObject'] the object of the hook (item, module, ...) (optional)
 * @param $args['hookAction'] the action on that object (transform, display, ...) (optional)
 * @param $args['hookArea'] the area we're dealing with (GUI, API) (optional)
 * @returns array
 * @return array of modules calling this hook module
 * @raise BAD_PARAM
 */
function modules_adminapi_gethookedmodules($args)
{
// Security Check (called by other modules, so we can't use one this here)
//    if(!xarSecurityCheck('AdminModules')) return;

    // Get arguments from argument array
    extract($args);

    // Argument check
    if (empty($hookModName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'hookModName');
        return;
    }

    list($dbconn) = xarDBGetConn();
    $xartable      =& xarDBGetTables();

    $query = "SELECT DISTINCT xar_smodule, xar_stype
              FROM $xartable[hooks] 
              WHERE xar_tmodule= '" . xarVarPrepForStore($hookModName) . "'";
    if (!empty($hookObject)) {
        $query .= " AND xar_object = '" . xarVarPrepForStore($hookObject) . "'";
    }
    if (!empty($hookAction)) {
        $query .= " AND xar_action = '" . xarVarPrepForStore($hookAction) . "'";
    }
    if (!empty($hookArea)) {
        $query .= " AND xar_tarea = '" . xarVarPrepForStore($hookArea) . "'";
    }

    $result =& $dbconn->Execute($query);
    if(!$result) return;

    // modlist will hold the hooked modules
    $modlist = array();
    for (; !$result->EOF; $result->MoveNext()) {
        list($callerModName,$callerItemType) = $result->fields;
        if (empty($callerModName)) continue;
        if (empty($callerItemType)) {
            $callerItemType = 0;
        }
        $modlist[$callerModName][$callerItemType] = 1;
    }
    $result->Close();

    return $modlist;
}

