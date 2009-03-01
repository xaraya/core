<?php
/**
 * Remove a module when the files are missing
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Remove a module when the files are missing
 *
 * @author Xaraya Development Team
 * @param $args['regid'] the id of the module
 * @returns bool
 * @return true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function modules_adminapi_removemissing($args)
{
    // Get arguments from argument array
    extract($args);

    // TODO (random) This whole exercise is on hold because w have no way of knowing which
    // tables actually belong to the module being removed, and so the cleanup is incomplete
    // For now just remove the entry in the modules table

    //    if (!xarVarFetch('remove', 'str', $remove, NULL, XARVAR_NOT_REQUIRED)) return;
    // Get module information
    //    $modinfo = xarModGetInfo($regid);
    $dbconn = xarDB::getConn();
    $tables = xarDB::getTables();

    $modInfo = xarModGetInfo($regid);
    $modId = $modInfo['systemid'];
    // Make what we do at least atomic
    try {
        $dbconn->begin();
        $query = "DELETE FROM $tables[modules] WHERE id = ?";
        $dbconn->Execute($query,array($modId));
        $dbconn->commit();
    } catch (SQLException $e) {
        $dbconn->rollback();
        throw $e;
    }

    return true;
}

?>
